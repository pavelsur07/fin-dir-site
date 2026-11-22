<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HeaderServicesMenuTest extends WebTestCase
{
    private const array SERVICE_LINKS = [
        '/services/finansovyy-direktor-na-autsorsinge',
        '/services/finansovaya-model-ot-professionala',
        '/services/buhgalterskie-uslugi',
        '/services/finansovyy-uchet',
    ];

    public function testDesktopServicesDropdownIsDisclosureWithFourServicesAndConsultCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $button = $crawler->filter('[data-vf-desktop-navigation] button[data-vf-dropdown]');
        self::assertCount(1, $button);
        self::assertSame('false', $button->attr('aria-expanded'));
        self::assertSame('Услуги', trim($button->text()));
        self::assertNull($button->attr('aria-current'));

        $panel = '#'.$button->attr('aria-controls');
        self::assertSelectorExists($panel.'[hidden]');
        self::assertSame(self::SERVICE_LINKS, $crawler->filter($panel.' ul a')->each(static fn ($link): ?string => $link->attr('href')));
        self::assertSelectorTextContains($panel.' a[href="/services/finansovyy-uchet"]', 'Онлайн');
        self::assertSelectorTextContains($panel.' a[href="/services/finansovyy-direktor-na-autsorsinge"]', 'Управленческий учёт');
        self::assertSelectorTextContains($panel.' a[href="/services"]', 'Все услуги и цены');
        // «Записаться»: с окном -- открывает его, без окна ведёт к форме на главной.
        self::assertSelectorExists($panel.' a[data-vf-lead-modal-open][href="/#lead-form"]');
        self::assertSelectorTextContains($panel, 'Не знаете, что выбрать?');
    }

    public function testMobileAccordionIsCollapsedOnHomeAndExpandedOnServicePage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $accordion = $crawler->filter('[data-vf-mobile-navigation] button[data-vf-disclosure]');
        self::assertSame('false', $accordion->attr('aria-expanded'));
        $panel = '#'.$accordion->attr('aria-controls');
        self::assertSelectorExists($panel.'[hidden]');
        self::assertSame(self::SERVICE_LINKS, $crawler->filter($panel.' ul a')->each(static fn ($link): ?string => $link->attr('href')));
        // Пояснения -- только на десктопе.
        self::assertSelectorTextNotContains($panel, 'Управленческий учёт');
        // Закреплённый низ панели: окно записи и личный кабинет.
        self::assertSelectorExists('[data-vf-menu-panel] a[data-vf-lead-modal-open][href="/#lead-form"]');
        self::assertSelectorTextContains('[data-vf-menu-panel] a[data-vf-lead-modal-open]', 'Записаться на разбор');
        self::assertSelectorExists('[data-vf-menu-panel] a[href="https://app.vashfindir.ru/"]');

        $crawler = $client->request('GET', '/services/finansovyy-direktor-na-autsorsinge');
        $accordion = $crawler->filter('[data-vf-mobile-navigation] button[data-vf-disclosure]');
        self::assertSame('true', $accordion->attr('aria-expanded'));
        self::assertSelectorNotExists('#'.$accordion->attr('aria-controls').'[hidden]');
    }

    public function testServicePageMarksItsLinkInBothMenusAndUnderlinesServicesButton(): void
    {
        $client = static::createClient();
        $client->request('GET', '/services/finansovyy-direktor-na-autsorsinge');

        foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
            self::assertSelectorCount(1, $menu.' a[aria-current="page"]', $menu);
            self::assertSelectorExists($menu.' a[href="/services/finansovyy-direktor-na-autsorsinge"][aria-current="page"]');
        }
        self::assertSelectorExists('[data-vf-desktop-navigation] button[data-vf-dropdown].font-semibold > span.absolute.bg-accent-fill');
        self::assertSelectorNotExists('[data-vf-desktop-navigation] button[aria-current]');
    }

    public function testDocsHeaderHasNoServicesMenuNorConsultCard(): void
    {
        $client = self::createClient([], ['HTTP_HOST' => 'docs.vashfindir.ru']);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('header [data-vf-disclosure]');
        self::assertSelectorNotExists('header [data-vf-lead-modal-open]');
        self::assertSelectorTextNotContains('header', 'Услуги');
        self::assertSelectorExists('[data-vf-desktop-navigation] a[aria-current="page"]');
        self::assertSelectorExists('[data-vf-menu-panel] a[href="https://app.vashfindir.ru/"]');
    }
}
