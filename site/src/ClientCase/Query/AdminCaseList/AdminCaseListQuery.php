<?php

declare(strict_types=1);

namespace App\ClientCase\Query\AdminCaseList;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Список кейсов для админки: все статусы, включая черновики и архив.
 * Кейсов десятки, поэтому без пагинации; публичный список -- отдельный PublicCaseListQuery.
 */
final class AdminCaseListQuery
{
    private const int LIMIT = 200;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return list<AdminCaseListItem>
     */
    public function all(?CaseStatus $status = null): array
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(c.id, c.title, c.slug, c.industry, c.status, c.featured, c.updatedAt, c.publishedAt)', AdminCaseListItem::class))
            ->from(ClientCase::class, 'c')
            ->orderBy('c.updatedAt', 'DESC')
            // Уникальный второй ключ: строки с одинаковой датой не меняют порядок между запросами.
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults(self::LIMIT);

        if (null !== $status) {
            $query->andWhere('c.status = :status')->setParameter('status', $status);
        }

        /** @var list<AdminCaseListItem> $items */
        $items = $query->getQuery()->getResult();

        return $items;
    }
}
