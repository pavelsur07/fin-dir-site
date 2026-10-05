<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FinancialModelServicePageTest extends WebTestCase
{
    private const string PATH = '/services/finansovaya-model-ot-professionala';

    public function testStubPageHasOnlyHeaderAndIsHiddenFromSearch(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertFalse($client->getResponse()->headers->has('Set-Cookie'));
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'Финансовая модель от профессионала');
        self::assertPageTitleSame('Финансовая модель от профессионала — Ваш Финдир');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru'.self::PATH.'"]');
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

    public function testServicesPageLinksToTheStub(): void
    {
        $client = static::createClient();
        $client->request('GET', '/services');

        self::assertSelectorExists('main a[href="'.self::PATH.'"]');
    }
}
