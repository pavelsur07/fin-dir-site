<?php

declare(strict_types=1);

namespace App\Lead\Entity;

use App\Lead\ValueObject\LeadErasureReason;
use Doctrine\ORM\Mapping as ORM;

/**
 * Запись журнала уничтожения обращения. Подтверждает, что и когда удалено, и не
 * содержит персональных данных: ни имени, ни контакта, ни текста заявки. Внешнего
 * ключа на lead_lead нет -- обращение удаляется в той же транзакции.
 */
#[ORM\Entity]
#[ORM\Table(name: 'lead_erasure')]
#[ORM\Index(name: 'lead_erasure_erased_idx', columns: ['erased_at'])]
class LeadErasure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $leadId;

    #[ORM\Column(length: 32, enumType: LeadErasureReason::class)]
    private LeadErasureReason $reason;

    /** ID лида в Битрикс24: копию в CRM удаляют по нему вручную. */
    #[ORM\Column(nullable: true)]
    private ?int $crmLeadId;

    #[ORM\Column]
    private \DateTimeImmutable $leadCreatedAt;

    #[ORM\Column]
    private \DateTimeImmutable $erasedAt;

    public function __construct(int $leadId, LeadErasureReason $reason, ?int $crmLeadId, \DateTimeImmutable $leadCreatedAt, \DateTimeImmutable $erasedAt)
    {
        $this->leadId = $leadId;
        $this->reason = $reason;
        $this->crmLeadId = $crmLeadId;
        $this->leadCreatedAt = $leadCreatedAt;
        $this->erasedAt = $erasedAt;
    }

    public function leadId(): int
    {
        return $this->leadId;
    }

    public function reason(): LeadErasureReason
    {
        return $this->reason;
    }

    public function crmLeadId(): ?int
    {
        return $this->crmLeadId;
    }

    public function erasedAt(): \DateTimeImmutable
    {
        return $this->erasedAt;
    }
}
