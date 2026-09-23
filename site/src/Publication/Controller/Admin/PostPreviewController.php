<?php

declare(strict_types=1);

namespace App\Publication\Controller\Admin;

use App\Publication\Adapter\MarkdownRenderer;
use App\Publication\Query\PostEditData\PostEditDataQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class PostPreviewController extends AbstractController
{
    #[Route('/admin/posts/{id}/preview', name: 'admin_post_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, PostEditDataQuery $query, MarkdownRenderer $markdown): Response
    {
        $post = $query->get($id);

        return $this->render('admin/posts/preview.html.twig', [
            'post' => $post,
            'body_html' => $markdown->toHtml($post->body),
        ]);
    }
}
