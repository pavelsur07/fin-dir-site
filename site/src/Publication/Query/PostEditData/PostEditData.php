<?php

declare(strict_types=1);

namespace App\Publication\Query\PostEditData;

use App\Publication\ValueObject\PostStatus;

/**
 * Данные одной статьи для формы редактирования и предпросмотра.
 */
final readonly class PostEditData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $body,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public PostStatus $status,
        public ?\DateTimeImmutable $publishedAt,
        public int $version,
    ) {
    }

    /** Slug однажды опубликованной статьи не меняется. */
    public function isSlugLocked(): bool
    {
        return null !== $this->publishedAt;
    }
}
