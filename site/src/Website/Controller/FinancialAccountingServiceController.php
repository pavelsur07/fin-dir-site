<?php

declare(strict_types=1);

namespace App\Website\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FinancialAccountingServiceController extends AbstractController
{
    #[Route('/services/finansovyy-uchet', name: 'service_financial_accounting', methods: ['GET'])]
    public function __invoke(): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        return $this->render('website/pages/marketing/service_financial_accounting.html.twig', [], $response);
    }
}
