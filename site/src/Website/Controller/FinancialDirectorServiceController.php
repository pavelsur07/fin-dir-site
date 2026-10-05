<?php

declare(strict_types=1);

namespace App\Website\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Страница услуги «Финансовый директор на аутсорсинге»: статический контент; кейсы подгружает компонент ClientCase:LatestResults.
 */
final class FinancialDirectorServiceController extends AbstractController
{
    #[Route('/services/finansovyy-direktor-na-autsorsinge', name: 'service_financial_director', methods: ['GET'])]
    public function __invoke(): Response
    {
        // Публичная страница кешируется: сессии нет, персонализации нет.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        return $this->render('website/pages/marketing/service_financial_director.html.twig', [], $response);
    }
}
