<?php

declare(strict_types=1);

namespace App\Documentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DocsFallbackController extends AbstractController
{
    public function __construct(#[Autowire('%vf.site_url%')] private readonly string $siteUrl)
    {
    }

    #[Route('/{path}', name: 'docs_fallback', host: 'docs.vashfindir.ru', requirements: ['path' => '.*'], defaults: ['path' => ''], priority: 100)]
    public function __invoke(Request $request, string $path): Response
    {
        if ('' === $path || preg_match('~^(?:articles|sections|search|media/documentation)(?:/|$)|^(?:robots\.txt|sitemap\.xml)$~D', $path)) {
            if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
                throw new MethodNotAllowedHttpException(['GET', 'HEAD']);
            }
            if ('media/documentation' === $path || str_starts_with($path, 'media/documentation/')) {
                return new Response('', Response::HTTP_NOT_FOUND, ['Cache-Control' => 'no-store']);
            }
            throw $this->createNotFoundException();
        }

        return new RedirectResponse($this->siteUrl.$request->getRequestUri(), 301);
    }
}
