<?php

declare(strict_types=1);

namespace App\Tests\Website;

use App\Lead\ValueObject\LeadConsent;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Согласие -- отдельный документ по 152-ФЗ: оператор, цели, перечень данных,
 * действия, срок и порядок отзыва должны быть на странице.
 */
final class ConsentPageTest extends WebTestCase
{
    public function testConsentDocumentHasRequiredParts(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/consent');

        self::assertResponseIsSuccessful();
        self::assertSame([], $client->getResponse()->headers->getCookies());
        $main = $crawler->filter('main')->text();
        self::assertStringNotContainsString('Документ в разработке', $main);

        foreach (['consent-operator', 'consent-purposes', 'consent-data', 'consent-actions', 'consent-term', 'consent-withdrawal', 'consent-how'] as $id) {
            self::assertSelectorExists('#'.$id.' h2');
        }
        foreach (['ООО «Ваш Финдир»', 'ОГРН 1156188000176', 'hello@vashfindir.ru', '3 года', 'ClientID', 'Отзыв согласия', 'не распространяется на получение рекламных'] as $fragment) {
            self::assertStringContainsString($fragment, $main);
        }
        // Редакция на странице -- та же, что сохраняется в заявке.
        self::assertSelectorTextContains('[data-vf-section="consent-hero"]', LeadConsent::VERSION);
        self::assertSelectorExists('meta[name="robots"][content="noindex, follow"]');
        self::assertSelectorExists('[data-vf-section="consent-document"] a[href="/privacy"]');
        self::assertSelectorTextContains('[data-vf-section="consent-hero"]', 'Действует с '.(new \DateTimeImmutable(LeadConsent::VERSION))->format('d.m.Y'));
    }

    /** Пункт 7 цитирует подпись чекбокса: при правке формы текст согласия должен остаться верным. */
    public function testConsentQuotesLeadFormCheckboxLabel(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $label = $client->getCrawler()->filter('form[data-vf-lead-form] input[name="agreement"]')->closest('[data-vf-component]')?->filter('label')->text();
        self::assertNotEmpty($label);

        $client->request('GET', '/consent');
        self::assertSelectorTextContains('#consent-how', '«'.$label.'»');
    }
}
