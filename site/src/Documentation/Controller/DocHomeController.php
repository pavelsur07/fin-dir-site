<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use App\Documentation\DTO\DocListRequest;
use App\Documentation\Query\PublicDocHomeQuery;
use App\Documentation\ValueObject\DocUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DocHomeController extends AbstractController
{
    #[Route('/', name: 'docs_home', host: 'docs.vashfindir.ru', methods: ['GET'], priority: 300)]
    public function __invoke(DocUrl $urls, PublicDocHomeQuery $query, Request $request, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');

        return $this->render('website/pages/documentation/index.html.twig', ['pager' => $query->paginate($input->page), 'navigation' => $query->navigation(), 'canonical_url' => $urls->route('docs_home').($input->page > 1 ? '?page='.$input->page : '')], $response);
    }
}
