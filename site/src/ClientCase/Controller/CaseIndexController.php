<?php

declare(strict_types=1);

namespace App\ClientCase\Controller;

use App\ClientCase\Query\FeaturedCase\FeaturedCaseQuery;
use App\ClientCase\Query\PublicCaseList\PublicCaseListQuery;
use App\ClientCase\ValueObject\CaseIndustry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CaseIndexController extends AbstractController
{
    #[Route('/cases', name: 'app_cases_index', methods: ['GET'])]
    public function __invoke(Request $request, PublicCaseListQuery $cases, FeaturedCaseQuery $featured): Response
    {
        // Публичная страница кешируется: сессии нет, персонализации нет.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        // Неизвестная отрасль -- 404: у фильтра нет «почти правильных» значений.
        $industrySlug = $request->query->getString('industry');
        $industry = '' === $industrySlug ? null : (CaseIndustry::tryFrom($industrySlug) ?? throw $this->createNotFoundException());

        $featuredCase = $featured->find($industry);
        $items = $cases->all($industry);

        return $this->render('website/pages/marketing/cases.html.twig', [
            'industry' => $industry,
            'industries' => CaseIndustry::cases(),
            'featured' => $featuredCase,
            'cases' => $items,
            'total' => \count($items) + (null === $featuredCase ? 0 : 1),
        ], $response);
    }
}
