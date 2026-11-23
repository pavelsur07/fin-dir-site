<?php

declare(strict_types=1);

namespace App\Tests\Website;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FinancialModelServicePageTest extends WebTestCase
{
    private const string PATH = '/services/finansovaya-model-ot-professionala';

    /** @return iterable<string, array{string, string}> */
    public static function stubPages(): iterable
    {
        yield 'accounting' => ['/services/buhgalterskie-uslugi', 'Бухгалтерские услуги'];
        yield 'financial accounting' => ['/services/finansovyy-uchet', 'Сервис финансового учета'];
    }

    #[DataProvider('stubPages')]
    public function testStubPageHasOnlyHeaderAndIsHiddenFromSearch(string $path, string $title): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertFalse($client->getResponse()->headers->has('Set-Cookie'));
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', $title);
        self::assertSelectorExists('main section p');
        self::assertTrue($client->getResponse()->isCacheable());
        self::assertPageTitleSame($title.' — Ваш Финдир');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru'.$path.'"]');
        // Без контента: закрыта от индексации, секций и форм нет.
        self::assertSelectorExists('meta[name="robots"][content="noindex, follow"]');
        self::assertSelectorCount(1, 'main section');
        self::assertSelectorNotExists('main form');
        self::assertSelectorExists('nav[aria-label="Хлебные крошки"] a[href="/services"]');
    }

    public function testPageIsIndexableCacheableAndHasSections(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertFalse($client->getResponse()->headers->has('Set-Cookie'));
        self::assertTrue($client->getResponse()->isCacheable());
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextSame('h1', 'Разработка финансовой модели бизнеса');
        self::assertPageTitleSame('Разработка финансовой модели бизнеса — Ваш Финдир');
        self::assertSelectorExists('meta[name="robots"][content="index, follow"]');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru'.self::PATH.'"]');
        self::assertSelectorExists('nav[aria-label="Хлебные крошки"] a[href="/services"]');

        self::assertSelectorTextContains('#finmodel-structure-title', 'Из чего состоит финансовая модель');
        self::assertSelectorTextContains('#finmodel-process-title', 'Этапы работы');
        self::assertSelectorCount(3, '#finmodel-process ol > li');
        self::assertSelectorCount(6, 'details[name="finmodel-faq"]');
        self::assertSelectorExists('main a[href="/services/finansovyy-direktor-na-autsorsinge"]');
        // Кнопка первого экрана открывает окно записи, без JS ведёт к форме.
        self::assertSelectorExists('section[data-vf-section="hero"] a[href="#lead-form"][data-vf-lead-modal-open]');

        self::assertSelectorExists('#lead-form form[action="/lead"][data-vf-form-key="consultation"] input[name="_token"]');
    }

    public function testNoMockupStubsAreVisible(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        $text = (string) $client->getCrawler()->filter('main')->text();
        // Заглушки макета вида «[N]+ моделей», «Срок: [уточнить]»; квадратные скобки JSON-LD сюда не относятся.
        self::assertDoesNotMatchRegularExpression('/\[[А-Яа-яN]/u', $text);
        self::assertStringNotContainsString('Срок:', $text);
        self::assertStringNotContainsString('Заглушк', $text);
    }

    public function testJsonLdHasServiceAndFaqPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PATH);

        $types = [];
        $faq = null;
        foreach ($crawler->filter('script[type="application/ld+json"]') as $script) {
            $data = json_decode((string) $script->textContent, true, flags: JSON_THROW_ON_ERROR);
            foreach (array_is_list($data) ? $data : [$data] as $item) {
                $types[] = $item['@type'];
                if ('FAQPage' === $item['@type']) {
                    $faq = $item;
                }
            }
        }

        self::assertContains('Service', $types);
        self::assertNotNull($faq);
        self::assertCount(6, $faq['mainEntity']);
        self::assertSame('Чем финансовая модель отличается от бюджета?', $faq['mainEntity'][0]['name']);
    }

    public function testCasesSectionShowsPublishedCases(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        // Миграция кладёт в site_test демо-кейсы: секция показывает 3 свежих опубликованных.
        self::assertSelectorTextContains('#finmodel-cases-title', 'Результаты клиентов');
        self::assertSelectorCount(3, '#finmodel-cases [data-vf-component="case-card"]');
    }

    public function testPageIsInSitemapAndInServicesMenuOnly(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');
        // Не пункт верхнего уровня шапки, а пункт меню «Услуги».
        foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
            self::assertSelectorNotExists($menu.' > ul > li > a[href="'.self::PATH.'"]');
            self::assertSelectorCount(1, $menu.' [data-vf-disclosure] + * a[href="'.self::PATH.'"]');
        }

        $client->request('GET', '/sitemap.xml');
        self::assertStringContainsString('<loc>https://vashfindir.ru'.self::PATH.'</loc>', (string) $client->getResponse()->getContent());
    }

    /** @return iterable<string, array{string}> */
    public static function servicePages(): iterable
    {
        yield 'financial model' => [self::PATH];
        yield 'accounting' => ['/services/buhgalterskie-uslugi'];
        yield 'financial accounting' => ['/services/finansovyy-uchet'];
    }

    #[DataProvider('servicePages')]
    public function testServicesPageLinksToThePage(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', '/services');

        self::assertSelectorExists('main a[href="'.$path.'"]');
    }
}
