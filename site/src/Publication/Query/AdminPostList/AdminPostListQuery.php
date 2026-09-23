<?php

declare(strict_types=1);

namespace App\Publication\Query\AdminPostList;

use App\Publication\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;

/**
 * Список статей для админки: все статусы, включая черновики и архив.
 * Публичный список -- отдельный Query (Stage 6), без флага "показать черновики".
 */
final class AdminPostListQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return PagerfantaInterface<AdminPostListItem>
     */
    public function paginate(AdminPostListCriteria $criteria): PagerfantaInterface
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(\sprintf(
                'NEW %s(p.id, p.title, p.slug, p.status, p.updatedAt, p.publishedAt)',
                AdminPostListItem::class,
            ))
            ->from(Post::class, 'p');

        // В DQL нет NULLS LAST: без этого ключа статьи без даты публикации
        // встали бы в начало сортировки по публикации.
        if (AdminPostSort::PUBLISHED === $criteria->sort) {
            $queryBuilder
                ->addSelect('CASE WHEN p.publishedAt IS NULL THEN 1 ELSE 0 END AS HIDDEN unpublishedLast')
                ->addOrderBy('unpublishedLast', 'ASC');
        }

        $queryBuilder
            ->addOrderBy($criteria->sort->dqlField(), $criteria->sort->direction())
            // Уникальный второй ключ: строки с одинаковой датой не прыгают между страницами.
            ->addOrderBy('p.id', 'DESC');

        if (null !== $criteria->status) {
            $queryBuilder->andWhere('p.status = :status')->setParameter('status', $criteria->status);
        }

        /** @var PagerfantaInterface<AdminPostListItem> $pager */
        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            // Без joins: count через простой COUNT, output walkers не нужны.
            new QueryAdapter($queryBuilder, fetchJoinCollection: false, useOutputWalkers: false),
            $criteria->page,
            AdminPostListCriteria::PER_PAGE,
        );

        return $pager;
    }
}
