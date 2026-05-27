<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CasesController extends AbstractController
{
    #[Route('/cases')]
    public function index(): Response
    {
        return $this->render('cases/index.html.twig');
    }
}
