<?php

declare(strict_types=1);

namespace App\Lead\Query\AdminLeadList;

use App\Lead\ValueObject\LeadStatus;

final readonly class AdminLeadListCriteria
{
    public const int PER_PAGE = 20;

    public function __construct(
        public ?LeadStatus $status = null,
        public ?string $form = null,
        public ?string $search = null,
        public int $page = 1,
    ) {
    }
}
