<?php

declare(strict_types=1);

namespace App\Documentation\ValueObject;

final class DocFields
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

    /** @return list<'EMPTY_DESCRIPTION'|'EMPTY_BODY'> */
    public static function publicationContentIssues(string $description, string $body): array
    {
        $issues = [];
        if ('' === trim($description)) {
            $issues[] = 'EMPTY_DESCRIPTION';
        }
        if ('' === trim($body)) {
            $issues[] = 'EMPTY_BODY';
        }

        return $issues;
    }

    public static function text(string $text, int $max, bool $required = true): string
    {
        $text = trim($text);
        if (($required && '' === $text) || mb_strlen($text) > $max) {
            throw new \InvalidArgumentException('Проверьте заполнение и длину полей.');
        }

        return $text;
    }

    public static function slug(string $slug, int $max): string
    {
        if (!preg_match(self::SLUG_PATTERN, $slug) || strlen($slug) > $max) {
            throw new \InvalidArgumentException('Адрес: латиница в нижнем регистре, цифры и дефисы.');
        }

        return $slug;
    }

    public static function position(int $position): int
    {
        if ($position < 0 || $position > 100000) {
            throw new \InvalidArgumentException('Порядок должен быть от 0 до 100000.');
        }

        return $position;
    }
}
