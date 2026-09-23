<?php

declare(strict_types=1);

namespace App\Lead\Query\NewLeadCount;

use App\Lead\Entity\Lead;
use App\Lead\ValueObject\LeadStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Сколько обращений ещё не взято в работу -- для счётчика в меню админки.
 * Спам и обращения в других статусах не считаются.
 */
final class NewLeadCountQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function count(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(Lead::class, 'l')
            ->where('l.status = :status')
            ->setParameter('status', LeadStatus::NEW)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
