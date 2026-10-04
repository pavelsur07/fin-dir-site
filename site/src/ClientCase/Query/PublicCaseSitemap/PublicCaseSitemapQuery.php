<?php

declare(strict_types=1);

namespace App\ClientCase\Query\PublicCaseSitemap;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseStatus;
use Doctrine\ORM\EntityManagerInterface;

final class PublicCaseSitemapQuery
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
            ->select('c.slug', 'c.updatedAt')
            ->from(ClientCase::class, 'c')
            ->where('c.status = :published')
            ->setParameter('published', CaseStatus::PUBLISHED)
            ->orderBy('c.publishedAt', 'DESC')
            ->addOrderBy('c.id', 'ASC')
            ->setMaxResults(self::MAX_URLS)
            ->getQuery()
            ->getArrayResult();

        return $rows;
    }
}
