<?php

declare(strict_types=1);

namespace App\Lead\Controller\Admin;

use App\Lead\Query\LeadCard\LeadCardQuery;
use App\Lead\Service\LeadNotificationSender;
use App\Lead\ValueObject\LeadFormCatalog;
use App\Lead\ValueObject\LeadStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class LeadShowController extends AbstractController
{
    #[Route('/admin/leads/{id}', name: 'admin_lead_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, LeadCardQuery $query, LeadNotificationSender $notifications): Response
    {
        return $this->render('admin/leads/show.html.twig', [
            'lead' => $query->get($id),
            'statuses' => LeadStatus::cases(),
            'forms' => LeadFormCatalog::all(),
            'notifications_enabled' => $notifications->isEnabled(),
        ]);
    }
}
