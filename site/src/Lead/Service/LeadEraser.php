<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\Entity\LeadErasure;
use App\Lead\Exception\LeadExportInProgress;
use App\Lead\Exception\LeadNotFound;
use App\Lead\Repository\LeadErasureRepository;
use App\Lead\Repository\LeadRepository;
use App\Lead\ValueObject\LeadErasureReason;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Полное удаление обращения с заметками (152-ФЗ, ст. 21): по запросу человека, по
 * отзыву согласия, по истечении срока хранения или как спама. В той же транзакции
 * пишет запись журнала уничтожения без персональных данных. Копию в Битрикс24 сайт
 * не удаляет: её ID возвращается в записи журнала, чтобы удалить лид в CRM вручную.
 */
final class LeadEraser
{
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly LeadErasureRepository $erasures,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @throws LeadExportInProgress обращение сейчас передаётся в CRM -- повторить позже
     * @throws LeadNotFound         обращения нет (уже удалено)
     */
    public function erase(int $id, LeadErasureReason $reason): LeadErasure
    {
        // Та же блокировка, что у передачи в CRM: иначе лид мог бы создаться в Битрикс24
        // уже после чтения crmLeadId, и его номер не попал бы в журнал.
        if (!$this->leads->tryLockCrmExport($id)) {
            throw new LeadExportInProgress($id);
        }

        try {
            $lead = $this->leads->get($id);
            $erasure = new LeadErasure($id, $reason, $lead->crmLeadId(), $lead->createdAt(), $this->clock->now());

            // Два агрегата -- одна транзакция: обращение не исчезнет без записи в журнале.
            // wrapInTransaction делает flush ровно один раз и фиксирует транзакцию.
            $this->entityManager->wrapInTransaction(function () use ($lead, $erasure): void {
                $this->leads->remove($lead);
                $this->erasures->save($erasure);
            });
        } finally {
            $this->leads->unlockCrmExport($id);
        }

        return $erasure;
    }
}
