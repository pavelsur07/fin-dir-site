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
}
