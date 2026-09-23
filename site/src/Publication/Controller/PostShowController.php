<?php

declare(strict_types=1);

namespace App\Publication\Controller;

use App\Publication\Adapter\MarkdownRenderer;
use App\Publication\Query\PublicPost\PostStructuredData;
use App\Publication\Query\PublicPost\PublicPostQuery;
use App\Publication\Query\PublicPostList\PublicPostListQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PostShowController extends AbstractController
{
    private const int RELATED_LIMIT = 3;

    #[Route('/gazeta/{slug}', name: 'gazeta_post', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'], methods: ['GET'])]
    public function __invoke(
        string $slug,
        PublicPostQuery $posts,
        PublicPostListQuery $list,
        MarkdownRenderer $markdown,
        #[Autowire('%vf.site_url%')] string $siteUrl,
    ): Response {
        $post = $posts->getBySlug($slug);

        // Только короткий max-age, без Last-Modified/304: HTML зависит не только от статьи,
        // но и от «Читайте также» и версии ассетов -- 304 по updatedAt отдавал бы устаревшую страницу.
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(300);

        $url = $siteUrl.$this->generateUrl('gazeta_post', ['slug' => $post->slug]);

        return $this->render('website/pages/blog_post.html.twig', [
            'post' => $post,
            'article' => $markdown->renderArticle($post->body),
            'related' => $list->latestExcept($post->id, self::RELATED_LIMIT),
            'canonical_url' => $url,
            'schema' => PostStructuredData::build($post, $url, $siteUrl.$this->generateUrl('gazeta_index'), $siteUrl),
        ], $response);
    }
}
