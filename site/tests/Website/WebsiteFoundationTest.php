<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebsiteFoundationTest extends WebTestCase
{
    public function testUiKitShowsProductionComponentsWithTailwindClasses(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/ui-kit');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-vf-ui-kit-intro] h1', 'Компоненты сайта');
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSelectorCount(1, 'main h1');
        self::assertSelectorExists('[data-vf-ui-kit-section="palette"] .bg-slate-950');
        self::assertSelectorExists('[data-vf-ui-kit-section="palette"] .bg-red-700');
        self::assertSelectorExists('[data-vf-ui-kit-section="logo"] img[src="/assets/brand/logo-light.png"]');
        self::assertSelectorExists('[data-vf-ui-kit-section="logo"] img[src="/assets/brand/logo-dark.png"]');
        self::assertSelectorExists('[data-vf-component="navbar"] dialog[data-vf-menu-dialog]');
        self::assertSelectorExists('[data-vf-component="button"][data-vf-variant="primary"]');
        self::assertSelectorExists('[data-vf-component="alert"]');
        self::assertSelectorCount(0, 'main [style], main style, main script');
        self::assertSame(1, $crawler->filter('link[href^="/assets/website/app.css?v="]')->count());
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
}
