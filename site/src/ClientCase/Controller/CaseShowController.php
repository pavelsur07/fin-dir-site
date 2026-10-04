<?php

declare(strict_types=1);

namespace App\ClientCase\Controller;

use App\ClientCase\Query\CaseDetail\CaseDetailQuery;
use App\ClientCase\Query\PublicCaseList\PublicCaseListQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CaseShowController extends AbstractController
{
    private const int RELATED_LIMIT = 3;

    #[Route('/cases/{slug}', name: 'app_cases_show', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'], methods: ['GET'])]
    public function __invoke(string $slug, CaseDetailQuery $details, PublicCaseListQuery $cases): Response
    {
        // Публичная страница кешируется: сессии нет, персонализации нет.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        $case = $details->find($slug) ?? throw $this->createNotFoundException();

        return $this->render('website/pages/marketing/case_show.html.twig', [
            'case' => $case,
            'related' => $cases->latestExcept($slug, self::RELATED_LIMIT),
        ], $response);
    }
}
