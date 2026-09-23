<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPost;

/**
 * Опубликованная статья для страницы сайта. Текст -- исходный Markdown.
 */
final readonly class PublicPostView
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $excerpt,
        public string $body,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public \DateTimeImmutable $publishedAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }

    public function seoTitle(): string
    {
        return $this->metaTitle ?? $this->title;
    }

    public function seoDescription(): ?string
    {
        return $this->metaDescription ?? $this->excerpt;
    }
}
