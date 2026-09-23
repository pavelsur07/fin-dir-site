<?php

declare(strict_types=1);

namespace App\Publication\Exception;

use App\Shared\Exception\Conflict;

/**
 * Slug однажды опубликованной статьи не меняется: её URL мог попасть в индекс.
 */
final class PostSlugIsLocked extends \DomainException implements Conflict
{
    public function __construct(public readonly ?int $postId)
    {
        parent::__construct(\sprintf('Slug of post %s is locked after first publication.', $postId ?? 'new'));
    }
}
