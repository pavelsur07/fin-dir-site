<?php

declare(strict_types=1);

namespace App\Tests\Publication\Controller;

use App\Publication\Entity\Post;
use App\Tests\Publication\Builder\PostBuilder;
use App\Tests\Publication\PostTableCleaner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicBlogTest extends WebTestCase
{
    use PostTableCleaner;

    private const string LEGACY_SLUG = 'marketpleys-ili-internet-magazin';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLegacyArticleIsImportedByMigration(): void
    {
        $crawler = $this->client->request('GET', '/gazeta/'.self::LEGACY_SLUG);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Маркетплейс или интернет-магазин');
        self::assertSelectorExists('article[data-vf-section="article"] .overflow-x-auto table');
        // Из старой вёрстки не переехали внешние картинки и форма, терявшая заявки.
        self::assertSelectorCount(0, 'main img');
        self::assertSelectorCount(0, 'main form');

        // Правила SITE_RULES §2, §11 на отрендеренной странице, а не только в шаблонах.
        $html = $crawler->html();
        self::assertStringNotContainsString('bootstrap', $html);
        self::assertStringNotContainsString('data-bs-', $html);
        self::assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html);
        self::assertDoesNotMatchRegularExpression('/<script\b(?![^>]*\bsrc\s*=)(?![^>]*type\s*=\s*"application\/ld\+json")[^>]*>/i', $html);
    }

    public function testOldUrlRedirectsPermanently(): void
    {
        $this->client->request('GET', '/gazeta/post-1');

        self::assertResponseStatusCodeSame(301);
        self::assertResponseRedirects('http://localhost/gazeta/'.self::LEGACY_SLUG, 301);
    }

    public function testIndexListsOnlyPublishedPosts(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('visible')->withTitle('Опубликованная статья')->published('2026-02-01')->build(),
            PostBuilder::aPost()->withSlug('hidden')->withTitle('Черновик статьи')->build(),
        );

        $this->client->request('GET', '/gazeta');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorExists('[data-vf-section="article-list"] a[href="/gazeta/visible"]');
        self::assertSelectorExists('[data-vf-section="article-list"] time[datetime="2026-02-01"]');
        self::assertSelectorTextNotContains('main', 'Черновик статьи');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru/gazeta"]');
    }

    public function testEmptyIndexShowsHonestEmptyState(): void
    {
        $this->resetPosts();

        $this->client->request('GET', '/gazeta');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Публикаций пока нет');
    }

    public function testIndexPaginationKeepsCanonicalPerPage(): void
    {
        $posts = [];
        for ($i = 1; $i <= 13; ++$i) {
            $posts[] = PostBuilder::aPost()->withSlug('p-'.$i)->published(\sprintf('2026-01-%02d', $i))->build();
        }
        $this->resetPosts(...$posts);

        $this->client->request('GET', '/gazeta');
        self::assertSelectorCount(12, '[data-vf-section="article-list"] h2 a');
        self::assertSelectorExists('[data-vf-component="pagination"] a[href="/gazeta?page=2"]');
        self::assertSelectorNotExists('[data-vf-component="pagination"] a[href="/gazeta"]');

        $this->client->request('GET', '/gazeta?page=2');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '[data-vf-section="article-list"] h2 a');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru/gazeta?page=2"]');
        self::assertSelectorExists('[data-vf-component="pagination"] a[href="/gazeta"]');

        $this->client->request('GET', '/gazeta?page=3');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/gazeta?page=0');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/gazeta?page=abc');
        self::assertResponseStatusCodeSame(400);
    }

    public function testArticlePageHasSeoTocAndStructuredData(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('article')->withTitle('Статья о ДДС')
                ->withBody("## Первый раздел\n\nТекст.\n\n## Второй раздел\n\n<script>alert(1)</script>")
                ->published('2026-02-01 10:00')->build(),
        );

        $crawler = $this->client->request('GET', '/gazeta/article');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'Статья о ДДС');
        self::assertSelectorTextContains('nav[data-vf-component="breadcrumb"] li[aria-current="page"]', 'Статья о ДДС');
        self::assertSelectorExists('nav[data-vf-component="breadcrumb"] a[href="/gazeta"]');
        // Статья газеты -- на всю ширину header и footer.
        self::assertSelectorNotExists('main .max-w-3xl');
        self::assertSelectorTextContains('title', 'Статья о ДДС — Ваш Финдир');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru/gazeta/article"]');
        self::assertSelectorExists('meta[property="og:type"][content="article"]');
        self::assertSelectorExists('time[datetime="2026-02-01"]');
        self::assertCount(2, $crawler->filter('[data-vf-section="article"] nav ol a[href^="#section-"]'));
        self::assertCount(0, $crawler->filter('[data-vf-section="article"] article script'));

        $types = [];
        foreach ($crawler->filter('script[type="application/ld+json"]') as $script) {
            foreach ((array) json_decode((string) $script->textContent, true, flags: \JSON_THROW_ON_ERROR) as $schema) {
                $types[] = \is_array($schema) ? ($schema['@type'] ?? null) : null;
            }
        }
        self::assertContains('BlogPosting', $types);
        self::assertContains('BreadcrumbList', $types);
    }

    public function testTitleCannotBreakOutOfJsonLdScript(): void
    {
        $payload = '</script><script>alert(1)</script>';
        $this->resetPosts(PostBuilder::aPost()->withSlug('xss')->withTitle('Статья '.$payload)->published()->build());

        $this->client->request('GET', '/gazeta/xss');

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('<script>alert(1)', $html);
        self::assertSelectorTextContains('h1', $payload);

        // Экранирование не ломает JSON: после декодирования заголовок исходный.
        $headlines = [];
        foreach ($this->client->getCrawler()->filter('script[type="application/ld+json"]') as $script) {
            foreach ((array) json_decode((string) $script->textContent, true, flags: \JSON_THROW_ON_ERROR) as $schema) {
                if (\is_array($schema) && 'BlogPosting' === ($schema['@type'] ?? null)) {
                    $headlines[] = $schema['headline'] ?? null;
                }
            }
        }
        self::assertSame(['Статья '.$payload], $headlines);
    }

    public function testRelatedBlockNeedsAtLeastTwoOtherPosts(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('main')->published('2026-03-01')->build(),
            PostBuilder::aPost()->withSlug('other')->published('2026-02-01')->build(),
        );
        $this->client->request('GET', '/gazeta/main');
        self::assertSelectorNotExists('#related');

        $this->persist(PostBuilder::aPost()->withSlug('third')->published('2026-01-01')->build());
        $this->client->request('GET', '/gazeta/main');
        self::assertSelectorExists('#related a[href="/gazeta/other"]');
        self::assertSelectorExists('#related a[href="/gazeta/third"]');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hiddenPosts(): iterable
    {
        yield 'черновик' => ['draft'];
        yield 'архив' => ['archived'];
        yield 'нет такой' => ['missing'];
    }

    #[DataProvider('hiddenPosts')]
    public function testUnpublishedPostIsNotFound(string $slug): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('draft')->build(),
            PostBuilder::aPost()->withSlug('archived')->published()->archived()->build(),
        );

        $this->client->request('GET', '/gazeta/'.$slug);

        self::assertResponseStatusCodeSame(404);
    }

    public function testArticleBodyIsSemanticArticle(): void
    {
        $this->resetPosts(PostBuilder::aPost()->withSlug('semantic')->published()->build());

        $this->client->request('GET', '/gazeta/semantic');

        self::assertSelectorExists('article[data-vf-section="article"] header h1');
        self::assertSelectorCount(1, 'main article');
    }

    public function testArticleIsCacheableWithoutSession(): void
    {
        $this->resetPosts(PostBuilder::aPost()->withSlug('cached')->published('2026-02-01 10:00')->build());

        $this->client->request('GET', '/gazeta/cached');

        $response = $this->client->getResponse();
        self::assertTrue($response->headers->hasCacheControlDirective('public'));
        self::assertSame('300', $response->headers->getCacheControlDirective('max-age'));
        self::assertSame([], $response->headers->getCookies());
        // HTML зависит от других статей и ассетов: 304 по дате статьи отдавал бы устаревшую страницу.
        self::assertFalse($response->headers->has('Last-Modified'));
    }

    public function testSitemapContainsStaticPagesAndPublishedPostsOnly(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('in-sitemap')->published('2026-02-01')->build(),
            PostBuilder::aPost()->withSlug('draft-not-in-sitemap')->build(),
        );

        $this->client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string((string) $this->client->getResponse()->getContent());
        self::assertNotFalse($xml);
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }

        foreach (['https://vashfindir.ru/', 'https://vashfindir.ru/services', 'https://vashfindir.ru/gazeta', 'https://vashfindir.ru/gazeta/in-sitemap'] as $expected) {
            self::assertContains($expected, $urls);
        }
        self::assertNotContains('https://vashfindir.ru/gazeta/draft-not-in-sitemap', $urls);
        self::assertNotContains('https://vashfindir.ru/gazeta/post-1', $urls);
    }

    private function resetPosts(Post ...$posts): void
    {
        self::clearPosts(self::getContainer()->get(EntityManagerInterface::class));
        foreach ($posts as $post) {
            $this->persist($post);
        }
    }

    private function persist(Post $post): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($post);
        $entityManager->flush();
    }
}
