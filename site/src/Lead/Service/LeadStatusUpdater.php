<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\Exception\LeadWasModified;
use App\Lead\Repository\LeadRepository;
use App\Lead\ValueObject\LeadStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Clock\ClockInterface;

/**
 * Статус обращения и дата следующего контакта -- одним сохранением.
 */
final class LeadStatusUpdater
{
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function update(int $id, int $expectedVersion, LeadStatus $status, ?\DateTimeImmutable $nextContactAt): void
    {
        $lead = $this->leads->get($id);
        $now = $this->clock->now();

        try {
            $this->entityManager->lock($lead, LockMode::OPTIMISTIC, $expectedVersion);
            $lead->changeStatus($status, $now);
            $lead->scheduleNextContact($nextContactAt, $now);
            $this->entityManager->flush();
        } catch (OptimisticLockException $e) {
            throw new LeadWasModified($id, $e);
        }
    }
}
