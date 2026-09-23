<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPostSitemap;

use App\Publication\Entity\Post;
use App\Publication\ValueObject\PostStatus;
use Doctrine\ORM\EntityManagerInterface;

final class PublicPostSitemapQuery
{
    /** Лимит одного sitemap-файла по протоколу sitemaps.org. */
    private const int MAX_URLS = 50000;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return list<array{slug: string, updatedAt: \DateTimeImmutable}>
     */
    public function all(): array
    {
        /** @var list<array{slug: string, updatedAt: \DateTimeImmutable}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('p.slug', 'p.updatedAt')
            ->from(Post::class, 'p')
            ->where('p.status = :published')
            ->setParameter('published', PostStatus::PUBLISHED)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults(self::MAX_URLS)
            ->getQuery()
            ->getArrayResult();

        return $rows;
    }
}
