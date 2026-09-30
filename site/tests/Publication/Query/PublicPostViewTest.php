<?php

declare(strict_types=1);

namespace App\Tests\Publication\Query;

use App\Publication\Query\PublicPost\PublicPostView;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublicPostViewTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function readingTimeCases(): iterable
    {
        yield 'empty text gives one minute' => [0, 1];
        yield 'short text gives one minute' => [50, 1];
        yield 'exactly 200 words' => [200, 1];
        yield '201 words round up' => [201, 2];
        yield '1000 words' => [1000, 5];
    }

    #[DataProvider('readingTimeCases')]
    public function testReadingMinutes(int $words, int $expected): void
    {
        $body = trim(str_repeat('слово ', $words));

        self::assertSame($expected, $this->view($body)->readingMinutes());
    }

    public function testMarkdownSyntaxIsNotCountedAsWords(): void
    {
        $body = str_repeat("## \n- **слово**\n", 200);

        self::assertSame(1, $this->view($body)->readingMinutes());
    }

    public function testWasUpdatedComparesCalendarDays(): void
    {
        $published = new \DateTimeImmutable('2026-05-27 09:00:00', new \DateTimeZone('UTC'));

        self::assertFalse($this->view('т', $published, $published->modify('+3 hours'))->wasUpdated());
        self::assertTrue($this->view('т', $published, $published->modify('+1 day'))->wasUpdated());
    }

    private function view(string $body, ?\DateTimeImmutable $published = null, ?\DateTimeImmutable $updated = null): PublicPostView
    {
        $published ??= new \DateTimeImmutable('2026-05-27 09:00:00', new \DateTimeZone('UTC'));

        return new PublicPostView(1, 'test', 'Заголовок', null, $body, null, null, $published, $updated ?? $published);
    }
}
