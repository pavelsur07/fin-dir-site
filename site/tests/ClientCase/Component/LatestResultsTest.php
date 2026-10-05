<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Component;

use App\Tests\ClientCase\Builder\ClientCaseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Twig\Environment;

final class LatestResultsTest extends WebTestCase
{
    public function testHomeAndServicePagesLoadCasesThemselves(): void
    {
        $client = static::createClient();

        foreach (['/' => ['home-cases', 'bg-surface-muted'], '/services/finansovyy-direktor-na-autsorsinge' => ['service-cases', 'bg-surface']] as $path => [$id, $background]) {
            $client->request('GET', $path);

            self::assertResponseIsSuccessful($path);
            self::assertSelectorCount(1, 'section#'.$id.'[data-vf-component="latest-case-results"]', $path);
            self::assertSelectorTextContains('#'.$id.'-title', 'Результаты клиентов в цифрах');
            self::assertSelectorCount(3, '#'.$id.' [data-vf-component="case-card"]', $path);
            self::assertSelectorExists('#'.$id.' a[href="/cases"]', $path);
            self::assertSelectorExists('section#'.$id.'.'.$background, $path);
        }
    }

    public function testHomeNoLongerHasHardcodedDemoCases(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertSelectorNotExists('#home-cases-title + p');
        self::assertSelectorTextNotContains('main', 'Как это выглядит на практике');
        self::assertSelectorTextNotContains('main', 'Реальные кейсы появятся после согласования');
    }

    public function testComponentShowsOnlyPublishedAndFewerThanLimit(): void
    {
        $client = static::createClient();
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('one')->withTitle('Опубликованный')->published('2026-02-01')->build(),
            ClientCaseBuilder::aCase()->withSlug('draft')->withTitle('Черновик')->build(),
        );

        $client->request('GET', '/');

        self::assertSelectorCount(1, '#home-cases [data-vf-component="case-card"]');
        self::assertSelectorTextNotContains('#home-cases', 'Черновик');
    }

    public function testSectionIsAbsentWithoutPublishedCases(): void
    {
        $client = static::createClient();
        $this->resetCases();

        foreach (['/', '/services/finansovyy-direktor-na-autsorsinge'] as $path) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
            self::assertSelectorNotExists('[data-vf-component="latest-case-results"]', $path);
        }
    }

    public function testParametersLimitAndTitleAreRespected(): void
    {
        self::bootKernel();
        $this->resetCases(
            ClientCaseBuilder::aCase()->withSlug('a')->withTitle('Кейс А')->published('2026-02-03')->build(),
            ClientCaseBuilder::aCase()->withSlug('b')->withTitle('Кейс Б')->published('2026-02-02')->build(),
            ClientCaseBuilder::aCase()->withSlug('c')->withTitle('Кейс В')->published('2026-02-01')->build(),
        );

        $html = self::getContainer()->get(Environment::class)
            ->createTemplate('<twig:ClientCase:LatestResults :limit="2" id="x" title="Свой заголовок" />')
            ->render();

        self::assertStringContainsString('id="x-title"', $html);
        self::assertStringContainsString('Свой заголовок', $html);
        // Самые свежие по дате публикации.
        self::assertStringContainsString('Кейс А', $html);
        self::assertStringContainsString('Кейс Б', $html);
        self::assertStringNotContainsString('Кейс В', $html);
    }

    private function resetCases(\App\ClientCase\Entity\ClientCase ...$cases): void
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
