<?php

declare(strict_types=1);

namespace App\Documentation\Controller\Admin;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Documentation\DTO\DocListRequest;
use App\Documentation\Query\AdminDocQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class DocLinkPickerController extends AbstractController
{
    #[Route('/admin/docs/link-picker', name: 'admin_doc_link_picker', methods: ['GET'])]
    public function __invoke(Request $request, AdminDocQuery $query, DocMarkdownProcessor $markdown, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);
        $id = $request->query->getInt('target', 0);
        $selected = null;
        $links = [];
        if ($id > 0) {
            $selected = $query->page($id);
            $links[] = ['title' => 'Вся страница', 'markdown' => $markdown->link((string) $selected['title'], $id)];
            foreach ($markdown->analyze((string) $selected['body'])->headings as $heading) {
                $links[] = ['title' => $heading['title'], 'markdown' => $markdown->link($heading['title'], $id, $heading['id'])];
            }
        }

        return $this->render('admin/docs/link_picker.html.twig', ['pager' => $query->pages($input->page, $input->q), 'q' => $input->q, 'selected' => $selected, 'links' => $links], new Response('', 200, ['Cache-Control' => 'private, no-store']));
    }
}
