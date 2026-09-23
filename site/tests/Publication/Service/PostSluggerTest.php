<?php

declare(strict_types=1);

namespace App\Tests\Publication\Service;

use App\Publication\Service\PostSlugger;
use App\Publication\ValueObject\PostSlug;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PostSluggerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function titles(): iterable
    {
        yield 'кириллица' => ['Как считать unit-экономику', 'kak-schitat-unit-ekonomiku'];
        yield 'ё, й, щ, ъ, ь' => ['Ёжик, йогурт и щедрость: подъезд, соль', 'ezhik-yogurt-i-shchedrost-podezd-sol'];
        yield 'регистр и цифры' => ['ОПиУ 2026 для Ozon', 'opiu-2026-dlya-ozon'];
        yield 'пунктуация по краям' => ['  «Маркетплейс» — или сайт?!  ', 'marketpleys-ili-sayt'];
        yield 'только символы' => ['?!… — ©', ''];
    }

    #[DataProvider('titles')]
    public function testSlugify(string $title, string $expected): void
    {
        self::assertSame($expected, new PostSlugger()->slugify($title));
    }

    public function testSuffixFitsIntoColumnForLongSlug(): void
    {
        $slugger = new PostSlugger();
        $slug = $slugger->withSuffix($slugger->slugify(str_repeat('щука ', 60)), 12);

        self::assertSame(PostSlug::MAX_LENGTH, \strlen($slug));
        self::assertStringEndsWith('-12', $slug);
        self::assertTrue(PostSlug::isValid($slug));
    }

    public function testLongTitleIsCutWithoutTrailingDash(): void
    {
        $slug = new PostSlugger()->slugify(str_repeat('слово ', 60));

        self::assertLessThanOrEqual(150, \strlen($slug));
        self::assertTrue(PostSlug::isValid($slug));
    }
}
