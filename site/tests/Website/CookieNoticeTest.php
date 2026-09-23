<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CookieNoticeTest extends WebTestCase
{
    public function testCookieNoticeLinksToCookieSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorExists('#cookieNotice a[href="/privacy#privacy-cookies"]');
        self::assertSelectorTextContains('#cookieNotice', 'Вебвизор');
    }
}
