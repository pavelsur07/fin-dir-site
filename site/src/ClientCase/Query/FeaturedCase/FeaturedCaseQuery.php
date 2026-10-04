<?php

declare(strict_types=1);

namespace App\ClientCase\Query\FeaturedCase;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Главный опубликованный кейс. При фильтре по отрасли возвращается, только если относится к ней.
 */
final class FeaturedCaseQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function find(?CaseIndustry $industry = null): ?FeaturedCaseView
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(c.slug, c.industry, c.title, c.task, c.tags, c.steps, c.metrics, c.source)', FeaturedCaseView::class))
            ->from(ClientCase::class, 'c')
            ->where('c.status = :published')
            ->andWhere('c.featured = true')
            ->setParameter('published', CaseStatus::PUBLISHED)
            ->orderBy('c.publishedAt', 'DESC')
            ->addOrderBy('c.id', 'ASC')
            ->setMaxResults(1);

        if (null !== $industry) {
            $query->andWhere('c.industry = :industry')->setParameter('industry', $industry);
        }

        /** @var FeaturedCaseView|null $view */
        $view = $query->getQuery()->getOneOrNullResult();

        return $view;
    }
}
