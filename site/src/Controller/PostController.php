<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/gazeta', name: 'gazeta_index')]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig');
    }

    #[Route('/gazeta/post-1', name: 'gazeta_post_1')]
    public function privacy(): Response
    {
        return $this->render('blog/post/post.html.twig');
    }
}
