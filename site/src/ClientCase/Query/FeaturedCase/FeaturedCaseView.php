<?php

declare(strict_types=1);

namespace App\ClientCase\Query\FeaturedCase;

use App\ClientCase\ValueObject\CaseIndustry;

final readonly class FeaturedCaseView
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
        public string $task,
        public array $tags,
        public array $steps,
        public array $metrics,
        public ?string $source,
    ) {
    }
}
