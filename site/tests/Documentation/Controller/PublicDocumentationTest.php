<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Controller;

use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicDocumentationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient([], ['HTTP_HOST' => 'docs.vashfindir.ru']);
    }

    private function seed(int $count = 1): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        for ($i = 0; $i < $count; ++$i) {
            $em->persist(DocPageBuilder::aPage($section, 'report-'.$i, true, $i));
        }
        $em->persist(DocPageBuilder::aPage($section, 'secret-draft'));
        $em->flush();
    }

    public function testPublishedPageHasCanonicalTocAndNoSession(): void
    {
        $this->seed();
        $this->client->request('GET', '/articles/report-0');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorExists('nav[aria-label="Оглавление"] a');
        self::assertSelectorExists('link[rel="canonical"][href="https://docs.vashfindir.ru/articles/report-0"]');
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
        self::assertStringContainsString('must-revalidate', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        self::assertSelectorExists('a[href="https://vashfindir.ru/privacy"]');
        self::assertSelectorNotExists('a[href*="secret-draft"]');
    }

    public function testDraftAndMissingPageHave404(): void
    {
        $this->seed();
        foreach (['/articles/secret-draft', '/articles/missing', '/articles/INVALID', '/sections/absent'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(404);
        }
    }

    public function testSectionPaginationAndNavigationAreStable(): void
    {
        $this->seed(45);
        $this->client->request('GET', '/sections/getting-started?page=3');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(5, 'main h2 a[href*="/articles/"]');
        $this->client->request('GET', '/sections/getting-started?page=4');
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/articles/report-1');
        self::assertSelectorExists('a[rel="prev"][href$="report-0"]');
        self::assertSelectorExists('a[rel="next"][href$="report-2"]');
    }

    public function testRussianSearchAndEmptyResults(): void
    {
        $this->seed();
        $this->client->request('GET', '/search?q=операции');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('main h2 a[href$="report-0"]');
        self::assertSelectorExists('meta[name="robots"][content="noindex, follow"]');
        self::assertSelectorNotExists('a[href*="secret-draft"]');
        $this->client->request('GET', '/search?q=несуществующееслово&page=2');
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/search?q=');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Введите запрос');
        $this->client->request('GET', '/search?q='.str_repeat('a', 201));
        self::assertResponseStatusCodeSame(422);
        $this->client->request('GET', '/search?page=abc');
        self::assertResponseStatusCodeSame(400);
    }

    public function testSitemapAndRobotsBelongToDocsHost(): void
    {
        $this->seed();
        $this->client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        $xml = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('https://docs.vashfindir.ru/articles/report-0', $xml);
        self::assertStringNotContainsString('secret-draft', $xml);
        $this->client->request('GET', '/robots.txt');
        self::assertStringContainsString('Sitemap: https://docs.vashfindir.ru/sitemap.xml', (string) $this->client->getResponse()->getContent());
        $this->client->request('GET', 'https://vashfindir.ru/robots.txt');
        self::assertStringContainsString('Sitemap: https://vashfindir.ru/sitemap.xml', (string) $this->client->getResponse()->getContent());
    }

    public function testUnsupportedMethodCannotFallThroughToMainSite(): void
    {
        foreach (['/', '/search', '/articles/report-0', '/sections/start', '/sitemap.xml', '/robots.txt'] as $path) {
            $this->client->request('POST', $path);
            self::assertResponseStatusCodeSame(405);
        }
        $this->client->request('POST', '/admin/docs/pages/1/publish');
        self::assertResponseRedirects('https://vashfindir.ru/admin/docs/pages/1/publish', 301);
        $this->client->request('GET', 'https://vashfindir.ru/articles/report-0');
        self::assertResponseStatusCodeSame(404);
    }

    public function testHeadKeepsStatusAndHasNoBodyOrCookie(): void
    {
        $this->seed();
        $this->client->request('HEAD', '/articles/report-0');
        self::assertResponseIsSuccessful();
        self::assertSame('', $this->client->getResponse()->getContent());
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
    }
}
