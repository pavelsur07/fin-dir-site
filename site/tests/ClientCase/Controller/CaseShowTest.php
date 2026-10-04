<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Controller;

use App\ClientCase\Entity\ClientCase;
use App\Tests\ClientCase\Builder\ClientCaseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CaseShowTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testPublishedCaseIsShownWithNoindexAndOtherCases(): void
    {
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('first')->withTitle('Первый кейс')->published('2026-02-01')->build(),
            ClientCaseBuilder::aCase()->withSlug('second')->withTitle('Второй кейс')->published('2026-02-02')->build(),
            ClientCaseBuilder::aCase()->withSlug('draft')->withTitle('Черновик')->build(),
        );

        $this->client->request('GET', '/cases/first');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'Первый кейс');
        self::assertSelectorExists('meta[name="robots"][content="noindex, follow"]');
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/cases"][aria-current="page"]');
        self::assertSelectorExists('main form[data-vf-lead-form][action="/lead"]');
        // «Другие кейсы»: только опубликованные и не текущий.
        self::assertSelectorCount(1, '[data-vf-component="case-card"]');
        self::assertSelectorTextContains('[data-vf-component="case-card"]', 'Второй кейс');
        self::assertSelectorTextNotContains('main', 'Черновик');
        self::assertTrue($this->client->getResponse()->headers->hasCacheControlDirective('public'));
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
    }

    public function testDraftArchivedAndUnknownCasesAreNotFound(): void
    {
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('draft')->build(),
            ClientCaseBuilder::aCase()->withSlug('archived')->published()->archived()->build(),
        );

        foreach (['draft', 'archived', 'missing'] as $slug) {
            $this->client->request('GET', '/cases/'.$slug);
            self::assertResponseStatusCodeSame(404, $slug);
        }
    }

    public function testFeaturedCasePageShowsStepsAndMetrics(): void
    {
        $this->resetCases(ClientCaseBuilder::aCase()->withSlug('main')->featured()->published()->build());

        $this->client->request('GET', '/cases/main');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Что сделали');
        self::assertSelectorTextContains('main', 'Шаг');
        self::assertSelectorTextContains('main', 'метрика');
    }

    public function testCaseCardsLinkToCasePages(): void
    {
        $this->resetCases(ClientCaseBuilder::aCase()->withSlug('linked')->published()->build());

        $this->client->request('GET', '/cases');

        self::assertSelectorExists('[data-vf-component="case-card"] a[href="/cases/linked"]');
    }

    public function testSeededDemoCaseWithStepsIsAvailable(): void
    {
        $this->client->request('GET', '/cases/kassovye-razryvy-sluchalis-kazhdyj-mesyac');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Построили платёжный календарь');
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
