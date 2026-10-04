<?php

declare(strict_types=1);

namespace App\ClientCase\Query\CaseEditData;

use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseStatus;

final readonly class CaseEditData
{
    /**
     * @param list<string>                              $tags
     * @param list<string>                              $steps
     * @param list<array{value: string, label: string}> $metrics
     */
    public function __construct(
        public int $id,
        public string $slug,
        public CaseIndustry $industry,
        public string $title,
        public string $problem,
        public string $resultValue,
        public string $resultLabel,
        public array $tags,
        public ?string $task,
        public array $steps,
        public array $metrics,
        public ?string $source,
        public bool $featured,
        public CaseStatus $status,
        public ?\DateTimeImmutable $publishedAt,
    ) {
    }

    /** После первой публикации адрес не меняется. */
    public function isSlugLocked(): bool
    {
        return null !== $this->publishedAt;
    }
}
