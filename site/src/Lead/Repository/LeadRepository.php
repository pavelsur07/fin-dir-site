<?php

declare(strict_types=1);

namespace App\Lead\Repository;

use App\Lead\Entity\Lead;
use App\Lead\Exception\LeadNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class LeadRepository
{
    /** Пространство ключей advisory-блокировок передачи в CRM. */
    private const int CRM_EXPORT_LOCK_SPACE = 7_340_024;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws LeadNotFound
     */
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

    /**
     * Обращения, ещё не переданные в CRM, -- для досылки. Окончательно решает
     * Lead::needsCrmExport(); версии согласия здесь только сужают выборку.
     *
     * @param list<string> $consentVersions
     *
     * @return list<Lead>
     */
    public function findPendingCrmExport(array $consentVersions, \DateTimeImmutable $since, \DateTimeImmutable $createdBefore, int $limit): array
    {
        if ([] === $consentVersions) {
            return [];
        }

        /** @var list<Lead> $leads */
        $leads = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l')
            ->where('l.crmExportedAt IS NULL')
            ->andWhere("l.status <> 'spam'")
            ->andWhere('l.consentVersion IN (:versions)')
            ->andWhere('l.createdAt >= :since')
            ->andWhere('l.createdAt < :createdBefore')
            ->setParameter('versions', $consentVersions)
            ->setParameter('since', $since)
            ->setParameter('createdBefore', $createdBefore)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $leads;
    }

    /**
     * Обращения, согласие по которым дано раньше $consentBefore, -- срок хранения истёк.
     *
     * @return list<int>
     */
    public function findIdsWithConsentBefore(\DateTimeImmutable $consentBefore, int $limit): array
    {
        /** @var list<int> $ids */
        $ids = $this->entityManager->createQueryBuilder()
            ->select('l.id')
            ->from(Lead::class, 'l')
            ->where('l.consentAt < :before')
            ->setParameter('before', $consentBefore)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $ids);
    }

    /**
     * Не даёт двум процессам (кнопка, cron, первая попытка регистрации) одновременно
     * передавать одно обращение в CRM: иначе оба не найдут лид и оба создадут его.
     * Сессионная advisory-блокировка PostgreSQL общая для всех контейнеров.
     */
    public function tryLockCrmExport(int $id): bool
    {
        return (bool) $this->entityManager->getConnection()->fetchOne('SELECT pg_try_advisory_lock(:space, :id)', ['space' => self::CRM_EXPORT_LOCK_SPACE, 'id' => $id]);
    }

    public function unlockCrmExport(int $id): void
    {
        $this->entityManager->getConnection()->executeQuery('SELECT pg_advisory_unlock(:space, :id)', ['space' => self::CRM_EXPORT_LOCK_SPACE, 'id' => $id]);
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
