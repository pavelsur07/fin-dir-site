<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPostList;

use App\Publication\ValueObject\PostRubric;

final readonly class PublicPostListItem
{
    /** Приблизительно: русские слова ~6 знаков + пробел, Markdown добавляет разметку. */
    private const int CHARS_PER_MINUTE = 1400;

    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $excerpt,
        public \DateTimeImmutable $publishedAt,
        public ?PostRubric $rubric = null,
        private int $bodyLength = 0,
    ) {
    }

    /**
     * Оценка по длине текста: тело статьи в список не загружается.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->bodyLength / self::CHARS_PER_MINUTE));
    }
}
