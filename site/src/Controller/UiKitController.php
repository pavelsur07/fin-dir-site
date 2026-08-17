<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UiKitController extends AbstractController
{
    #[Route('/ui-kit', name: 'ui_kit', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('website/pages/ui_kit.html.twig');
    }

    #[Route('/ui-kit/sections', name: 'ui_kit_sections', methods: ['GET'])]
    public function sections(): Response
    {
        return $this->render('website/pages/ui_kit_sections.html.twig');
    }
}
