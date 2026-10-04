<?php

declare(strict_types=1);

namespace App\ClientCase\Query\CaseDetail;

use App\ClientCase\ValueObject\CaseIndustry;

final readonly class CaseDetailView
{
    /**
     * @param list<string>                              $tags
     * @param list<string>                              $steps
     * @param list<array{value: string, label: string}> $metrics
     */
    public function __construct(
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
    ) {
    }
}
