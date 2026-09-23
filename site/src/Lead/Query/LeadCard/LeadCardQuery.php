<?php

declare(strict_types=1);

namespace App\Lead\Query\LeadCard;

use App\Lead\Entity\Lead;
use App\Lead\Entity\LeadNote;
use App\Lead\Exception\LeadNotFound;
use App\Lead\ValueObject\ContactType;
use App\Lead\ValueObject\LeadAttribution;
use App\Lead\ValueObject\LeadStatus;
use Doctrine\ORM\EntityManagerInterface;

final class LeadCardQuery
{
    private const int SAME_CONTACT_LIMIT = 20;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(int $id): LeadCard
    {
        /** @var array<string, mixed>|null $row */
        $row = $this->entityManager->createQueryBuilder()
            ->select('l.id, l.formKey, l.name, l.contact, l.contactNormalized, l.contactType, l.task, l.answers, l.pageUrl, l.referrer, l.utm, l.attribution, l.ymClientId')
            ->addSelect('l.consentAt, l.consentVersion, l.status, l.spamReason, l.nextContactAt, l.notifiedAt, l.notificationError, l.createdAt, l.version')
            ->from(Lead::class, 'l')
            ->where('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $row) {
            throw new LeadNotFound($id);
        }

        /** @var list<array{text: string, createdAt: \DateTimeImmutable}> $notes */
        $notes = $this->entityManager->createQueryBuilder()
            ->select('n.text, n.createdAt')
            ->from(LeadNote::class, 'n')
            ->where('IDENTITY(n.lead) = :id')
            ->setParameter('id', $id)
            ->orderBy('n.createdAt', 'DESC')
            ->addOrderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();

        /** @var list<array{id: int, createdAt: \DateTimeImmutable, status: LeadStatus}> $sameContact */
        $sameContact = $this->entityManager->createQueryBuilder()
            ->select('l.id, l.createdAt, l.status')
            ->from(Lead::class, 'l')
            ->where('l.contactNormalized = :contact')
            ->andWhere('l.id <> :id')
            ->setParameter('contact', $row['contactNormalized'])
            ->setParameter('id', $id)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(self::SAME_CONTACT_LIMIT)
            ->getQuery()
            ->getResult();

        \assert($row['contactType'] instanceof ContactType && $row['status'] instanceof LeadStatus && $row['createdAt'] instanceof \DateTimeImmutable);

        $attribution = \is_array($row['attribution'] ?? null) ? LeadAttribution::fromStored($row['attribution']) : null;
        $firstVisitAt = $attribution?->firstVisitAt();

        return new LeadCard(
            (int) $row['id'],
            (string) $row['formKey'],
            (string) $row['name'],
            (string) $row['contact'],
            $row['contactType'],
            $row['task'] ?? null,
            $row['answers'],
            $row['pageUrl'] ?? null,
            $row['referrer'] ?? null,
            $row['utm'],
            $attribution?->first,
            $attribution?->last,
            $attribution?->visits,
            $this->daysBetween($firstVisitAt, $row['createdAt']),
            $row['ymClientId'] ?? null,
            $row['consentAt'],
            (string) $row['consentVersion'],
            $row['status'],
            $row['spamReason'] ?? null,
            $row['nextContactAt'] ?? null,
            $row['notifiedAt'] ?? null,
            $row['notificationError'] ?? null,
            $row['createdAt'],
            (int) $row['version'],
            $notes,
            $sameContact,
        );
    }

    /** Часы браузера могут спешить: первый визит «после» заявки считается нулём дней. */
    private function daysBetween(?\DateTimeImmutable $from, \DateTimeImmutable $to): ?int
    {
        if (null === $from) {
            return null;
        }
        $interval = $from->diff($to);

        return 1 === $interval->invert ? 0 : (int) $interval->days;
    }
}
