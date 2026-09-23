<?php

declare(strict_types=1);

namespace App\Publication\Exception;

use App\Shared\Exception\NotFound;

final class PostNotFound extends \DomainException implements NotFound
{
    public function __construct(public readonly int $postId)
    {
        parent::__construct(\sprintf('Post %d not found.', $postId));
    }
}
