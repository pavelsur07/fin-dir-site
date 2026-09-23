<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Политика описывает то, что сайт делает на самом деле: Метрику с Вебвизором
 * на всех страницах и хранение источника переходов в браузере.
 */
final class PrivacyPolicyTest extends WebTestCase
{
    public function testPolicyDescribesCookiesAnalyticsAndAttribution(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/privacy');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('nav a[href="#privacy-cookies"]');
        $section = $crawler->filter('#privacy-cookies')->text();
        foreach (['Яндекс Метрика', 'ООО «ЯНДЕКС»', 'Вебвизор', '_ym_uid', 'vf_attr', 'vf_cookie_notice_accepted', 'ClientID', '90 дней'] as $fragment) {
            self::assertStringContainsString($fragment, $section);
        }
        // Ссылки на несуществующий пункт заменены на раздел 3.1.
        self::assertStringNotContainsString('пункте 3.3', $crawler->filter('main')->text());
        self::assertSelectorTextContains('[data-vf-section="privacy-hero"]', 'v1.3');
    }
}
