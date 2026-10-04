<?php

declare(strict_types=1);

namespace App\ClientCase\Controller\Admin;

use App\ClientCase\Service\ClientCaseStatusChanger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CaseStatusController extends AbstractController
{
    private const array FLASH = [
        'publish' => 'Кейс опубликован.',
        'unpublish' => 'Кейс снят с публикации.',
        'archive' => 'Кейс перенесён в архив.',
        'restore' => 'Кейс возвращён в черновики.',
    ];

    #[Route(
        '/admin/cases/{id}/{action}',
        name: 'admin_case_status',
        requirements: ['id' => '\d+', 'action' => 'publish|unpublish|archive|restore'],
        methods: ['POST'],
    )]
    public function __invoke(int $id, string $action, Request $request, ClientCaseStatusChanger $changer): Response
    {
        // Не #[IsCsrfTokenValid]: его InvalidCsrfTokenException -- AuthenticationException,
        // и firewall отвечает залогиненному админу редиректом на логин вместо 403.
        if (!$this->isCsrfTokenValid('case-status', $request->getPayload()->getString('_token'))) {
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
            ? $this->redirectToRoute('admin_case_list')
            : $this->redirectToRoute('admin_case_edit', ['id' => $id]);
    }
}
