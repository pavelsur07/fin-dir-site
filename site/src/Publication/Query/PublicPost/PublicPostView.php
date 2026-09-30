<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPost;

/**
 * Опубликованная статья для страницы сайта. Текст -- исходный Markdown.
 */
final readonly class PublicPostView
{
    private const int WORDS_PER_MINUTE = 200;

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

    /**
     * «Обновлено» показываем, только если правка была в другой календарный день, чем публикация.
     */
    public function wasUpdated(): bool
    {
        return $this->updatedAt->format('Y-m-d') !== $this->publishedAt->format('Y-m-d');
    }

    /**
     * Время чтения: слова Markdown-текста / 200, вверх, не меньше минуты.
     */
    public function readingMinutes(): int
    {
        $words = preg_match_all('/[\p{L}\p{N}]+/u', $this->body);

        return max(1, (int) ceil(($words ?: 0) / self::WORDS_PER_MINUTE));
    }
}
