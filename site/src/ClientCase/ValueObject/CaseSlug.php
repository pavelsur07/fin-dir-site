<?php

declare(strict_types=1);

namespace App\ClientCase\ValueObject;

/**
 * Формат slug кейса: часть публичного URL /cases/{slug}.
 */
final class CaseSlug
{
    public const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Длина колонки client_case.slug. */
    public const int MAX_LENGTH = 120;

    public static function isValid(string $slug): bool
    {
        return \strlen($slug) <= self::MAX_LENGTH && 1 === preg_match(self::PATTERN, $slug);
    }
}
