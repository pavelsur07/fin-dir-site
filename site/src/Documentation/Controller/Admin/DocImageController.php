<?php

declare(strict_types=1);

namespace App\Documentation\Controller\Admin;

use App\Documentation\DTO\DocImageUpload;
use App\Documentation\DTO\DocListRequest;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Query\AdminDocImageQuery;
use App\Documentation\Query\AdminDocQuery;
use App\Documentation\Service\DocImageRemover;
use App\Documentation\Service\DocImageUploader;
use App\Documentation\ValueObject\DocImageId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class DocImageController extends AbstractController
{
    #[Route('/admin/docs/pages/{id}/images', name: 'admin_doc_images', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(int $id, Request $request, AdminDocQuery $pages, AdminDocImageQuery $images, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);

        return $this->render('admin/docs/pages/images.html.twig', ['page' => $pages->page($id), 'images' => $images->forPage($id, $input->page)]);
    }

    #[Route('/admin/docs/images/{uuid}', name: 'admin_doc_image', methods: ['GET'], requirements: ['uuid' => DocImageId::PATTERN])]
    public function image(string $uuid, AdminDocImageQuery $images): Response
    {
        $image = $images->get($uuid) ?? throw $this->createNotFoundException();

        return new Response($image->contents, 200, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    #[Route('/admin/docs/pages/{id}/images', name: 'admin_doc_image_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function upload(int $id, Request $request, DocImageUploader $uploader): Response
    {
        $this->assertToken($request, 'doc-image-upload-'.$id);
        try {
            $file = $request->files->get('image');
            if (!$file instanceof UploadedFile || !$file->isValid() || $file->getSize() > DocImageUpload::MAX_INPUT_BYTES) {
                throw new DocConflict('Выберите PNG или WebP размером до 8 МиБ.');
            }
            $result = $uploader->upload($id, $file->getContent());
        } catch (DocConflict $error) {
            return $this->failure($id, $request, $error->getMessage());
        }

        if ('json' === $request->getPreferredFormat()) {
            return $this->json($result->toArray());
        }
        $this->addFlash('success', 'Снимок загружен. Скопируйте Markdown в текст статьи.');

        return $this->redirectToRoute('admin_doc_images', ['id' => $id], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/docs/pages/{id}/images/{uuid}/delete', name: 'admin_doc_image_delete', methods: ['POST'], requirements: ['id' => '\d+', 'uuid' => DocImageId::PATTERN])]
    public function delete(int $id, string $uuid, Request $request, DocImageRemover $remover): Response
    {
        $this->assertToken($request, 'doc-image-delete-'.$id.'-'.$uuid);
        try {
            $remover->remove($id, $uuid);
        } catch (DocConflict $error) {
            return $this->failure($id, $request, $error->getMessage(), Response::HTTP_CONFLICT);
        }

        return 'json' === $request->getPreferredFormat()
            ? new Response('', Response::HTTP_NO_CONTENT)
            : $this->redirectToRoute('admin_doc_images', ['id' => $id], Response::HTTP_SEE_OTHER);
    }

    private function assertToken(Request $request, string $intent): void
    {
        if (!$this->isCsrfTokenValid($intent, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function failure(int $id, Request $request, string $message, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): Response
    {
        if ('json' === $request->getPreferredFormat()) {
            return $this->json(['message' => $message], $status);
        }

        $this->addFlash('doc_image_error', $message);

        return $this->redirectToRoute('admin_doc_images', ['id' => $id], Response::HTTP_SEE_OTHER);
    }
}
