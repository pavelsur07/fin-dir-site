<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Контакт в каноническом виде: по нему ищут повторные обращения.
 */
final readonly class NormalizedContact
{
    public function __construct(
        public string $value,
        public ContactType $type,
    ) {
    }

    public static function fromRaw(string $raw): self
    {
        $raw = trim($raw);

        if (1 === preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/u', $raw)) {
            return new self(mb_strtolower($raw), ContactType::EMAIL);
        }

        if (1 === preg_match('/^@?([a-z0-9_]{5,32})$/i', $raw, $match) && str_starts_with($raw, '@')) {
            return new self('@'.mb_strtolower($match[1]), ContactType::TELEGRAM);
        }

        $digits = (string) preg_replace('/\D+/', '', $raw);
        if (1 === preg_match('/^[\d\s()+\-]+$/', $raw)) {
            $international = str_starts_with(ltrim($raw), '+');
            if (11 === \strlen($digits) && ('7' === $digits[0] || (!$international && '8' === $digits[0]))) {
                return new self('+7'.substr($digits, 1), ContactType::PHONE);
            }
            if (10 === \strlen($digits) && !$international) {
                return new self('+7'.$digits, ContactType::PHONE);
            }
            if (\strlen($digits) >= 7) {
                return new self('+'.$digits, ContactType::PHONE);
            }
        }

        return new self(mb_strtolower($raw), ContactType::OTHER);
    }
}
