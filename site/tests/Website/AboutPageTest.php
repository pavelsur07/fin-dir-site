<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AboutPageTest extends WebTestCase
{
    public function testSectionsFollowAgreedOrderAndShareTeamWithHome(): void
    {
        $client = static::createClient();
        $client->request('GET', '/about');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('main section[data-vf-component="page-header"]', 'финансовый директор на аутсорсинге');

        // Порядок секций; каждая -- прямой потомок main без вложенного контейнера layout.
        $labels = $client->getCrawler()->filter('main > section, main > div > section')->each(
            static fn ($node): ?string => $node->attr('aria-labelledby'),
        );
        self::assertSame(
            [null, 'mission-title', 'about-facts-title', 'about-process-title', 'about-audience-title', 'about-team-title', 'about-requisites-title', 'cta-banner-title'],
            $labels,
        );

        // Команда -- те же данные, что на главной.
        self::assertSelectorCount(4, 'main section[aria-labelledby="about-team-title"] li');
        self::assertSelectorCount(1, 'main section[aria-labelledby="about-team-title"] img[alt="Павел Новиков, управляющий партнёр"]');
        self::assertSelectorTextContains('#about-team-title', 'Кто будет вашим финдиром');

        self::assertSelectorCount(3, 'main section[aria-labelledby="about-facts-title"] li');
        self::assertSelectorCount(3, 'main section[aria-labelledby="about-process-title"] li');
        self::assertSelectorCount(6, 'main section[aria-labelledby="about-audience-title"] li');
        self::assertSelectorCount(8, 'main section[aria-labelledby="about-requisites-title"] dl > div');
        self::assertSelectorExists('main section[aria-labelledby="cta-banner-title"] .dark a[href="/#lead-form"]');
    }

    public function testHomeKeepsTeamAndTrustSectionIds(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorCount(4, 'main section[aria-labelledby="home-team-title"] li');
        self::assertSelectorExists('#home-trust-data-title');
    }

    public function testHomeShowsTrustFactsAsRowLikeOnCasesPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // Строка без карточек и без видимого заголовка: числа type-t3-num, подписи type-t6.
        self::assertSelectorCount(3, 'main section[aria-label="Нам доверяют"] li');
        self::assertSelectorCount(3, 'main section[aria-label="Нам доверяют"] li span.type-t3-num');
        self::assertSelectorCount(3, 'main section[aria-label="Нам доверяют"] li span.type-t6');
        self::assertSelectorNotExists('main section[aria-label="Нам доверяют"] h2');
        self::assertSelectorNotExists('main section[aria-label="Нам доверяют"] li.rounded-lg');
        self::assertSelectorNotExists('#about-facts-title');
    }

    public function testRequisitesBelongToFinKonsalting(): void
    {
        $client = static::createClient();
        $client->request('GET', '/about');

        $text = (string) $client->getCrawler()->filter('main section[aria-labelledby="about-requisites-title"]')->text();
        foreach (['ООО «Фин Консалтинг»', '6140000234 / 616801001', '1156188000176', 'Ростовская обл., г. Ростов-на-Дону, ул. Малиновского, д. 3б, офис 8', 'АО «АЛЬФА-БАНК»'] as $expected) {
            self::assertStringContainsString($expected, $text);
        }
        self::assertStringNotContainsString('Демо-банк', $text);
        self::assertStringNotContainsString('Демонстрационная', $text);
    }

    public function testFooterAndLegalPagesNameTheSameLegalEntity(): void
    {
        $client = static::createClient();

        foreach (['/', '/privacy', '/consent'] as $path) {
            $client->request('GET', $path);
            self::assertStringContainsString('ООО «Фин Консалтинг»', (string) $client->getResponse()->getContent(), $path);
            self::assertStringNotContainsString('ООО «Ваш Финдир»', (string) $client->getResponse()->getContent(), $path);
        }
        $client->request('GET', '/privacy');
        self::assertSelectorTextContains('#privacy-requisites', 'КПП: 616801001');
        self::assertSelectorTextContains('#privacy-requisites', 'Ростовская обл., г. Ростов-на-Дону, ул. Малиновского, д. 3б, офис 8');
    }

    public function testAboutPageKeepsTrustFactsAsCards(): void
    {
        $client = static::createClient();
        $client->request('GET', '/about');

        self::assertSelectorCount(3, 'main section[aria-labelledby="about-facts-title"] li.rounded-lg');
        self::assertSelectorExists('main section[aria-labelledby="about-facts-title"] h2#about-facts-title');
    }
}
