<?php

declare(strict_types=1);

namespace App\Lead\Exception;

use App\Shared\Exception\Conflict;

final class LeadWasModified extends \DomainException implements Conflict
{
    public function __construct(public readonly int $leadId, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Lead %d was modified concurrently.', $leadId), 0, $previous);
    }
}
