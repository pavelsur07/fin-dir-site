<?php

declare(strict_types=1);

namespace App\ClientCase\Query\CaseDetail;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Один опубликованный кейс по slug. Черновик и архив здесь не находятся никогда.
 */
final class CaseDetailQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function find(string $slug): ?CaseDetailView
    {
        /** @var CaseDetailView|null $view */
        $view = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(c.slug, c.industry, c.title, c.problem, c.resultValue, c.resultLabel, c.tags, c.task, c.steps, c.metrics, c.source)', CaseDetailView::class))
            ->from(ClientCase::class, 'c')
            ->where('c.slug = :slug')
            ->andWhere('c.status = :published')
            ->setParameter('slug', $slug)
            ->setParameter('published', CaseStatus::PUBLISHED)
            ->getQuery()
            ->getOneOrNullResult();

        return $view;
    }
}
