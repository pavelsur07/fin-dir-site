<?php

declare(strict_types=1);

namespace App\Lead\Query\AdminLeadList;

use App\Lead\ValueObject\LeadStatus;

final readonly class AdminLeadListItem
{
    public function __construct(
        public int $id,
        public \DateTimeImmutable $createdAt,
        public string $formKey,
        public string $name,
        public string $contact,
        public LeadStatus $status,
        public ?\DateTimeImmutable $nextContactAt,
        public ?\DateTimeImmutable $notifiedAt,
        public int $version,
    ) {
    }
}
