<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
}
