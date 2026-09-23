<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPost;

use App\Publication\Entity\Post;
use App\Publication\Exception\PostNotFound;
use App\Publication\ValueObject\PostStatus;
use Doctrine\ORM\EntityManagerInterface;

final class PublicPostQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Черновик, архив и несуществующий slug неразличимы снаружи: всё это 404.
     */
    public function getBySlug(string $slug): PublicPostView
    {
        $view = $this->entityManager->createQueryBuilder()
            ->select(\sprintf(
                'NEW %s(p.id, p.slug, p.title, p.excerpt, p.body, p.metaTitle, p.metaDescription, p.publishedAt, p.updatedAt)',
                PublicPostView::class,
            ))
            ->from(Post::class, 'p')
            ->where('p.slug = :slug')
            ->andWhere('p.status = :published')
            ->setParameter('slug', $slug)
            ->setParameter('published', PostStatus::PUBLISHED)
            ->getQuery()
            ->getOneOrNullResult();

        return $view instanceof PublicPostView ? $view : throw new PostNotFound($slug);
    }
}
