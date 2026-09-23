<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebsiteSeoTest extends WebTestCase
{
    public function testHomepageRendersSeoMetadata(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'Финансовый директор на аутсорсинге');
        self::assertSelectorCount(1, 'title');
        self::assertSelectorTextContains('title', 'Финансовый директор на аутсорсинге для селлеров маркетплейсов');
        self::assertSelectorExists('meta[name="description"][content*="ДДС"]');
        self::assertSelectorExists('meta[name="robots"][content="index, follow"]');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru/"]');
        self::assertSelectorExists('link[rel="icon"][type="image/svg+xml"][href="/favicon.svg"]');

        self::assertSelectorExists('meta[property="og:type"][content="website"]');
        self::assertSelectorExists('meta[property="og:site_name"][content="Ваш Финдир"]');
        self::assertSelectorExists('meta[property="og:locale"][content="ru_RU"]');
        self::assertSelectorExists('meta[property="og:title"]');
        self::assertSelectorExists('meta[property="og:description"]');
        self::assertSelectorExists('meta[property="og:url"][content="https://vashfindir.ru/"]');
        self::assertSelectorExists('meta[property="og:image"][content="https://vashfindir.ru/assets/og-image.png"]');
        self::assertSelectorExists('meta[name="twitter:card"][content="summary_large_image"]');

        self::assertSelectorCount(1, 'script[src^="/assets/website/metrika.js?v="][defer]');
    }

    public function testHomepageJsonLdIsValidAndMatchesPageContent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();

        $types = [];
        $faqQuestionCount = null;
        foreach ($crawler->filter('script[type="application/ld+json"]') as $node) {
            $documents = json_decode((string) $node->textContent, true);
            self::assertIsArray($documents, 'JSON-LD должен быть валидным JSON');

            foreach ($documents as $document) {
                self::assertSame('https://schema.org', $document['@context'] ?? null);
                $type = $document['@type'] ?? null;
                self::assertIsString($type);
                $types[] = $type;

                if ('FAQPage' === $type) {
                    $faqQuestionCount = count($document['mainEntity'] ?? []);
                }
            }
        }

        foreach (['Organization', 'WebSite', 'Service', 'FAQPage'] as $expectedType) {
            self::assertContains($expectedType, $types);
        }

        $faqItemsCount = $crawler->filter('[data-vf-section="faq"] details')->count();
        self::assertGreaterThan(0, $faqItemsCount);
        self::assertSame($faqItemsCount, $faqQuestionCount, 'FAQPage mainEntity должен совпадать с видимым FAQ');
    }

    public function testUiKitStaysNoindexWithoutAnalytics(): void
    {
        $client = static::createClient();

        foreach (['/ui-kit', '/ui-kit/sections'] as $path) {
            $client->request('GET', $path);

            self::assertResponseIsSuccessful();
            self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
            self::assertSelectorCount(0, 'script[src*="metrika"]', $path);
            self::assertSelectorExists('link[rel="canonical"]', $path);
        }
    }

    public function testSeoStaticFilesExistAndAreConsistent(): void
    {
        $robots = $this->read($this->projectPath('public/robots.txt'));
        self::assertStringContainsString('Disallow: /ui-kit', $robots);
        self::assertStringContainsString('Sitemap: https://vashfindir.ru/sitemap.xml', $robots);

        // sitemap генерируется маршрутом (Stage 6): статический файл перекрыл бы его в nginx.
        self::assertFileDoesNotExist($this->projectPath('public/sitemap.xml'));

        self::assertFileExists($this->projectPath('public/favicon.svg'));

        $ogImage = $this->read($this->projectPath('public/assets/og-image.png'));
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $ogImage);
    }

    private function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 2).'/'.$relativePath;
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertNotFalse($contents, $path);

        return $contents;
    }
}
