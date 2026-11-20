<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use App\Documentation\DTO\DocListRequest;
use App\Documentation\Query\PublicDocHomeQuery;
use App\Documentation\Query\PublicDocSearchQuery;
use App\Documentation\ValueObject\DocUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DocSearchController extends AbstractController
{
    #[Route('/search', name: 'docs_search', host: 'docs.vashfindir.ru', methods: ['GET'], priority: 300)]
    public function __invoke(DocUrl $urls, PublicDocSearchQuery $query, PublicDocHomeQuery $home, Request $request, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');

        return $this->render('website/pages/documentation/search.html.twig', ['pager' => $query->paginate(trim($input->q), $input->page), 'q' => trim($input->q), 'navigation' => $home->navigation(), 'canonical_url' => $urls->route('docs_search')], $response);
    }
}
