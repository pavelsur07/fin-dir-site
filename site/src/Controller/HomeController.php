<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lead\ValueObject\LeadConsent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('website/pages/marketing/home.html.twig');
    }

    #[Route('/privacy', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('website/pages/legal/privacy.html.twig');
    }

    #[Route('/offer', name: 'offer')]
    public function offer(): Response
    {
        return $this->render('website/pages/legal/offer.html.twig');
    }

    #[Route('/consent', name: 'consent')]
    public function consent(): Response
    {
        // Редакция на странице совпадает с той, что сохраняется в заявке.
        return $this->render('website/pages/legal/consent.html.twig', ['consent_version' => LeadConsent::VERSION]);
    }
}
