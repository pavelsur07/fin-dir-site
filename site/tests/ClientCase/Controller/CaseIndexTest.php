<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Controller;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseIndustry;
use App\Tests\ClientCase\Builder\ClientCaseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CaseIndexTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testSeededDemoCasesAreShownAndPageIsIndexable(): void
    {
        $this->client->request('GET', '/cases');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('[role="status"]', 'Показано кейсов: 7');
        self::assertSelectorExists('meta[name="robots"][content="index, follow"]');
        self::assertSelectorExists('[data-vf-component="case-featured"]');
        self::assertSelectorCount(6, '[data-vf-component="case-card"]');
        self::assertSelectorCount(1, 'nav[data-vf-component="cases-filter"] a[aria-current="true"]');
        self::assertSelectorExists('main form[data-vf-lead-form][action="/lead"]');
        // Секция заявки светлая: панель на bg-surface без блока .dark.
        self::assertSelectorExists('section#lead-form.bg-surface');
        self::assertSelectorNotExists('.dark #lead-form, #lead-form .dark, #lead-form.dark');
        self::assertSelectorTextContains('#lead-form', 'Хотите увидеть реальную прибыль своего бизнеса?');
    }

    public function testDraftAndArchivedCasesAreNotPublic(): void
    {
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('pub')->withTitle('Опубликованный')->published()->build(),
            ClientCaseBuilder::aCase()->withSlug('draft')->withTitle('Черновик')->build(),
            ClientCaseBuilder::aCase()->withSlug('arch')->withTitle('Архивный')->published()->archived()->build(),
        );

        $this->client->request('GET', '/cases');

        self::assertSelectorTextContains('[role="status"]', 'Показано кейсов: 1');
        self::assertSelectorExists('[data-vf-component="case-card"]');
        self::assertSelectorTextContains('main', 'Опубликованный');
        self::assertSelectorTextNotContains('main', 'Черновик');
        self::assertSelectorTextNotContains('main', 'Архивный');
    }

    public function testFeaturedCaseIsShownForAllAndItsIndustryOnly(): void
    {
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('main')->withTitle('Главный')->inIndustry(CaseIndustry::CONSTRUCTION)->featured()->published('2026-02-01')->build(),
            ClientCaseBuilder::aCase()->withSlug('build')->inIndustry(CaseIndustry::CONSTRUCTION)->published('2026-02-02')->build(),
            ClientCaseBuilder::aCase()->withSlug('it')->inIndustry(CaseIndustry::IT)->published('2026-02-03')->build(),
        );

        $this->client->request('GET', '/cases');
        self::assertSelectorExists('[data-vf-component="case-featured"]');
        self::assertSelectorTextContains('[role="status"]', 'Показано кейсов: 3');

        $this->client->request('GET', '/cases?industry=stroitelstvo');
        self::assertSelectorExists('[data-vf-component="case-featured"]');
        self::assertSelectorCount(1, '[data-vf-component="case-card"]');
        self::assertSelectorTextContains('[role="status"]', 'Показано кейсов: 2');
        self::assertSelectorTextContains('nav[data-vf-component="cases-filter"] a[aria-current="true"]', 'Строительство');

        $this->client->request('GET', '/cases?industry=it');
        self::assertSelectorNotExists('[data-vf-component="case-featured"]');
        self::assertSelectorCount(1, '[data-vf-component="case-card"]');
        self::assertSelectorTextContains('[role="status"]', 'Показано кейсов: 1');
    }

    public function testUnknownIndustryIsNotFound(): void
    {
        $this->client->request('GET', '/cases?industry=unknown');

        self::assertResponseStatusCodeSame(404);
    }

    public function testSitemapContainsCasesPageAndPublishedCasesOnly(): void
    {
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('in-sitemap')->published()->build(),
            ClientCaseBuilder::aCase()->withSlug('draft-case')->build(),
            ClientCaseBuilder::aCase()->withSlug('archived-case')->published()->archived()->build(),
        );

        $this->client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        $xml = simplexml_load_string((string) $this->client->getResponse()->getContent());
        self::assertNotFalse($xml);
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }

        self::assertContains('https://vashfindir.ru/cases', $urls);
        self::assertContains('https://vashfindir.ru/cases/in-sitemap', $urls);
        self::assertNotContains('https://vashfindir.ru/cases/draft-case', $urls);
        self::assertNotContains('https://vashfindir.ru/cases/archived-case', $urls);
    }

    private function resetCases(ClientCase ...$cases): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // Миграция кладёт в site_test демо-кейсы; DELETE идёт внутри транзакции DAMA и откатывается.
        $entityManager->getConnection()->executeStatement('DELETE FROM client_case');
        foreach ($cases as $case) {
            $entityManager->persist($case);
        }
        $entityManager->flush();
    }
}
