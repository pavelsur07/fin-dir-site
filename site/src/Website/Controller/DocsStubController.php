<?php

declare(strict_types=1);

namespace App\Website\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * docs.vashfindir.ru: заглушка базы знаний на месте снятого Docmost.
 * Корень -- страница «База знаний в разработке», остальные пути -- 301 на тот же путь основного сайта:
 * ссылки шапки и подвала относительные и ведут на vashfindir.ru, старые адреса Docmost попадают на 404 сайта.
 * priority выше маршрутов основного сайта: иначе «/» на этом хосте забрала бы главная.
 */
final class DocsStubController extends AbstractController
{
    public function __construct(
        #[Autowire('%vf.site_url%')]
        private readonly string $siteUrl,
    ) {
    }

    #[Route('/{path}', name: 'docs_stub', requirements: ['path' => '.*'], defaults: ['path' => ''], host: 'docs.vashfindir.ru', priority: 100)]
    public function __invoke(Request $request, string $path): Response
    {
        if ('' !== $path) {
            // getRequestUri -- путь и query как пришли, в закодированном виде: %3F не превращается в query, CR/LF не попадают в Location.
            return new RedirectResponse($this->siteUrl.$request->getRequestUri(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        return $this->render('website/pages/marketing/docs_stub.html.twig', [], $response);
    }
}
