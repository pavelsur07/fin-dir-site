<?php

declare(strict_types=1);

namespace App\Website\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RobotsController
{
    #[Route('/robots.txt', name: 'robots', methods: ['GET'], priority: 300)]
    public function __invoke(Request $request): Response
    {
        $base = 'docs.vashfindir.ru' === $request->getHost() ? 'https://docs.vashfindir.ru' : 'https://vashfindir.ru';

        return new Response("User-agent: *\nAllow: /\n\nSitemap: ".$base."/sitemap.xml\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=0, must-revalidate']);
    }
}
