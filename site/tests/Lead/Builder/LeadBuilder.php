<?php

declare(strict_types=1);

namespace App\Tests\Lead\Builder;

use App\Lead\Entity\Lead;
use App\Lead\ValueObject\LeadAttribution;
use App\Lead\ValueObject\LeadConsent;

final class LeadBuilder
{
    private string $submissionId = '00000000-0000-4000-8000-000000000001';
    private string $formKey = 'consultation';
    private string $name = 'Иван';
    private string $contact = '+7 900 123-45-67';
    private ?string $spamReason = null;
    private \DateTimeImmutable $createdAt;
    private ?LeadAttribution $attribution = null;
    private ?string $ymClientId = null;

    private function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('2026-09-01 10:00:00');
    }

    public static function aLead(): self
    {
        return new self();
    }

    public function withSubmissionId(string $submissionId): self
    {
        $this->submissionId = $submissionId;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withContact(string $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function spam(string $reason = 'honeypot'): self
    {
        $this->spamReason = $reason;

        return $this;
    }

    public function createdAt(string $at): self
    {
        $this->createdAt = new \DateTimeImmutable($at);

        return $this;
    }

    /**
     * @param array<string, mixed> $data формат localStorage vf_attr; время -- относительно createdAt
     */
    public function withAttribution(array $data, ?string $ymClientId = null): self
    {
        $this->attribution = LeadAttribution::fromJson(json_encode($data, \JSON_THROW_ON_ERROR), $this->createdAt);
        $this->ymClientId = $ymClientId;

        return $this;
    }

    public function build(): Lead
    {
        return new Lead(
            $this->submissionId,
            $this->formKey,
            $this->name,
            $this->contact,
            'Нужен отчёт ДДС',
            [],
            '/',
            null,
            [],
            LeadConsent::VERSION,
            $this->spamReason,
            $this->createdAt,
            $this->attribution,
            $this->ymClientId,
        );
    }
}
