<?php

declare(strict_types=1);

namespace App\ClientCase\Exception;

use App\Shared\Exception\Conflict;

final class CaseSlugAlreadyTaken extends \DomainException implements Conflict
{
    public function __construct(public readonly string $slug, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Case slug "%s" is already taken.', $slug), 0, $previous);
    }
}
