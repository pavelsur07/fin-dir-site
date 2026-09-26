<?php

declare(strict_types=1);

namespace App\Publication\Controller;

use App\Publication\Query\PublicPostList\PublicPostListQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PostIndexController extends AbstractController
{
    #[Route('/gazeta', name: 'gazeta_index', methods: ['GET'])]
    public function __invoke(Request $request, PublicPostListQuery $posts): Response
    {
        // Публичная страница кешируется: сессии нет, персонализации нет.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        // Нечисловая страница -- 400 (getInt), 0 и вне диапазона -- 404 (Pagerfanta).
        return $this->render('website/pages/publication/blog.html.twig', [
            'pager' => $posts->paginate($request->query->getInt('page', 1)),
        ], $response);
    }
}
