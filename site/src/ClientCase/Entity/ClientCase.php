<?php

declare(strict_types=1);

namespace App\ClientCase\Entity;

use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'client_case')]
#[ORM\UniqueConstraint(name: 'client_case_slug_uniq', columns: ['slug'])]
#[ORM\Index(name: 'client_case_status_idx', columns: ['status'])]
#[ORM\Index(name: 'client_case_industry_idx', columns: ['industry'])]
class ClientCase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $slug;

    #[ORM\Column(length: 32, enumType: CaseIndustry::class)]
    private CaseIndustry $industry;

    #[ORM\Column(length: 200)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $problem;

    /** Ключевой результат: число и подпись, например «3 200 000 ₽» и «высвобождено из остатков». */
    #[ORM\Column(length: 60)]
    private string $resultValue;

    #[ORM\Column(length: 160)]
    private string $resultLabel;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $tags;

    /** Расширенное описание главного кейса: задача, шаги, метрики и источник данных. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $task = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $steps = [];

    /** @var list<array{value: string, label: string}> */
    #[ORM\Column(type: 'json')]
    private array $metrics = [];

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $source = null;

    #[ORM\Column]
    private bool $featured = false;

    #[ORM\Column(length: 16, enumType: CaseStatus::class)]
    private CaseStatus $status = CaseStatus::DRAFT;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** Дата первой публикации. При снятии с публикации сохраняется. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    /**
     * @param list<string> $tags
     */
    public function __construct(
        string $slug,
        CaseIndustry $industry,
        string $title,
        string $problem,
        string $resultValue,
        string $resultLabel,
        array $tags,
        \DateTimeImmutable $now,
    ) {
        foreach (['slug' => $slug, 'title' => $title, 'problem' => $problem, 'resultValue' => $resultValue, 'resultLabel' => $resultLabel] as $field => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException(\sprintf('Case %s must not be empty.', $field));
            }
        }

        $this->slug = $slug;
        $this->industry = $industry;
        $this->title = $title;
        $this->problem = $problem;
        $this->resultValue = $resultValue;
        $this->resultLabel = $resultLabel;
        $this->tags = $tags;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Делает кейс главным: у него обязательны задача, шаги и метрики. Главным может быть только один кейс --
     * это правило сценария (снять признак с прежнего), а не Entity.
     *
     * @param list<string>                              $steps
     * @param list<array{value: string, label: string}> $metrics
     */
    public function markAsFeatured(string $task, array $steps, array $metrics, ?string $source, \DateTimeImmutable $now): void
    {
        if ('' === trim($task) || [] === $steps || [] === $metrics) {
            throw new \InvalidArgumentException('Featured case requires task, steps and metrics.');
        }

        $this->task = $task;
        $this->steps = $steps;
        $this->metrics = $metrics;
        $this->source = $source;
        $this->featured = true;
        $this->updatedAt = $now;
    }

    public function publish(\DateTimeImmutable $now): void
    {
        $this->status = CaseStatus::PUBLISHED;
        $this->publishedAt ??= $now;
        $this->updatedAt = $now;
    }

    public function archive(\DateTimeImmutable $now): void
    {
        $this->status = CaseStatus::ARCHIVED;
        $this->updatedAt = $now;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function status(): CaseStatus
    {
        return $this->status;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
