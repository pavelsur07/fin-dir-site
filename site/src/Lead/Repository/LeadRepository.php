<?php

declare(strict_types=1);

namespace App\Lead\Repository;

use App\Lead\Entity\Lead;
use App\Lead\Exception\LeadNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class LeadRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(int $id): Lead
    {
        return $this->entityManager->find(Lead::class, $id) ?? throw new LeadNotFound($id);
    }

    public function findBySubmissionId(string $submissionId): ?Lead
    {
        return $this->entityManager->getRepository(Lead::class)->findOneBy(['submissionId' => $submissionId]);
    }

    /**
     * Обращения без отправленного уведомления -- для повтора командой.
     *
     * @return list<Lead>
     */
    public function findPendingNotification(\DateTimeImmutable $since, int $limit): array
    {
        /** @var list<Lead> $leads */
        $leads = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l')
            ->where('l.notifiedAt IS NULL')
            ->andWhere("l.status <> 'spam'")
            ->andWhere('l.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $leads;
    }

    public function save(Lead $lead): void
    {
        $this->entityManager->persist($lead);
    }

    public function remove(Lead $lead): void
    {
        $this->entityManager->remove($lead);
    }
}
