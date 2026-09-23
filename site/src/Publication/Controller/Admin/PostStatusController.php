<?php

declare(strict_types=1);

namespace App\Publication\Controller\Admin;

use App\Publication\Service\PostStatusChanger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class PostStatusController extends AbstractController
{
    private const array FLASH = [
        'publish' => 'Статья опубликована.',
        'unpublish' => 'Статья снята с публикации.',
        'archive' => 'Статья перенесена в архив.',
        'restore' => 'Статья возвращена в черновики.',
    ];

    #[Route(
        '/admin/posts/{id}/{action}',
        name: 'admin_post_status',
        requirements: ['id' => '\d+', 'action' => 'publish|unpublish|archive|restore'],
        methods: ['POST'],
    )]
    public function __invoke(int $id, string $action, Request $request, PostStatusChanger $changer): Response
    {
        // Не #[IsCsrfTokenValid]: его InvalidCsrfTokenException -- AuthenticationException,
        // и firewall отвечает залогиненному админу редиректом на логин вместо 403.
        if (!$this->isCsrfTokenValid('post-status', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        match ($action) {
            'publish' => $changer->publish($id),
            'unpublish' => $changer->unpublish($id),
            'archive' => $changer->archive($id),
            'restore' => $changer->restore($id),
            default => throw $this->createNotFoundException(),
        };

        $this->addFlash('success', self::FLASH[$action]);

        return 'list' === $request->getPayload()->getString('back')
            ? $this->redirectToRoute('admin_post_list')
            : $this->redirectToRoute('admin_post_edit', ['id' => $id]);
    }
}
