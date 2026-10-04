<?php

declare(strict_types=1);

namespace App\ClientCase\Controller\Admin;

use App\ClientCase\Query\AdminCaseList\AdminCaseListQuery;
use App\ClientCase\ValueObject\CaseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CaseListController extends AbstractController
{
    #[Route('/admin/cases', name: 'admin_case_list', methods: ['GET'])]
    public function __invoke(Request $request, AdminCaseListQuery $query): Response
    {
        // getEnum отвечает 400 на неизвестный статус.
        $status = $request->query->getEnum('status', CaseStatus::class);

        return $this->render('admin/cases/list.html.twig', [
            'cases' => $query->all($status),
            'status' => $status,
            'statuses' => CaseStatus::cases(),
        ]);
    }
}
