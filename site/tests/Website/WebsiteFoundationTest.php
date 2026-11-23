<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

final class WebsiteFoundationTest extends WebTestCase
{
    public function testUiKitPagesAreRemoved(): void
    {
        $client = static::createClient();

        foreach (['/ui-kit', '/ui-kit/sections'] as $path) {
            $client->request('GET', $path);
            self::assertResponseStatusCodeSame(404, $path);
        }
    }

    public function testPublicPagesHaveOneHeadingAndHomepageHasDecorativeIcons(): void
    {
        $client = static::createClient();

        foreach (['/', '/about', '/services', '/cases', '/partners', '/offer', '/privacy', '/consent', '/gazeta'] as $path) {
            $client->request('GET', $path);

            self::assertResponseIsSuccessful($path);
            self::assertSelectorCount(1, 'h1', $path);
            self::assertFalse($client->getResponse()->headers->has('Set-Cookie'), $path);
            self::assertSelectorExists('meta[name="description"]', $path);
            self::assertSelectorExists('link[rel="canonical"]', $path);
            // Инлайновые SVG -- только декоративные иконки; логотип -- файлы SVG в <img>.
            // Число иконок /cases зависит от количества кейсов в БД -- его проверяет CaseIndexTest.
            if ('/cases' !== $path) {
                if ('/' === $path) {
                    // Иконок на главной много (галочки, этапы маршрута, форма): все они декоративные.
                    self::assertSame($client->getCrawler()->filter('body svg[aria-hidden="true"]')->count(), $client->getCrawler()->filter('body svg')->count(), $path);
                } else {
                    // Иконки окна записи на разбор (маркетинговые страницы) и шапки (меню «Услуги», кнопки) не считаем;
                    // иконки шапки -- только декоративные.
                    $modalSvg = $client->getCrawler()->filter('[data-vf-section="lead-modal"] svg')->count();
                    $headerSvg = $client->getCrawler()->filter('header svg')->count();
                    self::assertSame($headerSvg, $client->getCrawler()->filter('header svg[aria-hidden="true"]')->count(), $path);
                    self::assertSame(['/about' => 2, '/gazeta' => 2][$path] ?? 0, $client->getCrawler()->filter('body svg')->count() - $modalSvg - $headerSvg, $path);
                }
            }
            // Шапка (24 и 32), мобильное меню (32) и подвал (28) -- картинки внутри ссылок с доступным именем; окно записи -- ещё один (28, тёмный).
            $modalLogos = $client->getCrawler()->filter('[data-vf-section="lead-modal"] img[data-vf-logo]')->count();
            self::assertSelectorCount(4 + $modalLogos, 'img[data-vf-logo][alt=""][src$=".svg"]', $path);
            if ('/' === $path) {
                // Состав главной (ноябрь 2026): секции из макета, прежние секции на главной не подключены.
                foreach (['home-hero-title', 'home-services-title', 'home-advantages-title', 'home-route-title', 'home-reports-title', 'home-integrations-title', 'home-team-title', 'home-faq-title', 'lead-form-title'] as $titleId) {
                    self::assertSelectorExists('main section[aria-labelledby="'.$titleId.'"]', $titleId);
                }
                self::assertSelectorExists('main section[data-vf-section="trust-strip"]');
                foreach (['home-pain-points-title', 'home-fit-title', 'home-process-title', 'home-trust-data-title', 'home-profit-path-title', 'home-cabinet-preview-title'] as $titleId) {
                    self::assertSelectorNotExists('#'.$titleId, $titleId);
                }
                self::assertSelectorCount(4, 'main section[aria-labelledby="home-team-title"] li');
                self::assertSelectorCount(1, 'main section[aria-labelledby="home-team-title"] img[alt="Павел Новиков, управляющий партнёр"]');
                // Все кнопки-ссылки главной ведут к форме заявки.
                $buttonLinks = $client->getCrawler()->filter('main a[data-vf-component="button"]');
                self::assertGreaterThan(0, $buttonLinks->count());
                self::assertSame(['#lead-form'], array_values(array_unique($buttonLinks->each(static fn ($link): string => (string) $link->attr('href')))));
            }
            // Главная и страницы с шапкой (Page Header) имеют полноширинную секцию; текст остальных страниц остаётся в общем контейнере.
            // /cases: тёмный hero -- Page Header внутри обёртки .dark.
            $headingSelector = match (true) {
                '/cases' === $path => 'main > div.dark > section[data-vf-component="page-header"] h1',
                in_array($path, ['/', '/about', '/gazeta'], true) => 'main > section > div.max-w-container h1',
                default => 'main > div.max-w-container > div:not([class*="max-w-"]) h1',
            };
            self::assertSelectorExists($headingSelector, $path);
        }
    }

    public function testServicesPageLinksToFinancialDirectorService(): void
    {
        $client = static::createClient();
        $client->request('GET', '/services');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorExists('main a[href="/services/finansovyy-direktor-na-autsorsinge"]');
        self::assertSelectorExists('main a[href="/services/finansovaya-model-ot-professionala"]');
    }

    public function testWebsiteAssetsAreBuiltFromPinnedTailwind(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root.'/assets/styles/website/app.css');
        $compiled = (string) file_get_contents($root.'/public/assets/website/app.css');
        self::assertStringContainsString('@import "tailwindcss" source(none);', $source);
        self::assertStringContainsString('.bg-accent-fill', $compiled);
        self::assertStringNotContainsString('.bg-red-700', $compiled);
        self::assertStringContainsString('.translate-x-full', $compiled);
        self::assertStringNotContainsString('@import "tailwindcss"', $compiled);

        $wrapperPath = dirname(__DIR__, 3).'/scripts/tailwindcss.sh';
        if (!is_file($wrapperPath)) {
            $wrapperPath = '/workspace/scripts/tailwindcss.sh';
        }
        $wrapper = (string) file_get_contents($wrapperPath);
        self::assertStringContainsString("TAILWIND_VERSION='4.3.3'", $wrapper);
        foreach (['analytics.js', 'navigation.js', 'metrika.js', 'article-toc.js', 'bitrix24.js', 'tabs.js', 'lead-modal.js', 'doc-image-viewer.js'] as $script) {
            self::assertSame(file_get_contents($root.'/assets/scripts/website/'.$script), file_get_contents($root.'/public/assets/website/'.$script));
        }
    }

    public function testAssetVersionMatchesCompiledWebsiteAssets(): void
    {
        $root = dirname(__DIR__, 2);
        $contents = '';
        foreach (['app.css', 'analytics.js', 'navigation.js', 'metrika.js', 'article-toc.js', 'bitrix24.js', 'tabs.js', 'lead-modal.js', 'doc-image-viewer.js'] as $asset) {
            $contents .= (string) file_get_contents($root.'/public/assets/website/'.$asset);
        }
        $version = substr(hash('sha256', $contents), 0, 12);
        foreach (['website/layouts/base.html.twig'] as $layout) {
            $template = (string) file_get_contents($root.'/templates/'.$layout);
            self::assertStringContainsString("{% set vf_asset_version = '$version' %}", $template);
        }
    }

    public function testNavigationContainsRequestedItemsAndAccountCta(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/services');

        self::assertResponseIsSuccessful();
        foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
            // Верхний уровень: «Услуги» -- кнопка раскрытия, остальные -- ссылки.
            self::assertSame(
                ['Услуги', 'Кейсы', 'Газета', 'Партнёрам', 'О Компании'],
                $crawler->filter($menu.' > ul > li > a, '.$menu.' > ul > li > button')->each(static fn ($link): string => trim($link->text())),
            );
        }
        // «Личный кабинет»: в десктопной шапке и в закреплённом низу мобильной панели.
        foreach (['[data-vf-desktop-navigation]', '[data-vf-menu-panel]'] as $area) {
            self::assertSelectorTextContains($area.' a[href="https://app.vashfindir.ru/"]', 'Личный кабинет');
            self::assertSelectorCount(1, $area.' a[href="https://app.vashfindir.ru/"][data-vf-component="button"]');
        }
        self::assertStringNotContainsString('Записаться на встречу', $crawler->filter('header')->text());
    }

    public function testNavigationHighlightsTheSameItemInBothMenus(): void
    {
        $client = static::createClient();
        // «Услуги»: aria-current получает пункт текущей страницы в панели (/services -- «Все услуги и цены»).
        foreach (['/' => null, '/services' => '/services', '/services/finansovyy-direktor-na-autsorsinge' => '/services/finansovyy-direktor-na-autsorsinge', '/cases' => '/cases', '/about' => '/about', '/gazeta' => '/gazeta', '/partners' => '/partners', '/privacy' => null] as $path => $activeHref) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
            foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
                self::assertSelectorCount($activeHref ? 1 : 0, $menu.' a[aria-current="page"]', $path.' '.$menu);
                if ($activeHref) {
                    self::assertSelectorExists($menu.' a[href="'.$activeHref.'"][aria-current="page"]', $path);
                }
            }
        }
    }

    /**
     * Шапка по разделу 16.1: высота 64 / 72 с lg, пункты во всю высоту с плашкой 40/8, активный раздел --
     * aria-current, fg 600 и полоса 2px accent-fill на нижней кромке; crimson-текста в меню нет, кнопка 44/10.
     */
    public function testHeaderFollowsDesignSystemAndActiveItemHasNoCrimsonText(): void
    {
        $client = static::createClient();
        // Вложенная страница статьи подсвечивает «Газета».
        $client->request('GET', '/gazeta');
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('header nav.h-header-compact.lg\\:h-header');
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/gazeta"][aria-current="page"].font-semibold.text-fg.self-stretch');
        self::assertSelectorCount(1, '[data-vf-desktop-navigation] a[aria-current="page"] > span.absolute.inset-x-3.bottom-0.h-0\\.5.rounded-full.bg-accent-fill');
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/gazeta"] > span.h-control-md.rounded-sm');
        // Crimson-текст и акцентный hover в меню убраны (мобильное меню тоже).
        // Пункты верхнего уровня; ссылка «Все услуги и цены» в панели «Услуги» -- синяя по макету.
        self::assertSelectorNotExists('[data-vf-desktop-navigation] > ul > li > a.text-accent, [data-vf-mobile-navigation] > ul > li > a.text-accent');
        self::assertSelectorNotExists('[data-vf-desktop-navigation] > ul > li > a[class*="hover:text-accent"], [data-vf-mobile-navigation] > ul > li > a[class*="hover:text-accent"]');
        // Кнопка шапки использует общую высоту 44px и радиус 10px.
        self::assertSelectorExists('[data-vf-desktop-navigation] [data-vf-component="button"].min-h-11.rounded-md[data-vf-variant="secondary"]');
        self::assertSelectorNotExists('header [data-vf-component="button"].bg-red-600');

        $client->request('GET', '/gazeta/marketpleys-ili-internet-magazin');
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/gazeta"][aria-current="page"]');
    }

    /**
     * На каждом разделе ровно один aria-current="page" и в десктопной шапке, и в мобильном меню; полоса, font-semibold
     * и атрибут завязаны на одно условие: полосы без атрибута и атрибута без оформления не бывает.
     */
    public function testActiveItemHasExactlyOneAriaCurrentAndMatchingStyling(): void
    {
        $client = static::createClient();
        // Разделы-ссылки; «Услуги» (кнопка с панелью) -- HeaderServicesMenuTest.
        foreach (['/cases' => 'Кейсы', '/about' => 'О Компании', '/gazeta' => 'Газета', '/partners' => 'Партнёрам'] as $path => $label) {
            $crawler = $client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
            foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
                self::assertSelectorCount(1, $menu.' a[aria-current="page"]', $path.' '.$menu);
                self::assertSame($label, trim($crawler->filter($menu.' a[aria-current="page"]')->text()), $path.' '.$menu);
            }
            self::assertSelectorCount(1, '[data-vf-desktop-navigation] ul span.absolute.bg-accent-fill', $path);
            self::assertSelectorCount(1, '[data-vf-desktop-navigation] ul a[aria-current="page"] > span.absolute.bg-accent-fill', $path);
            self::assertSelectorNotExists('[data-vf-desktop-navigation] ul a:not([aria-current]) > span.absolute', $path);
            foreach (['[data-vf-desktop-navigation] > ul > li >', '[data-vf-mobile-navigation] > ul > li >'] as $list) {
                self::assertSelectorCount(1, $list.' a.font-semibold', $path);
                self::assertSelectorNotExists($list.' a.font-semibold:not([aria-current])', $path);
                self::assertSelectorNotExists($list.' a[aria-current]:not(.font-semibold)', $path);
            }
        }
    }

    public function testHomepageHasOneFooter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'footer');
    }

    public function testErrorPagesRenderWithTheirHeadings(): void
    {
        $client = static::createClient();
        $client->request('GET', '/page-that-does-not-exist');
        self::assertResponseStatusCodeSame(404);
        $requests = static::getContainer()->get(RequestStack::class);
        $requests->push(Request::create('/'));
        try {
            foreach (['error404', 'error500'] as $error) {
                $html = static::getContainer()->get(Environment::class)->render('bundles/TwigBundle/Exception/'.$error.'.html.twig');
                self::assertSame(1, substr_count($html, '<h1'), $error);
                self::assertStringContainsString('<meta name="description"', $html, $error);
            }
        } finally {
            $requests->pop();
        }
    }
}
