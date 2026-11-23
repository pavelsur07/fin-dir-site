<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Политика описывает то, что сайт делает на самом деле: Метрика с Вебвизором и чат --
 * только после «Принять», источник переходов в браузере, хранение данных в России.
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
        foreach (['Яндекс Метрика', 'ООО «ЯНДЕКС»', 'Вебвизор', '_ym_uid', 'vf_attr', 'vf_cookie_choice', 'ClientID', '90 дней', 'Только необходимые', 'Настройки cookie', 'Битрикс24'] as $fragment) {
            self::assertStringContainsString($fragment, $section);
        }
        // Ссылки на несуществующий пункт заменены на раздел 3.1.
        self::assertStringNotContainsString('пункте 3.3', $crawler->filter('main')->text());
        self::assertSelectorTextContains('[data-vf-section="privacy-hero"]', 'v1.6');
        // Политика не противоречит согласию: срок 3 года, рассылки -- только по отдельному согласию.
        $main = $crawler->filter('main')->text();
        self::assertStringNotContainsString('без ограничения срока', $main);
        self::assertStringNotContainsString('Предоставляя свои данные, Пользователь подтверждает согласие на получение', $main);
        self::assertStringNotContainsString('отчество', $main);
        // Согласие не выводится из факта посещения сайта (ч. 1 ст. 9 152-ФЗ).
        self::assertStringNotContainsString('безоговорочного согласия', $main);
        self::assertStringNotContainsString('посредством открытия', $main);
        self::assertStringContainsString('Открытие и просмотр Сайта не считаются согласием', $main);
        // Получатели данных названы, хранение -- в России, за рубеж не передаётся.
        foreach (['ООО «ТАЙМВЭБ.КЛАУД»', 'ООО «1С-Битрикс»', 'в Москве', 'не передаёт персональные данные на территорию иностранных государств'] as $fragment) {
            self::assertStringContainsString($fragment, $main);
        }
        // Ссылки только на существующие разделы содержания.
        foreach ($crawler->filter('nav[aria-labelledby="privacy-toc-title"] a')->extract(['href']) as $href) {
            self::assertCount(1, $crawler->filter($href), $href);
        }
    }
}
