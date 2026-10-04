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
            self::assertSelectorCount(['/' => 20, '/about' => 2][$path] ?? 0, 'body svg', $path);
            // Шапка (24 и 32), мобильное меню (32) и подвал (28) -- картинки внутри ссылок с доступным именем.
            self::assertSelectorCount(4, 'img[data-vf-logo][alt=""][src$=".svg"]', $path);
            if ('/' === $path) {
                self::assertSelectorCount(8, 'main section[aria-labelledby="home-pain-points-title"] svg[aria-hidden="true"]');
                self::assertSelectorCount(7, 'main section[aria-labelledby="home-fit-title"] svg[aria-hidden="true"]');
                self::assertSelectorExists('main section[aria-labelledby="home-process-title"] > div > h2#home-process-title');
                self::assertSelectorExists('main section[aria-labelledby="home-trust-data-title"] > div > h2#home-trust-data-title');
                self::assertSelectorExists('main section[aria-labelledby="home-profit-path-title"] > div > h2#home-profit-path-title');
                self::assertSelectorCount(4, 'main section[aria-labelledby="home-team-title"] li');
                self::assertSelectorCount(1, 'main section[aria-labelledby="home-team-title"] img[alt="Павел Новиков, управляющий партнёр"]');
            }
            // Главная и страницы с шапкой (Page Header) имеют полноширинную секцию; текст остальных страниц остаётся в общем контейнере.
            self::assertSelectorExists(in_array($path, ['/', '/about'], true) ? 'main > section > div.max-w-container h1' : 'main > div.max-w-container > div:not([class*="max-w-"]) h1', $path);
        }
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
        foreach (['analytics.js', 'navigation.js', 'metrika.js', 'article-toc.js'] as $script) {
            self::assertSame(file_get_contents($root.'/assets/scripts/website/'.$script), file_get_contents($root.'/public/assets/website/'.$script));
        }
    }

    public function testAssetVersionMatchesCompiledWebsiteAssets(): void
    {
        $root = dirname(__DIR__, 2);
        $contents = '';
        foreach (['app.css', 'analytics.js', 'navigation.js', 'metrika.js', 'article-toc.js'] as $asset) {
            $contents .= (string) file_get_contents($root.'/public/assets/website/'.$asset);
        }
        $version = substr(hash('sha256', $contents), 0, 12);
        foreach (['website/layouts/base.html.twig', 'admin/layout.html.twig'] as $layout) {
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
            self::assertSame(
                ['Услуги', 'Кейсы', 'Газета', 'Партнёрам', 'О Компании'],
                $crawler->filter($menu.' ul a')->each(static fn ($link): string => trim($link->text())),
            );
            self::assertSelectorExists($menu.' a[href="https://app.vashfindir.ru/"]');
            self::assertSelectorTextContains($menu.' a[href="https://app.vashfindir.ru/"]', 'Личный кабинет');
            self::assertSelectorCount(1, $menu.' [data-vf-component="button"]');
        }
        self::assertStringNotContainsString('Записаться на встречу', $crawler->filter('header')->text());
    }

    public function testNavigationHighlightsTheSameItemInBothMenus(): void
    {
        $client = static::createClient();
        foreach (['/' => null, '/services' => '/services', '/cases' => '/cases', '/about' => '/about', '/gazeta' => '/gazeta', '/partners' => '/partners', '/privacy' => null] as $path => $activeHref) {
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
     * aria-current, fg 600 и полоса 2px accent-fill на нижней кромке; crimson-текста в меню нет, кнопка 40/8.
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
        self::assertSelectorNotExists('[data-vf-desktop-navigation] a.text-accent, [data-vf-mobile-navigation] a.text-accent');
        self::assertSelectorNotExists('[data-vf-desktop-navigation] a[class*="hover:text-accent"], [data-vf-mobile-navigation] a[class*="hover:text-accent"]');
        // Кнопка шапки 40/8, а не 48/12.
        self::assertSelectorExists('[data-vf-desktop-navigation] [data-vf-component="button"].h-control-md.rounded-sm');
        self::assertSelectorNotExists('header [data-vf-component="button"].h-control-lg');

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
        foreach (['/services' => 'Услуги', '/cases' => 'Кейсы', '/about' => 'О Компании', '/gazeta' => 'Газета', '/partners' => 'Партнёрам'] as $path => $label) {
            $crawler = $client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
            foreach (['[data-vf-desktop-navigation]', '[data-vf-mobile-navigation]'] as $menu) {
                self::assertSelectorCount(1, $menu.' a[aria-current="page"]', $path.' '.$menu);
                self::assertSame($label, trim($crawler->filter($menu.' a[aria-current="page"]')->text()), $path.' '.$menu);
            }
            self::assertSelectorCount(1, '[data-vf-desktop-navigation] ul span.absolute.bg-accent-fill', $path);
            self::assertSelectorCount(1, '[data-vf-desktop-navigation] ul a[aria-current="page"] > span.absolute.bg-accent-fill', $path);
            self::assertSelectorNotExists('[data-vf-desktop-navigation] ul a:not([aria-current]) > span.absolute', $path);
            foreach (['[data-vf-desktop-navigation] ul', '[data-vf-mobile-navigation] ul'] as $list) {
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
