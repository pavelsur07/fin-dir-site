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

    public function testPublicPagesHaveOneHeadingAndNoIcons(): void
    {
        $client = static::createClient();

        foreach (['/', '/about', '/services', '/cases', '/partners', '/offer', '/privacy', '/consent', '/gazeta'] as $path) {
            $client->request('GET', $path);

            self::assertResponseIsSuccessful($path);
            self::assertSelectorCount(1, 'h1', $path);
            self::assertFalse($client->getResponse()->headers->has('Set-Cookie'), $path);
            self::assertSelectorExists('meta[name="description"]', $path);
            self::assertSelectorExists('link[rel="canonical"]', $path);
            // До новой дизайн-системы на сайте нет иконок, включая футер и cookie.
            self::assertSelectorCount(0, 'body svg', $path);
            // Контент всех страниц использует ту же базовую ширину, что шапка и футер.
            self::assertSelectorExists('main > div.max-w-6xl > div:not([class*="max-w-"]) h1', $path);
        }
    }

    public function testWebsiteAssetsAreBuiltFromPinnedTailwind(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root.'/assets/styles/website/app.css');
        $compiled = (string) file_get_contents($root.'/public/assets/website/app.css');
        self::assertStringContainsString('@import "tailwindcss" source(none);', $source);
        self::assertStringContainsString('.bg-red-700', $compiled);
        self::assertStringContainsString('.translate-x-full', $compiled);
        self::assertStringNotContainsString('@import "tailwindcss"', $compiled);

        $wrapperPath = dirname(__DIR__, 3).'/scripts/tailwindcss.sh';
        if (!is_file($wrapperPath)) {
            $wrapperPath = '/workspace/scripts/tailwindcss.sh';
        }
        $wrapper = (string) file_get_contents($wrapperPath);
        self::assertStringContainsString("TAILWIND_VERSION='4.3.3'", $wrapper);
        foreach (['analytics.js', 'navigation.js', 'metrika.js'] as $script) {
            self::assertSame(file_get_contents($root.'/assets/scripts/website/'.$script), file_get_contents($root.'/public/assets/website/'.$script));
        }
    }

    public function testAssetVersionMatchesCompiledWebsiteAssets(): void
    {
        $root = dirname(__DIR__, 2);
        $contents = '';
        foreach (['app.css', 'analytics.js', 'navigation.js', 'metrika.js'] as $asset) {
            $contents .= (string) file_get_contents($root.'/public/assets/website/'.$asset);
        }
        $version = substr(hash('sha256', $contents), 0, 12);
        foreach (['website/layouts/base.html.twig', 'admin/layout.html.twig'] as $layout) {
            $template = (string) file_get_contents($root.'/templates/'.$layout);
            self::assertStringContainsString("{% set vf_asset_version = '$version' %}", $template);
        }
    }

    public function testNavigationCtaFromServicesReachesTheHomeLeadForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/services');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/#lead-form"]');
        self::assertSelectorExists('[data-vf-mobile-navigation] a[href="/#lead-form"]');
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
