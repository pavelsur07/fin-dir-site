<?php

declare(strict_types=1);

namespace App\Publication\Query\AdminPostList;

use App\Publication\ValueObject\PostStatus;

final readonly class AdminPostListItem
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public PostStatus $status,
        public \DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $publishedAt,
    ) {
    }
}
