<?php

declare(strict_types=1);

namespace App\Lead\Controller\Admin;

use App\Lead\Query\AdminLeadList\AdminLeadListCriteria;
use App\Lead\Query\AdminLeadList\AdminLeadListQuery;
use App\Lead\ValueObject\LeadFormCatalog;
use App\Lead\ValueObject\LeadStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class LeadListController extends AbstractController
{
    #[Route('/admin/leads', name: 'admin_lead_list', methods: ['GET'])]
    public function __invoke(Request $request, AdminLeadListQuery $query): Response
    {
        $form = $request->query->getString('form') ?: null;
        if (null !== $form && !LeadFormCatalog::has($form)) {
            throw new BadRequestHttpException('Unknown form filter.');
        }

        $criteria = new AdminLeadListCriteria(
            $request->query->getEnum('status', LeadStatus::class),
            $form,
            $request->query->getString('q') ?: null,
            $request->query->getInt('page', 1),
        );

        return $this->render('admin/leads/list.html.twig', [
            'pager' => $query->paginate($criteria),
            'criteria' => $criteria,
            'statuses' => LeadStatus::cases(),
            'forms' => LeadFormCatalog::all(),
        ]);
    }
}
