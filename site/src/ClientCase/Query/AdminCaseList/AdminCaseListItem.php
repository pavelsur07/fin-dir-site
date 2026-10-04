<?php

declare(strict_types=1);

namespace App\ClientCase\Query\AdminCaseList;

use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseStatus;

final readonly class AdminCaseListItem
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public CaseIndustry $industry,
        public CaseStatus $status,
        public bool $featured,
        public \DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $publishedAt,
    ) {
    }
}
