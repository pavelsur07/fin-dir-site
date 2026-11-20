<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use App\Documentation\Query\PublicDocSitemapQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocSitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'docs_sitemap', host: 'docs.vashfindir.ru', methods: ['GET'], priority: 300)]
    public function __invoke(PublicDocSitemapQuery $query): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $this->render('website/docs_sitemap.xml.twig', $query->get(), $response);
    }
}
