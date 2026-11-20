<?php

declare(strict_types=1);

namespace App\Documentation\Controller\Admin;

use App\Documentation\DTO\DocListRequest;
use App\Documentation\DTO\DocSectionInput;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Form\DocSectionType;
use App\Documentation\Query\AdminDocQuery;
use App\Documentation\Service\DocSectionCreator;
use App\Documentation\Service\DocSectionEditor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class DocSectionController extends AbstractController
{
    #[Route('/admin/docs/sections', name: 'admin_doc_sections', methods: ['GET'])]
    public function index(AdminDocQuery $query, Request $request, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);

        return $this->render('admin/docs/sections/list.html.twig', ['pager' => $query->sections($input->page)]);
    }

    #[Route('/admin/docs/sections/new', name: 'admin_doc_section_new', methods: ['GET', 'POST'])]
    public function create(Request $request, DocSectionCreator $creator): Response
    {
        $input = new DocSectionInput();
        $form = $this->createForm(DocSectionType::class, $input);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $id = $creator->create($input);

                return $this->redirectToRoute('admin_doc_section_edit', ['id' => $id]);
            } catch (DocConflict $e) {
                $form->addError(new FormError($e->getMessage()));
                $errorResponse = new Response('', 409);
            }
        }

        return $this->render('admin/docs/sections/form.html.twig', ['form' => $form, 'section' => null], $errorResponse ?? null);
    }

    #[Route('/admin/docs/sections/{id}/edit', name: 'admin_doc_section_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, AdminDocQuery $query, DocSectionEditor $editor): Response
    {
        $section = $query->section($id);
        $input = DocSectionInput::fromData($section);
        $form = $this->createForm(DocSectionType::class, $input, ['slug_locked' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $editor->edit($id, $input);

                return $this->redirectToRoute('admin_doc_sections');
            } catch (DocConflict $e) {
                $form->addError(new FormError($e->getMessage()));
                $errorResponse = new Response('', 409);
            }
        }

        return $this->render('admin/docs/sections/form.html.twig', ['form' => $form, 'section' => $section], $errorResponse ?? null);
    }
}
