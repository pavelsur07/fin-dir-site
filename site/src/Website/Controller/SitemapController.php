<?php

declare(strict_types=1);

namespace App\Website\Controller;

use App\Publication\Query\PublicPostSitemap\PublicPostSitemapQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * sitemap.xml генерируется: статьи блога появляются в нём сразу после публикации.
 */
final class SitemapController extends AbstractController
{
    /** Публичные индексируемые страницы. Новая страница сайта добавляется сюда. */
    private const array STATIC_ROUTES = [
        'home', 'app_services_index', 'app_cases_index', 'app_about_index', 'app_partners_index',
        'gazeta_index', 'privacy', 'offer', 'consent',
    ];

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'], format: 'xml')]
    public function __invoke(PublicPostSitemapQuery $posts): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $this->render('website/sitemap.xml.twig', [
            'static_routes' => self::STATIC_ROUTES,
            'posts' => $posts->all(),
        ], $response);
    }
}
