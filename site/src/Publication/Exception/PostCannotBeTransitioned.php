<?php

declare(strict_types=1);

namespace App\Publication\Exception;

use App\Publication\ValueObject\PostStatus;
use App\Shared\Exception\Conflict;

final class PostCannotBeTransitioned extends \DomainException implements Conflict
{
    public function __construct(
        public readonly ?int $postId,
        public readonly PostStatus $from,
        public readonly PostStatus $to,
        string $reason = 'transition is not allowed',
    ) {
        parent::__construct(\sprintf('Post %s cannot go from %s to %s: %s.', $postId ?? 'new', $from->value, $to->value, $reason));
    }
}
