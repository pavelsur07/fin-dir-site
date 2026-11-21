<?php

declare(strict_types=1);

namespace App\Documentation\Controller\Admin;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Documentation\DTO\DocListRequest;
use App\Documentation\DTO\DocPageInput;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Form\DocPageType;
use App\Documentation\Query\AdminDocImageQuery;
use App\Documentation\Query\AdminDocQuery;
use App\Documentation\Service\DocPageCreator;
use App\Documentation\Service\DocPageEditor;
use App\Documentation\Service\DocPagePublisher;
use App\Documentation\Service\DocPageUnpublisher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class DocPageController extends AbstractController
{
    #[Route('/admin/docs/pages', name: 'admin_doc_pages', methods: ['GET'])]
    public function index(AdminDocQuery $query, Request $request, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);

        return $this->render('admin/docs/pages/list.html.twig', ['pager' => $query->pages($input->page, $input->q, $input->status, $input->section), 'filters' => $input, 'sections' => $query->sectionOptions()]);
    }

    #[Route('/admin/docs/pages/new', name: 'admin_doc_page_new', methods: ['GET', 'POST'])]
    public function create(Request $request, AdminDocQuery $query, DocPageCreator $creator): Response
    {
        $input = new DocPageInput();
        $options = array_column($query->sectionOptions(), 'id', 'title');
        $form = $this->createForm(DocPageType::class, $input, ['sections' => $options]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $id = $creator->create($input);

                return $this->redirectToRoute('admin_doc_page_edit', ['id' => $id]);
            } catch (DocConflict $e) {
                $form->addError(new FormError($e->getMessage()));
                $errorResponse = new Response('', 409);
            }
        }

        return $this->render('admin/docs/pages/form.html.twig', ['form' => $form, 'page' => null], $errorResponse ?? null);
    }

    #[Route('/admin/docs/pages/{id}/edit', name: 'admin_doc_page_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, AdminDocQuery $query, DocPageEditor $editor, DocMarkdownProcessor $markdown, AdminDocImageQuery $images): Response
    {
        $page = $query->page($id);
        $input = DocPageInput::fromData($page);
        foreach ($query->targets($id) as $target) {
            if ('related' === $target['type']) {
                $input->relatedLinks .= $markdown->link((string) $target['title'], (int) $target['target_id'])."\n";
            }
        }
        $options = array_column($query->sectionOptions(), 'id', 'title');
        $form = $this->createForm(DocPageType::class, $input, ['sections' => $options, 'slug_locked' => null !== $page['published_at']]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $editor->edit($id, $input);

                return $this->redirectToRoute('admin_doc_page_edit', ['id' => $id]);
            } catch (DocConflict $e) {
                $form->addError(new FormError($e->getMessage()));
                $errorResponse = new Response('', 409);
            }
        }

        return $this->render('admin/docs/pages/form.html.twig', ['form' => $form, 'page' => $page, 'images' => $images->forPage($id, 1)], $errorResponse ?? null);
    }

    #[Route('/admin/docs/pages/{id}/{action}', name: 'admin_doc_status', requirements: ['id' => '\d+', 'action' => 'publish|unpublish'], methods: ['POST'])]
    public function status(int $id, string $action, Request $request, DocPagePublisher $publisher, DocPageUnpublisher $unpublisher): Response
    {
        if (!$this->isCsrfTokenValid('doc-status-'.$id, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $version = $request->getPayload()->getInt('version');
        try {
            if ('publish' === $action) {
                $publisher->publish($id, $version);
            } else {
                $unpublisher->unpublish($id, $version);
            }
        } catch (DocConflict $e) {
            return $this->render('admin/docs/conflict.html.twig', ['reason' => $e->getMessage(), 'id' => $id], new Response('', 409));
        }

        return $this->redirectToRoute('admin_doc_page_edit', ['id' => $id]);
    }

    #[Route('/admin/docs/pages/{id}/preview', name: 'admin_doc_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(int $id, AdminDocQuery $query, DocMarkdownProcessor $markdown, AdminDocImageQuery $images): Response
    {
        $page = $query->page($id);

        return $this->render('admin/docs/pages/preview.html.twig', [
            'page' => $page,
            'article' => $markdown->renderTargets((string) $page['body'], $query->targets($id), $images->referencedByPage($id)),
        ], new Response('', 200, ['Cache-Control' => 'private, no-store']));
    }
}
