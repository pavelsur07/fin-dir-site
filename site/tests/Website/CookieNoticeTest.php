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

    /**
     * Регрессия: после ухода с Bootstrap стиль класса is-visible пропал, и баннер
     * навсегда оставался opacity-0. Видимость держится на data-visible, который
     * ставит navigation.js, -- правила должны быть и в разметке, и в собранном CSS.
     */
    public function testNoticeBecomesVisibleThroughDataVisibleVariant(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorExists('#cookieNotice[hidden].opacity-0.data-visible\\:opacity-100.data-visible\\:translate-y-0');

        // Крестик позиционируется внутри карточки, а не от fixed-обёртки на всю ширину экрана.
        self::assertSelectorExists('#cookieNotice > div.relative #cookieClose.absolute');
        // Touch target крестика -- не меньше 44px (SITE_RULES, --vf-control-min-height).
        self::assertSelectorExists('#cookieClose.size-control');

        $root = (string) self::getContainer()->getParameter('kernel.project_dir');
        $css = (string) file_get_contents($root.'/public/assets/website/app.css');
        self::assertStringContainsString('.data-visible\\:opacity-100[data-visible]', $css);
        self::assertStringContainsString('.data-visible\\:translate-y-0[data-visible]', $css);

        $script = (string) file_get_contents($root.'/assets/scripts/website/navigation.js');
        self::assertStringContainsString('cookieNotice.dataset.visible', $script);
        self::assertStringNotContainsString('is-visible', $script);
    }
}
