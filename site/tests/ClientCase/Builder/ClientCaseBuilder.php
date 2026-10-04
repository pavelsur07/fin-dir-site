<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Builder;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseStatus;

/**
 * Валидный кейс с безопасными значениями по умолчанию. Время задаётся явно --
 * build() не читает системные часы.
 */
final class ClientCaseBuilder
{
    private string $slug = 'demo-case';
    private CaseIndustry $industry = CaseIndustry::TRADE;
    private string $title = 'Демо-кейс';
    private CaseStatus $status = CaseStatus::DRAFT;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $publishedAt = null;
    private bool $featured = false;

    private function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('2026-01-10 10:00:00');
    }

    public static function aCase(): self
    {
        return new self();
    }

    public function withSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function inIndustry(CaseIndustry $industry): self
    {
        $this->industry = $industry;

        return $this;
    }

    public function published(string $at = '2026-01-15 12:00:00'): self
    {
        $this->status = CaseStatus::PUBLISHED;
        $this->publishedAt = new \DateTimeImmutable($at);

        return $this;
    }

    public function archived(): self
    {
        $this->status = CaseStatus::ARCHIVED;

        return $this;
    }

    public function featured(): self
    {
        $this->featured = true;

        return $this;
    }

    public function build(): ClientCase
    {
        $case = new ClientCase($this->slug, $this->industry, $this->title, 'Была проблема.', '1 000 ₽', 'результат', ['ДДС'], $this->createdAt);

        if ($this->featured) {
            $case->markAsFeatured('Задача.', ['Шаг'], [['value' => '1', 'label' => 'метрика']], 'Демо-данные', $this->createdAt);
        }
        if (null !== $this->publishedAt) {
            $case->publish($this->publishedAt);
        }
        if (CaseStatus::ARCHIVED === $this->status) {
            $case->archive($this->createdAt);
        }

        return $case;
    }
}
