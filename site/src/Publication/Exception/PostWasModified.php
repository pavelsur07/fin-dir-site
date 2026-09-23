<?php

declare(strict_types=1);

namespace App\Publication\Exception;

use App\Shared\Exception\Conflict;

/**
 * Статью сохранили в другой вкладке/сессии после того, как её открыли на редактирование.
 */
final class PostWasModified extends \DomainException implements Conflict
{
    public function __construct(public readonly int $postId, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Post %d was modified concurrently.', $postId), 0, $previous);
    }
}
