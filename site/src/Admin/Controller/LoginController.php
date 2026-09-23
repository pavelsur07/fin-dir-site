<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    // POST сюда же перехватывает form_login firewall-а admin до контроллера.
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
