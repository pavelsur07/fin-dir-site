<?php

declare(strict_types=1);

namespace App\Lead\Controller\Admin;

use App\Lead\Query\NewLeadCount\NewLeadCountQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Фрагмент без маршрута: layout админки встраивает его через render(controller()).
 * Так счётчик читается Query, а не запросом из Twig (AGENTS.md §7).
 */
#[IsGranted('ROLE_ADMIN')]
final class LeadCounterController extends AbstractController
{
    public function __invoke(NewLeadCountQuery $query): Response
    {
        return $this->render('admin/leads/_counter.html.twig', ['count' => $query->count()]);
    }
}
