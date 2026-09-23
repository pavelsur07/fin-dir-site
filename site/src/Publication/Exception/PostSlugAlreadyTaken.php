<?php

declare(strict_types=1);

namespace App\Publication\Exception;

use App\Shared\Exception\Conflict;

final class PostSlugAlreadyTaken extends \DomainException implements Conflict
{
    public function __construct(public readonly string $slug, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Slug "%s" is already taken.', $slug), 0, $previous);
    }
}
