<?php

declare(strict_types=1);

namespace App\Website\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Страница услуги «Финансовая модель от профессионала». Пока статичная заглушка без контента.
 */
final class FinancialModelServiceController extends AbstractController
{
    #[Route('/services/finansovaya-model-ot-professionala', name: 'service_financial_model', methods: ['GET'])]
    public function __invoke(): Response
    {
        // Публичная страница кешируется: сессии нет, персонализации нет.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        return $this->render('website/pages/marketing/service_financial_model.html.twig', [], $response);
    }
}
