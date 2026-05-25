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

    #[Route('/', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig');
    }

}
