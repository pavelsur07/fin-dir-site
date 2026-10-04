<?php

declare(strict_types=1);

namespace App\Tests\Publication\Controller;

use App\Publication\Entity\Post;
use App\Publication\ValueObject\PostRubric;
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
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/gazeta"][aria-current="page"]');
        self::assertSelectorExists('[data-vf-mobile-navigation] a[href="/gazeta"][aria-current="page"]');
        self::assertSelectorExists('article[data-vf-section="article"] .overflow-x-auto table');
        // Из старой вёрстки не переехали внешние картинки; обложки у публикации нет -- блока нет.
        self::assertSelectorCount(0, 'main img');
        self::assertSelectorCount(0, 'article figure');
        // Форма вопроса: одна в панели (с lg) и одна под статьёй (ниже lg), обе на POST /lead с CSRF и согласием.
        self::assertSelectorCount(2, 'main form[data-vf-lead-form][action="/lead"]');
        self::assertSelectorExists('article aside form#article-question-panel-form, article aside form[aria-labelledby="article-question-panel-title"]');
        self::assertSelectorExists('article > div.lg\\:hidden form[aria-labelledby="article-question-bottom-title"]');
        self::assertSelectorCount(2, 'main form input[name="_token"]');
        self::assertSelectorCount(2, 'main form input[name="agreement"][required]');
        self::assertSelectorTextContains('#article-question-panel-title', 'Вопрос по вашей ситуации?');
        self::assertSelectorTextContains('article aside form p', 'Консультант ответит в Telegram или перезвонит в течение часа.');

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
        self::assertSelectorExists('[data-vf-section="article-featured"][href="/gazeta/visible"]');
        self::assertSelectorTextContains('[data-vf-section="article-featured"] time', '1 февраля 2026');
        self::assertSelectorTextContains('[data-vf-section="article-featured"]', '1 мин');
        // Обложки нет: типографская заглушка, скрытая от скринридера, без <img>.
        self::assertSelectorExists('[data-vf-section="article-featured"] [aria-hidden="true"].aspect-video');
        self::assertSelectorCount(0, 'main img');
        self::assertSelectorExists('[data-vf-component="breadcrumb"], nav[aria-label="Хлебные крошки"]');
        self::assertSelectorExists('footer a[href="/gazeta"][aria-current="page"]');
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
        // Главная статья + 11 карточек в сетке = 12 на странице.
        self::assertSelectorCount(1, '[data-vf-section="article-featured"]');
        self::assertSelectorCount(11, '[data-vf-section="article-list"] h3');
        self::assertSelectorExists('[data-vf-component="pagination"] a[href="/gazeta?page=2"]');
        // Первая страница в пагинации -- текущая, без стрелки «назад».
        self::assertSelectorExists('[data-vf-component="pagination"] a[href="/gazeta"][aria-current="page"]');
        self::assertSelectorNotExists('[data-vf-component="pagination"] a[aria-label="Предыдущая страница"]');

        $this->client->request('GET', '/gazeta?page=2');
        self::assertResponseIsSuccessful();
        // Не первая страница: главной статьи нет, одна карточка.
        self::assertSelectorCount(0, '[data-vf-section="article-featured"]');
        self::assertSelectorCount(1, '[data-vf-section="article-list"] h3');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru/gazeta?page=2"]');
        self::assertSelectorExists('link[rel="prev"][href="https://vashfindir.ru/gazeta"]');
        self::assertSelectorNotExists('link[rel="next"]');
        self::assertSelectorExists('[data-vf-component="pagination"] a[href="/gazeta"]');

        $this->client->request('GET', '/gazeta?page=3');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/gazeta?page=0');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/gazeta?page=abc');
        self::assertResponseStatusCodeSame(400);
    }

    /**
     * Поведение скролл-шпиона проверить в PHP нельзя, но правила раздела 26 зафиксированы в скрипте:
     * линия 160px, все H2, у конца статьи активен последний, «0 из N» до первого H2, аккордеон сворачивается по клику.
     */
    public function testScrollSpyScriptKeepsSectionRules(): void
    {
        $script = (string) file_get_contents(self::getContainer()->getParameter('kernel.project_dir').'/assets/scripts/website/article-toc.js');

        self::assertStringContainsString('const ACTIVE_LINE = 160;', $script);
        self::assertStringContainsString("body.querySelectorAll('h2[id]')", $script);
        self::assertStringContainsString('body.lastElementChild', $script);
        self::assertStringContainsString('current = headings.length - 1;', $script);
        self::assertStringContainsString('`${activeIndex + 1} из ${headings.length}`', $script);
        self::assertStringContainsString('accordion.open = false;', $script);
        self::assertStringNotContainsString('.style', $script);
    }

    public function testTocListsAtMostSevenSectionsAndIsHiddenForSingleSection(): void
    {
        $sections = '';
        for ($i = 1; $i <= 9; ++$i) {
            $sections .= "## Раздел {$i}\n\nТекст.\n\n";
        }
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('long')->withBody($sections)->published('2026-02-01 10:00')->build(),
            PostBuilder::aPost()->withSlug('short')->withBody("## Единственный\n\nТекст.")->published('2026-02-02 10:00')->build(),
        );

        $crawler = $this->client->request('GET', '/gazeta/long');
        self::assertCount(7, $crawler->filter('article aside nav a.js-toc-link'));
        // Больше 7 разделов: полосы нет, есть счётчик по всем H2.
        self::assertCount(0, $crawler->filter('article aside .js-toc-segment'));
        self::assertSelectorTextSame('article aside .js-toc-progress', '0 из 9');

        $crawler = $this->client->request('GET', '/gazeta/short');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('article aside nav'));
        self::assertCount(0, $crawler->filter('article details'));
        // Форма вопроса остаётся и без оглавления.
        self::assertCount(1, $crawler->filter('article aside form'));
    }

    /**
     * Вложенная страница статьи подсвечивает «Газета»: ровно один aria-current="page" в десктопной шапке и в мобильном меню
     * (третий -- последняя крошка). Панель статьи -- <aside> с подписью, внутри nav «Содержание».
     */
    public function testArticlePageHighlightsGazetaInBothMenusAndHasLabelledPanel(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('nav-article')->withBody("## Первый\n\nТекст.\n\n## Второй\n\nТекст.")->published('2026-02-01 10:00')->build(),
        );

        $crawler = $this->client->request('GET', '/gazeta/nav-article');

        self::assertResponseIsSuccessful();
        foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
            self::assertSelectorCount(1, $menu.' a[aria-current="page"]', $menu);
            self::assertSame('Газета', trim($crawler->filter($menu.' a[aria-current="page"]')->text()), $menu);
        }
        self::assertSelectorCount(1, '[data-vf-desktop-navigation] ul span.absolute.bg-accent-fill');
        self::assertSelectorCount(1, 'nav[data-vf-component="breadcrumb"] li[aria-current="page"]');
        self::assertSelectorExists('article aside[aria-label="Панель статьи"] nav[aria-label="Содержание"]');
    }

    public function testPostCanOverrideQuestionFormTitle(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('with-title')->withFormTitle('Вопрос по НДС для вашей компании?')->published('2026-02-01 10:00')->build(),
        );

        $this->client->request('GET', '/gazeta/with-title');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#article-question-panel-title', 'Вопрос по НДС для вашей компании?');
        self::assertSelectorTextContains('#article-question-bottom-title', 'Вопрос по НДС для вашей компании?');
        self::assertSelectorTextNotContains('main', 'Вопрос по вашей ситуации?');
        // Подзаголовок общий.
        self::assertSelectorTextContains('#article-question-panel-title + p', 'Консультант ответит в Telegram');
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
        // Один список пунктов выводится дважды: в панели (с lg) и в раскрывающемся блоке (ниже lg).
        self::assertCount(2, $crawler->filter('aside nav a.js-toc-link[href^="#section-"]'));
        self::assertCount(2, $crawler->filter('details nav a.js-toc-link[href^="#section-"]'));
        self::assertSelectorExists('script[src^="/assets/website/article-toc.js?v="]');
        // Раздел 26: оглавление липкое (top-sticky) в колонке панели, форма внизу панели; колонка слева и панель справа (justify-between), H2 с scroll-mt-sticky.
        self::assertSelectorExists('article aside.lg\\:min-w-toc.lg\\:max-w-toc.lg\\:self-stretch > div.grow > nav.lg\\:sticky.lg\\:top-sticky');
        self::assertSelectorExists('article > div.lg\\:flex.lg\\:justify-between.lg\\:gap-10 > [data-vf-article-body].max-w-measure');
        self::assertSelectorExists('[data-vf-article-body] h2.scroll-mt-sticky');
        // Ниже lg -- аккордеон (не sticky): строка control-xl, пункты control-touch, chevron поворачивается за duration-fast.
        self::assertSelectorExists('article details[data-vf-toc-accordion].lg\\:hidden.rounded-md.border > summary.h-control-xl');
        self::assertSelectorExists('article details[data-vf-toc-accordion] svg.group-open\\:rotate-180.duration-fast.size-icon-md');
        self::assertSelectorExists('article details[data-vf-toc-accordion] a.js-toc-link.min-h-control-touch');
        self::assertSelectorNotExists('article details.sticky');
        // Счётчик до первого H2 -- «0 из N» и сегмент прогресса на каждый H2.
        self::assertSelectorTextSame('article aside .js-toc-progress', '0 из 2');
        self::assertCount(2, $crawler->filter('article aside .js-toc-segment'));
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

    public function testRubricFilterShowsOnlyThatRubricWithoutFeatured(): void
    {
        $this->resetPosts(
            PostBuilder::aPost()->withSlug('tax-1')->withTitle('Про налоги')->withRubric(PostRubric::TAXES)->published('2026-02-02')->build(),
            PostBuilder::aPost()->withSlug('unit-1')->withTitle('Про юнит')->withRubric(PostRubric::UNIT_ECONOMICS)->published('2026-02-01')->build(),
            PostBuilder::aPost()->withSlug('none-1')->withTitle('Без рубрики')->published('2026-01-01')->build(),
        );

        $this->client->request('GET', '/gazeta?rubric=nalogi');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, '[data-vf-section="article-featured"]');
        self::assertSelectorCount(1, '[data-vf-section="article-list"] h3');
        self::assertSelectorTextContains('[data-vf-section="article-list"]', 'Про налоги');
        self::assertSelectorTextNotContains('main', 'Про юнит');
        self::assertSelectorTextNotContains('main', 'Без рубрики');
        self::assertSelectorExists('[data-vf-component="rubrics"] a[href="/gazeta?rubric=nalogi"][aria-current="true"]');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru/gazeta?rubric=nalogi"]');
    }

    public function testEmptyRubricShowsEmptyStateAndUnknownRubricIs404(): void
    {
        $this->resetPosts(PostBuilder::aPost()->withSlug('tax-1')->withRubric(PostRubric::TAXES)->published('2026-02-02')->build());

        $this->client->request('GET', '/gazeta?rubric=otchetnost');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Статей в этой рубрике пока нет');
        self::assertSelectorCount(0, '[data-vf-section="article-list"]');

        $this->client->request('GET', '/gazeta?rubric=net-takoj');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPaginationLinksKeepRubric(): void
    {
        $posts = [];
        for ($i = 1; $i <= 13; ++$i) {
            $posts[] = PostBuilder::aPost()->withSlug('t-'.$i)->withRubric(PostRubric::TAXES)->published(\sprintf('2026-01-%02d', $i))->build();
        }
        $this->resetPosts(...$posts);

        $this->client->request('GET', '/gazeta?rubric=nalogi');

        self::assertSelectorExists('link[rel="next"][href="https://vashfindir.ru/gazeta?rubric=nalogi&page=2"]');
        self::assertSelectorExists('[data-vf-component="pagination"] a[href="/gazeta?rubric=nalogi&page=2"]');
        self::assertSelectorExists('[data-vf-component="show-more"] a[href="/gazeta?rubric=nalogi&page=2"]');
        self::assertSelectorTextContains('[data-vf-component="show-more"]', 'Показано 12 из 13');
    }
}
