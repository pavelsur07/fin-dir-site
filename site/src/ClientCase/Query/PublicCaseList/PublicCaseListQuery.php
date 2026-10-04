<?php

declare(strict_types=1);

namespace App\ClientCase\Query\PublicCaseList;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Опубликованные кейсы без главного для сетки на сайте. Черновики и архив сюда не попадают никогда.
 */
final class PublicCaseListQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return list<CaseListItem>
     */
    public function all(?CaseIndustry $industry = null): array
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(c.slug, c.industry, c.title, c.problem, c.resultValue, c.resultLabel, c.tags)', CaseListItem::class))
            ->from(ClientCase::class, 'c')
            ->where('c.status = :published')
            ->andWhere('c.featured = false')
            ->setParameter('published', CaseStatus::PUBLISHED)
            ->orderBy('c.publishedAt', 'DESC')
            ->addOrderBy('c.id', 'ASC');

        if (null !== $industry) {
            $query->andWhere('c.industry = :industry')->setParameter('industry', $industry);
        }

        /** @var list<CaseListItem> $items */
        $items = $query->getQuery()->getResult();

        return $items;
    }
}
