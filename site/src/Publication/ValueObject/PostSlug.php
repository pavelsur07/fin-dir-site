<?php

declare(strict_types=1);

namespace App\Publication\ValueObject;

/**
 * Формат slug статьи: часть публичного URL /gazeta/{slug}.
 */
final class PostSlug
{
    public const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Длина колонки publication_post.slug. */
    public const int MAX_LENGTH = 150;

    public static function isValid(string $slug): bool
    {
        return \strlen($slug) <= self::MAX_LENGTH && 1 === preg_match(self::PATTERN, $slug);
    }
}
