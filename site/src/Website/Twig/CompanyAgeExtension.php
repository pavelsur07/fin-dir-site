<?php

declare(strict_types=1);

namespace App\Website\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Сколько лет компания на рынке: считается от года основания по текущей дате, а не хардкодится в шаблоне.
 */
final class CompanyAgeExtension
{
    /**
     * @param int|null $currentYear текущий год; по умолчанию из системной даты (параметр нужен тестам)
     *
     * @return array{count: int, unit: string} например, {count: 11, unit: "лет"} или {count: 21, unit: "год"}
     */
    #[AsTwigFunction('years_since')]
    public function yearsSince(int $year, ?int $currentYear = null): array
    {
        $count = max(0, ($currentYear ?? (int) date('Y')) - $year);

        return ['count' => $count, 'unit' => self::unit($count)];
    }

    /**
     * Склонение слова «год»: 1 год, 2–4 года, 5–20 лет, 21 год, 22–24 года, 11–14 всегда «лет».
     */
    public static function unit(int $count): string
    {
        $lastTwo = $count % 100;
        $last = $count % 10;

        return match (true) {
            $lastTwo >= 11 && $lastTwo <= 14 => 'лет',
            1 === $last => 'год',
            $last >= 2 && $last <= 4 => 'года',
            default => 'лет',
        };
    }
}
