<?php

declare(strict_types=1);

namespace App\ClientCase\Exception;

use App\Shared\Exception\NotFound;

final class CaseNotFound extends \DomainException implements NotFound
{
    public function __construct(public readonly int $id)
    {
        parent::__construct(\sprintf('Case "%d" not found.', $id));
    }
}
