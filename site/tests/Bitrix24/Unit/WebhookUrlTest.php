<?php

declare(strict_types=1);

namespace App\Tests\Bitrix24\Unit;

use App\Bitrix24\Exception\InvalidWebhookUrl;
use App\Bitrix24\ValueObject\WebhookUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebhookUrlTest extends TestCase
{
    public function testParsesCloudWebhookAndMasksToken(): void
    {
        $webhook = WebhookUrl::fromString('  HTTPS://VashFinDir.bitrix24.ru/rest/12/abc123def456ghij  ');

        self::assertSame('vashfindir.bitrix24.ru', $webhook->portal);
        self::assertSame('https://vashfindir.bitrix24.ru/rest/12/abc123def456ghij/', $webhook->reveal());
        self::assertSame('vashfindir.bitrix24.ru/rest/12/…ghij/', $webhook->mask());
        self::assertStringNotContainsString('abc123def456', $webhook->mask());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'http' => ['http://a.bitrix24.ru/rest/1/abcdefgh12/'];
        yield 'чужой домен' => ['https://example.com/rest/1/abcdefgh12/'];
        yield 'похожий домен' => ['https://a.bitrix24.ru.evil.com/rest/1/abcdefgh12/'];
        yield 'чужая зона' => ['https://a.bitrix24.xyz/rest/1/abcdefgh12/'];
        yield 'коробка' => ['https://crm.company.ru/rest/1/abcdefgh12/'];
        yield 'логин в адресе' => ['https://user@a.bitrix24.ru/rest/1/abcdefgh12/'];
        yield 'порт' => ['https://a.bitrix24.ru:8443/rest/1/abcdefgh12/'];
        yield 'query' => ['https://a.bitrix24.ru/rest/1/abcdefgh12/?x=1'];
        yield 'метод в пути' => ['https://a.bitrix24.ru/rest/1/abcdefgh12/crm.lead.add'];
        yield 'короткий токен' => ['https://a.bitrix24.ru/rest/1/abc/'];
        yield 'пусто' => [''];
    }

    #[DataProvider('invalid')]
    public function testRejectsAnythingButCloudWebhook(string $raw): void
    {
        $this->expectException(InvalidWebhookUrl::class);

        WebhookUrl::fromString($raw);
    }
}
