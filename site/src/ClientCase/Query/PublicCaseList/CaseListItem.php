<?php

declare(strict_types=1);

namespace App\ClientCase\Query\PublicCaseList;

use App\ClientCase\ValueObject\CaseIndustry;

final readonly class CaseListItem
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $slug,
        public CaseIndustry $industry,
        public string $title,
        public string $problem,
        public string $resultValue,
        public string $resultLabel,
        public array $tags,
    ) {
    }
}
