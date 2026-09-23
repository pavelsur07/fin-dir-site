<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPostList;

final readonly class PublicPostListItem
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $excerpt,
        public \DateTimeImmutable $publishedAt,
    ) {
    }
}
