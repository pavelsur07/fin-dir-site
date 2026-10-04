<?php

declare(strict_types=1);

namespace App\ClientCase\Entity;

use App\ClientCase\Exception\CaseCannotBeTransitioned;
use App\ClientCase\Exception\CaseSlugIsLocked;
use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseSlug;
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
        self::requireFilled(['title' => $title, 'problem' => $problem, 'resultValue' => $resultValue, 'resultLabel' => $resultLabel]);
        self::requireSlug($slug);

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
     * Основное содержание карточки. Вызывается и при создании, и при правке.
     *
     * @param list<string> $tags
     */
    public function edit(
        CaseIndustry $industry,
        string $title,
        string $problem,
        string $resultValue,
        string $resultLabel,
        array $tags,
        \DateTimeImmutable $now,
    ): void {
        self::requireFilled(['title' => $title, 'problem' => $problem, 'resultValue' => $resultValue, 'resultLabel' => $resultLabel]);

        $this->industry = $industry;
        $this->title = $title;
        $this->problem = $problem;
        $this->resultValue = $resultValue;
        $this->resultLabel = $resultLabel;
        $this->tags = $tags;
        $this->updatedAt = $now;
    }

    /**
     * Адрес меняется только до первой публикации: после неё он уже мог попасть в поиск и ссылки.
     */
    public function changeSlug(string $slug, \DateTimeImmutable $now): void
    {
        if ($slug === $this->slug) {
            return;
        }

        if (null !== $this->publishedAt) {
            throw new CaseSlugIsLocked($this->id);
        }

        self::requireSlug($slug);
        $this->slug = $slug;
        $this->updatedAt = $now;
    }

    /**
     * Расширенное описание: задача, шаги, метрики и источник данных. У главного кейса они обязательны.
     *
     * @param list<string>                              $steps
     * @param list<array{value: string, label: string}> $metrics
     */
    public function describe(?string $task, array $steps, array $metrics, ?string $source, \DateTimeImmutable $now): void
    {
        if ($this->featured && (null === $task || '' === trim($task) || [] === $steps || [] === $metrics)) {
            throw new \InvalidArgumentException('Featured case requires task, steps and metrics.');
        }

        $this->task = $task;
        $this->steps = $steps;
        $this->metrics = $metrics;
        $this->source = $source;
        $this->updatedAt = $now;
    }

    /**
     * Делает кейс главным: у него обязательны задача, шаги и метрики. Что главным остаётся только один кейс --
     * правило сценария (снять признак с прежнего), а не Entity.
     */
    public function markAsFeatured(\DateTimeImmutable $now): void
    {
        if (null === $this->task || '' === trim($this->task) || [] === $this->steps || [] === $this->metrics) {
            throw new \InvalidArgumentException('Featured case requires task, steps and metrics.');
        }

        $this->featured = true;
        $this->updatedAt = $now;
    }

    public function unmarkAsFeatured(\DateTimeImmutable $now): void
    {
        if (!$this->featured) {
            return;
        }

        $this->featured = false;
        $this->updatedAt = $now;
    }

    public function publish(\DateTimeImmutable $now): void
    {
        $this->transitionTo(CaseStatus::PUBLISHED, $now);
        $this->publishedAt ??= $now;
    }

    public function unpublish(\DateTimeImmutable $now): void
    {
        // ARCHIVED → DRAFT разрешён, но это restore(), а не снятие с публикации.
        if (CaseStatus::ARCHIVED === $this->status) {
            throw new CaseCannotBeTransitioned($this->id, $this->status, CaseStatus::DRAFT, 'use restore');
        }

        $this->transitionTo(CaseStatus::DRAFT, $now);
    }

    public function archive(\DateTimeImmutable $now): void
    {
        $this->transitionTo(CaseStatus::ARCHIVED, $now);
    }

    /** Из архива кейс возвращается черновиком: публиковать его снова -- отдельное решение. */
    public function restore(\DateTimeImmutable $now): void
    {
        if (CaseStatus::PUBLISHED === $this->status) {
            throw new CaseCannotBeTransitioned($this->id, $this->status, CaseStatus::DRAFT, 'use unpublish');
        }

        $this->transitionTo(CaseStatus::DRAFT, $now);
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

    /**
     * Повтор перехода в текущий статус -- no-op: повторный POST ничего не ломает.
     */
    private function transitionTo(CaseStatus $target, \DateTimeImmutable $now): void
    {
        if ($target === $this->status) {
            return;
        }

        if (!$this->status->canTransitionTo($target)) {
            throw new CaseCannotBeTransitioned($this->id, $this->status, $target);
        }

        $this->status = $target;
        $this->updatedAt = $now;
    }

    /**
     * @param array<string, string> $fields
     */
    private static function requireFilled(array $fields): void
    {
        foreach ($fields as $field => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException(\sprintf('Case %s must not be empty.', $field));
            }
        }
    }

    private static function requireSlug(string $slug): void
    {
        if (!CaseSlug::isValid($slug)) {
            throw new \InvalidArgumentException(\sprintf('Invalid case slug "%s".', $slug));
        }
    }
}
