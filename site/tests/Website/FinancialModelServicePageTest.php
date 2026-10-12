<?php

declare(strict_types=1);

namespace App\Tests\Website;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FinancialModelServicePageTest extends WebTestCase
{
    private const string PATH = '/services/finansovaya-model-ot-professionala';

    /** @return iterable<string, array{string, string}> */
    public static function servicePages(): iterable
    {
        yield 'financial model' => [self::PATH, 'Разработка финансовой модели бизнеса'];
        yield 'accounting' => ['/services/buhgalterskie-uslugi', 'Бухгалтерские услуги'];
        yield 'financial accounting' => ['/services/finansovyy-uchet', 'Сервис финансового учета'];
    }

    #[DataProvider('servicePages')]
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

    public function testPageIsNotInHeaderMenuNorSitemap(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');
        foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
            self::assertSelectorNotExists($menu.' a[href="'.self::PATH.'"]');
            self::assertSelectorCount(1, $menu.' a[href="/services/finansovyy-direktor-na-autsorsinge"]');
        }

        $client->request('GET', '/sitemap.xml');
        self::assertStringNotContainsString(self::PATH, (string) $client->getResponse()->getContent());
    }

    #[DataProvider('servicePages')]
    public function testServicesPageLinksToTheStub(string $path, string $title): void
    {
        $client = static::createClient();
        $client->request('GET', '/services');

        self::assertSelectorExists('main a[href="'.$path.'"]');
    }
}
