<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\Adapter\LeadNotification;
use PHPUnit\Framework\TestCase;

final class LeadNotificationTest extends TestCase
{
    public function testTextContainsContextButNoPersonalData(): void
    {
        $text = new LeadNotification(
            42,
            'Диагностика',
            '/gazeta/test',
            [['questionLabel' => 'Оборот в месяц', 'answerLabel' => '1–5 млн ₽']],
            ['utm_source' => 'yandex'],
        )->text('https://vashfindir.ru/admin/leads/42');

        self::assertStringContainsString('Новое обращение №42', $text);
        self::assertStringContainsString('Форма: Диагностика', $text);
        self::assertStringContainsString('Страница: /gazeta/test', $text);
        self::assertStringContainsString('Оборот в месяц 1–5 млн ₽', $text);
        self::assertStringContainsString('utm_source: yandex', $text);
        self::assertStringContainsString('href="https://vashfindir.ru/admin/leads/42"', $text);
    }

    public function testApostropheUsesEntityTelegramUnderstands(): void
    {
        $text = new LeadNotification(1, 'Форма', '/', [], ['utm_campaign' => "o'reilly"])->text('https://vashfindir.ru/admin/leads/1');

        // Telegram HTML не знает &apos; и отвергает всё сообщение.
        self::assertStringNotContainsString('&apos;', $text);
        self::assertStringContainsString('o&#039;reilly', $text);
    }

    public function testNewlinesCannotForgeNotificationLines(): void
    {
        $text = new LeadNotification(1, 'Форма', '/', [], ['utm_source' => "x\nФорма: Поддельная"])->text('https://vashfindir.ru/admin/leads/1');

        self::assertStringContainsString('utm_source: x Форма: Поддельная', $text);
        self::assertSame(1, substr_count($text, "\nФорма:"));
    }

    public function testUserControlledValuesAreEscapedForTelegramHtml(): void
    {
        $text = new LeadNotification(1, 'Форма', '/<b>x</b>', [], ['utm_source' => '<a href="evil">x</a>'])->text('https://vashfindir.ru/admin/leads/1');

        self::assertStringNotContainsString('<b>x</b>', $text);
        self::assertStringNotContainsString('<a href="evil">', $text);
    }
}
