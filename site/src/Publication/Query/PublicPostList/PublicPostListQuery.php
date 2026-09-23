<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPostList;

use App\Publication\Entity\Post;
use App\Publication\ValueObject\PostStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;

/**
 * Опубликованные статьи для сайта. Черновики и архив сюда не попадают никогда:
 * админский список -- отдельный AdminPostListQuery.
 */
final class PublicPostListQuery
{
    public const int PER_PAGE = 12;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return PagerfantaInterface<PublicPostListItem>
     */
    public function paginate(int $page): PagerfantaInterface
    {
        /** @var PagerfantaInterface<PublicPostListItem> $pager */
        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($this->published(), fetchJoinCollection: false, useOutputWalkers: false),
            $page,
            self::PER_PAGE,
        );

        return $pager;
    }

    /**
     * Последние статьи для блока "Читайте также".
     *
     * @return list<PublicPostListItem>
     */
    public function latestExcept(int $excludeId, int $limit): array
    {
        /** @var list<PublicPostListItem> $items */
        $items = $this->published()
            ->andWhere('p.id <> :exclude')
            ->setParameter('exclude', $excludeId)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $items;
    }

    private function published(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(p.id, p.slug, p.title, p.excerpt, p.publishedAt)', PublicPostListItem::class))
            ->from(Post::class, 'p')
            ->where('p.status = :published')
            ->setParameter('published', PostStatus::PUBLISHED)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');
    }
}
