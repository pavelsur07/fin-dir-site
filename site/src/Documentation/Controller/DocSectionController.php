<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use App\Documentation\DTO\DocListRequest;
use App\Documentation\Query\PublicDocSectionQuery;
use App\Documentation\ValueObject\DocUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DocSectionController extends AbstractController
{
    #[Route('/sections/{slug}', name: 'docs_section', host: 'docs.vashfindir.ru', methods: ['GET'], priority: 300, requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function __invoke(DocUrl $urls, string $slug, PublicDocSectionQuery $query, Request $request, ValidatorInterface $validator): Response
    {
        $input = DocListRequest::fromRequest($request, $validator);
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');

        return $this->render('website/pages/documentation/section.html.twig', $query->get($slug, $input->page) + ['canonical_url' => $urls->route('docs_section', $slug).($input->page > 1 ? '?page='.$input->page : '')], $response);
    }
}
