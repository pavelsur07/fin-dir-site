<?php

declare(strict_types=1);

namespace App\Publication\Exception;

use App\Shared\Exception\NotFound;

final class PostNotFound extends \DomainException implements NotFound
{
    /**
     * @param int|string $reference id (админка) или slug (публичный сайт)
     */
    public function __construct(public readonly int|string $reference)
    {
        parent::__construct(\sprintf('Post "%s" not found.', $reference));
    }
}
