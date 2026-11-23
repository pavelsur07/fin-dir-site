<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\Exception\LeadExportInProgress;
use App\Lead\Exception\LeadNotFound;
use App\Lead\Repository\LeadRepository;
use App\Lead\ValueObject\LeadConsent;
use App\Lead\ValueObject\LeadErasureReason;
use Psr\Clock\ClockInterface;

/**
 * Удаляет обращения, срок хранения которых истёк (LeadConsent::RETENTION, «3 года
 * с даты отправки заявки»). Каждое удаляется отдельной транзакцией через LeadEraser
 * с записью в журнале: сбой на одном не откатывает уже удалённые. Отдельная транзакция
 * на обращение (вместо одной на всю пачку) -- осознанное исключение из PATTERNS.md 5.1:
 * долгая транзакция держала бы блокировки, а частичный результат здесь корректен.
 */
final class ExpiredLeadPurger
{
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly LeadEraser $eraser,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array{erased: int, crmLeadIds: list<int>} crmLeadIds -- лиды в Битрикс24, которые нужно удалить вручную
     */
    public function purge(int $limit): array
    {
        $result = ['erased' => 0, 'crmLeadIds' => []];

        foreach ($this->leads->findIdsWithConsentBefore(LeadConsent::expiredBefore($this->clock->now()), $limit) as $id) {
            try {
                $erasure = $this->eraser->erase($id, LeadErasureReason::RETENTION_EXPIRED);
            } catch (LeadNotFound|LeadExportInProgress) {
                // Удалено параллельно или сейчас уходит в CRM -- останется следующему запуску.
                continue;
            }
            ++$result['erased'];
            if (null !== $erasure->crmLeadId()) {
                $result['crmLeadIds'][] = $erasure->crmLeadId();
            }
        }

        return $result;
    }
}
