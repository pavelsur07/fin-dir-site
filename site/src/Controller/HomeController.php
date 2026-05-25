<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/privacy', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig');
    }

    #[Route('/offer', name: 'offer')]
    public function offer(): Response
    {
        return $this->render('home/offer.html.twig');
    }

    #[Route('/consent', name: 'consent')]
    public function consent(): Response
    {
        return $this->render('home/consent.html.twig');
    }

}
