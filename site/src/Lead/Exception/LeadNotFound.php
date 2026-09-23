<?php

declare(strict_types=1);

namespace App\Lead\Exception;

use App\Shared\Exception\NotFound;

final class LeadNotFound extends \DomainException implements NotFound
{
    public function __construct(public readonly int $leadId)
    {
        parent::__construct(\sprintf('Lead %d not found.', $leadId));
    }
}
