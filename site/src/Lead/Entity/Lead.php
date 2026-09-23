<?php

declare(strict_types=1);

namespace App\Lead\Entity;

use App\Lead\ValueObject\ContactType;
use App\Lead\ValueObject\LeadStatus;
use App\Lead\ValueObject\NormalizedContact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Обращение с формы сайта. IP и User-Agent не хранятся (минимизация ПД).
 */
#[ORM\Entity]
#[ORM\Table(name: 'lead_lead')]
#[ORM\UniqueConstraint(name: 'lead_lead_submission_uniq', columns: ['submission_id'])]
#[ORM\Index(name: 'lead_lead_status_idx', columns: ['status'])]
#[ORM\Index(name: 'lead_lead_contact_idx', columns: ['contact_normalized'])]
#[ORM\Index(name: 'lead_lead_created_idx', columns: ['created_at'])]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Одноразовый id отправки: повторный POST не создаёт второе обращение. */
    #[ORM\Column(type: 'guid')]
    private string $submissionId;

    #[ORM\Column(length: 32)]
    private string $formKey;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 200)]
    private string $contact;

    #[ORM\Column(length: 200)]
    private string $contactNormalized;

    #[ORM\Column(length: 16, enumType: ContactType::class)]
    private ContactType $contactType;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $task;

    /** @var list<array{question: string, questionLabel: string, answer: string, answerLabel: string}> */
    #[ORM\Column(type: 'json')]
    private array $answers;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $pageUrl;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $referrer;

    /** @var array<string, string> */
    #[ORM\Column(type: 'json')]
    private array $utm;

    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    #[ORM\Column(length: 16)]
    private string $consentVersion;

    #[ORM\Column(length: 16, enumType: LeadStatus::class)]
    private LeadStatus $status;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $spamReason;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextContactAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $notifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notificationError = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private int $version = 1;

    /** @var Collection<int, LeadNote> */
    #[ORM\OneToMany(targetEntity: LeadNote::class, mappedBy: 'lead', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $notes;

    /**
     * @param list<array{question: string, questionLabel: string, answer: string, answerLabel: string}> $answers
     * @param array<string, string>                                                                     $utm
     * @param ?string                                                                                   $spamReason не null -- обращение сразу помечается спамом
     */
    public function __construct(
        string $submissionId,
        string $formKey,
        string $name,
        string $contact,
        ?string $task,
        array $answers,
        ?string $pageUrl,
        ?string $referrer,
        array $utm,
        string $consentVersion,
        ?string $spamReason,
        \DateTimeImmutable $now,
    ) {
        $normalized = NormalizedContact::fromRaw($contact);

        $this->submissionId = $submissionId;
        $this->formKey = $formKey;
        $this->name = trim($name);
        $this->contact = trim($contact);
        $this->contactNormalized = $normalized->value;
        $this->contactType = $normalized->type;
        $this->task = null === $task || '' === trim($task) ? null : trim($task);
        $this->answers = $answers;
        $this->pageUrl = $pageUrl;
        $this->referrer = $referrer;
        $this->utm = $utm;
        $this->consentAt = $now;
        $this->consentVersion = $consentVersion;
        $this->status = null === $spamReason ? LeadStatus::NEW : LeadStatus::SPAM;
        $this->spamReason = $spamReason;
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->notes = new ArrayCollection();
    }

    public function changeStatus(LeadStatus $status, \DateTimeImmutable $now): void
    {
        if ($status === $this->status) {
            return;
        }

        $this->status = $status;
        $this->updatedAt = $now;
    }

    public function scheduleNextContact(?\DateTimeImmutable $at, \DateTimeImmutable $now): void
    {
        $this->nextContactAt = $at;
        $this->updatedAt = $now;
    }

    public function addNote(string $text, \DateTimeImmutable $now): void
    {
        $this->notes->add(new LeadNote($this, $text, $now));
        $this->updatedAt = $now;
    }

    /** Спам не уведомляет; уже отправленное не шлётся второй раз. */
    public function needsNotification(): bool
    {
        return LeadStatus::SPAM !== $this->status && null === $this->notifiedAt;
    }

    public function markNotified(\DateTimeImmutable $now): void
    {
        $this->notifiedAt = $now;
        $this->notificationError = null;
    }

    public function markNotificationFailed(string $error): void
    {
        $this->notificationError = mb_substr($error, 0, 255);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function formKey(): string
    {
        return $this->formKey;
    }

    /**
     * @return list<array{question: string, questionLabel: string, answer: string, answerLabel: string}>
     */
    public function answers(): array
    {
        return $this->answers;
    }

    public function pageUrl(): ?string
    {
        return $this->pageUrl;
    }

    /**
     * @return array<string, string>
     */
    public function utm(): array
    {
        return $this->utm;
    }

    public function status(): LeadStatus
    {
        return $this->status;
    }

    public function notifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function notificationError(): ?string
    {
        return $this->notificationError;
    }
}
