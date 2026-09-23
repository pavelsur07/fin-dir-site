<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\ValueObject\LeadAttribution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LeadAttributionTest extends TestCase
{
    private const int NOW = 1790000000;

    public function testValidAttributionIsKept(): void
    {
        $attribution = $this->parse([
            'v' => 1,
            'first' => [
                'ts' => self::NOW - 86400 * 3,
                'channel' => 'cpc',
                'source' => 'yandex',
                'medium' => 'cpc',
                'campaign' => 'brand',
                'landing' => '/services',
                'referrer' => 'https://yandex.ru',
                'click' => ['yclid' => '42'],
            ],
            'last' => ['ts' => self::NOW - 60, 'channel' => 'organic', 'referrer' => 'https://www.google.com'],
            'visits' => 3,
        ]);

        self::assertNotNull($attribution);
        self::assertSame([
            'first' => [
                'ts' => self::NOW - 86400 * 3,
                'channel' => 'cpc',
                'source' => 'yandex',
                'medium' => 'cpc',
                'campaign' => 'brand',
                'landing' => '/services',
                'referrer' => 'https://yandex.ru',
                'click' => ['yclid' => '42'],
            ],
            'last' => ['ts' => self::NOW - 60, 'channel' => 'organic', 'referrer' => 'https://www.google.com'],
            'visits' => 3,
        ], $attribution->toArray());
        self::assertSame(self::NOW - 86400 * 3, $attribution->firstVisitAt()?->getTimestamp());
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function unusableJson(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'broken' => ['{"first":'];
        yield 'scalar' => ['42'];
        yield 'no first' => ['{"last":{"channel":"direct"}}'];
        yield 'first without known keys' => ['{"first":{"foo":"bar"}}'];
        yield 'too big' => ['{"first":{"source":"'.str_repeat('a', 5000).'"}}'];
    }

    #[DataProvider('unusableJson')]
    public function testUnusableJsonGivesNull(?string $json): void
    {
        self::assertNull(LeadAttribution::fromJson($json, $this->now()));
    }

    public function testUnknownKeysAreDroppedAndValuesTruncated(): void
    {
        $attribution = $this->parse(['first' => [
            'source' => str_repeat('я', 300),
            'email' => 'secret@example.com',
            'click' => ['yclid' => str_repeat('1', 150), 'fbclid' => 'x'],
        ]]);

        self::assertNotNull($attribution);
        self::assertSame(['source', 'click'], array_keys($attribution->first));
        self::assertSame(200, mb_strlen($attribution->first['source'] ?? ''));
        self::assertSame(['yclid' => str_repeat('1', 100)], $attribution->first['click'] ?? null);
    }

    public function testForeignLandingIsDroppedAndReferrerReducedToOrigin(): void
    {
        $attribution = $this->parse(['first' => [
            'landing' => '//evil.example/phish',
            'referrer' => 'https://yandex.ru/search/?text=финдир&uid=secret',
            'channel' => 'organic',
        ]]);

        self::assertSame(['channel' => 'organic', 'referrer' => 'https://yandex.ru'], $attribution?->first);
    }

    public function testChannelOutsideFormatBecomesUnknown(): void
    {
        self::assertSame('unknown', $this->parse(['first' => ['channel' => '<script>']])?->first['channel'] ?? null);
    }

    public function testTimestampsInFutureOrTooOldAreDropped(): void
    {
        $attribution = $this->parse([
            'first' => ['ts' => self::NOW - 401 * 86400, 'channel' => 'direct'],
            'last' => ['ts' => self::NOW + 2 * 86400, 'channel' => 'cpc'],
        ]);

        self::assertSame(['channel' => 'direct'], $attribution?->first);
        self::assertSame(['channel' => 'cpc'], $attribution->last);
        self::assertNull($attribution->firstVisitAt());
    }

    public function testVisitsOutsideRangeAreDropped(): void
    {
        self::assertNull($this->parse(['first' => ['channel' => 'direct'], 'visits' => 0])?->visits);
        self::assertNull($this->parse(['first' => ['channel' => 'direct'], 'visits' => 10001])?->visits);
        self::assertNull($this->parse(['first' => ['channel' => 'direct'], 'visits' => '5'])?->visits);
    }

    public function testStoredArrayRoundTrips(): void
    {
        $attribution = $this->parse(['first' => ['channel' => 'direct'], 'visits' => 2]);
        self::assertNotNull($attribution);

        self::assertEquals($attribution, LeadAttribution::fromStored($attribution->toArray()));
        self::assertNull(LeadAttribution::fromStored([]));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parse(array $data): ?LeadAttribution
    {
        return LeadAttribution::fromJson(json_encode($data, \JSON_THROW_ON_ERROR), $this->now());
    }

    private function now(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp(self::NOW);
    }
}
