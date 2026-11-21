<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use App\Documentation\Query\PublicDocImageQuery;
use App\Documentation\ValueObject\DocImageId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocImageController
{
    #[Route('/media/documentation/{id}', name: 'docs_image', host: 'docs.vashfindir.ru', methods: ['GET'], priority: 300, requirements: ['id' => DocImageId::PATTERN])]
    public function __invoke(string $id, Request $request, PublicDocImageQuery $images): Response
    {
        $image = $images->get($id);
        if (null === $image) {
            return new Response('', Response::HTTP_NOT_FOUND, ['Cache-Control' => 'no-store']);
        }

        $response = new Response($image->contents, 200, [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=0, must-revalidate',
        ]);
        $response->setEtag($image->sha256);
        $response->isNotModified($request);

        return $response;
    }
}
