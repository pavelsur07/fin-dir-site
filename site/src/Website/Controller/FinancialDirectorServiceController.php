<?php

declare(strict_types=1);

namespace App\Website\Controller;

use App\ClientCase\Query\PublicCaseList\PublicCaseListQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Страница услуги «Финансовый директор на аутсорсинге»: статический контент и три свежих опубликованных кейса.
 */
final class FinancialDirectorServiceController extends AbstractController
{
    private const int CASES_LIMIT = 3;

    #[Route('/services/finansovyy-direktor-na-autsorsinge', name: 'service_financial_director', methods: ['GET'])]
    public function __invoke(PublicCaseListQuery $cases): Response
    {
        // Публичная страница кешируется: сессии нет, персонализации нет.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        // Три самых свежих опубликованных кейса (по дате публикации). Список не отдаёт признак «главный»,
        // поэтому «избранного» отбора нет; пустой массив скрывает секцию. Пустой slug ничего не исключает.
        return $this->render('website/pages/marketing/service_financial_director.html.twig', [
            'cases' => $cases->latestExcept('', self::CASES_LIMIT),
        ], $response);
    }
}
