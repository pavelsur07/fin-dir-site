<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\Adapter\LeadNotification;
use PHPUnit\Framework\TestCase;

final class LeadNotificationTest extends TestCase
{
    public function testTextIsOnlyNumberAndAdminLink(): void
    {
        $text = new LeadNotification(42)->text('https://vashfindir.ru/admin/leads/42');

        self::assertSame("<b>Новое обращение №42</b>\n\n<a href=\"https://vashfindir.ru/admin/leads/42\">Открыть в админке</a>", $text);
    }

    public function testAdminUrlIsEscapedForTelegramHtml(): void
    {
        $text = new LeadNotification(1)->text("https://vashfindir.ru/admin/leads/1?a=1&b='x\"");

        // Telegram HTML не знает &apos; и отвергает всё сообщение.
        self::assertStringNotContainsString('&apos;', $text);
        self::assertStringContainsString('href="https://vashfindir.ru/admin/leads/1?a=1&amp;b=&#039;x&quot;"', $text);
    }
}
