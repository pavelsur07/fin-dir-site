<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Documentation\Query\PublicDocImageQuery;
use App\Documentation\Query\PublicDocPageQuery;
use App\Documentation\ValueObject\DocUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocPageController extends AbstractController
{
    #[Route('/articles/{slug}', name: 'docs_page', host: 'docs.vashfindir.ru', methods: ['GET'], priority: 300, requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function __invoke(DocUrl $urls, string $slug, PublicDocPageQuery $query, DocMarkdownProcessor $markdown, PublicDocImageQuery $images): Response
    {
        $view = $query->getBySlug($slug);
        $articleImages = $images->referencedByPage((int) $view['page']['id']);
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');

        return $this->render('website/pages/documentation/page.html.twig', $view + [
            'article' => $markdown->renderTargets((string) $view['page']['body'], $view['targets'], $articleImages, imageViewer: true),
            'image_viewer' => [] !== $articleImages,
            'canonical_url' => $urls->route('docs_page', $slug),
        ], $response);
    }
}
