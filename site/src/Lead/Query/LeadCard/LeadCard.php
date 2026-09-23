<?php

declare(strict_types=1);

namespace App\Lead\Query\LeadCard;

use App\Lead\ValueObject\ContactType;
use App\Lead\ValueObject\LeadStatus;

/**
 * Карточка обращения для админки: само обращение, заметки и другие обращения
 * с тем же контактом.
 */
final readonly class LeadCard
{
    /**
     * @param list<array{question: string, questionLabel: string, answer: string, answerLabel: string}> $answers
     * @param array<string, string>                                                                     $utm
     * @param array<string, mixed>|null                                                                 $firstTouch  первый визит: channel, source, medium, campaign, content, term, landing, referrer, click, ts
     * @param array<string, mixed>|null                                                                 $lastTouch   последний значимый (не прямой) визит, тот же формат
     * @param int|null                                                                                  $daysToLead  полных дней от первого визита до заявки
     * @param list<array{text: string, createdAt: \DateTimeImmutable}>                                  $notes
     * @param list<array{id: int, createdAt: \DateTimeImmutable, status: LeadStatus}>                   $sameContact
     */
    public function __construct(
        public int $id,
        public string $formKey,
        public string $name,
        public string $contact,
        public ContactType $contactType,
        public ?string $task,
        public array $answers,
        public ?string $pageUrl,
        public ?string $referrer,
        public array $utm,
        public ?array $firstTouch,
        public ?array $lastTouch,
        public ?int $visits,
        public ?int $daysToLead,
        public ?string $ymClientId,
        public \DateTimeImmutable $consentAt,
        public string $consentVersion,
        public LeadStatus $status,
        public ?string $spamReason,
        public ?\DateTimeImmutable $nextContactAt,
        public ?\DateTimeImmutable $notifiedAt,
        public ?string $notificationError,
        public \DateTimeImmutable $createdAt,
        public int $version,
        public array $notes,
        public array $sameContact,
    ) {
    }
}
