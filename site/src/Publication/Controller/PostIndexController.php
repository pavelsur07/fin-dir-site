<?php

declare(strict_types=1);

namespace App\Publication\Controller;

use App\Publication\Query\PublicPostList\PublicPostListQuery;
use App\Publication\ValueObject\PostRubric;
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

        // Неизвестная рубрика -- 404: у фильтра нет «почти правильных» значений.
        $rubricSlug = $request->query->getString('rubric');
        $rubric = '' === $rubricSlug ? null : (PostRubric::tryFrom($rubricSlug) ?? throw $this->createNotFoundException());

        // Нечисловая страница -- 400 (getInt), 0 и вне диапазона -- 404 (Pagerfanta).
        return $this->render('website/pages/publication/blog.html.twig', [
            'pager' => $posts->paginate($request->query->getInt('page', 1), $rubric),
            'rubric' => $rubric,
            'rubrics' => PostRubric::cases(),
        ], $response);
    }
}
