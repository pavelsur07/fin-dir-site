<?php

declare(strict_types=1);

namespace App\Publication\Service;

use App\Publication\ValueObject\PostSlug;

/**
 * Slug из заголовка. Своя таблица вместо AsciiSlugger: в образах нет ext-intl,
 * а без него AsciiSlugger кириллицу не транслитерирует.
 */
final class PostSlugger
{
    private const array CYRILLIC = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
        'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    /**
     * @return string пустая строка, если в заголовке нет ни одной буквы или цифры
     */
    public function slugify(string $title, int $maxLength = PostSlug::MAX_LENGTH): string
    {
        $slug = strtr(mb_strtolower($title), self::CYRILLIC);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

        return self::truncate($slug, $maxLength);
    }

    /**
     * Slug с числовым суффиксом, который целиком влезает в колонку: обрезается основа.
     */
    public function withSuffix(string $slug, int $suffix): string
    {
        $tail = '-'.$suffix;

        return self::truncate($slug, PostSlug::MAX_LENGTH - \strlen($tail)).$tail;
    }

    private static function truncate(string $slug, int $maxLength): string
    {
        return \strlen($slug) > $maxLength ? rtrim(substr($slug, 0, $maxLength), '-') : $slug;
    }
}
