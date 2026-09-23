<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Правила для адресов, пришедших из браузера: свой путь и origin чужого сайта.
 */
final class WebAddress
{
    /** Локальный путь: не "//host" и не "/\\host" (браузер читает его как "//host"), без пробелов. */
    public const string LOCAL_PATH = '#^/(?![/\\\\])[^\s\x00-\x1f]*$#';

    /** Только origin: путь и query чужого сайта могут содержать ПД. */
    public static function origin(?string $url): ?string
    {
        $parts = null === $url || '' === $url ? false : parse_url($url);
        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    public static function isLocalPath(string $path): bool
    {
        return 1 === preg_match(self::LOCAL_PATH, $path);
    }
}
