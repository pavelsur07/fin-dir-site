<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebsiteFoundationTest extends WebTestCase
{
    public function testUiKitUsesProductionComponentsAndBothThemes(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/ui-kit');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSelectorExists('[data-vf-color-context="light"]');
        self::assertSelectorExists('[data-vf-color-context="dark"][data-theme="dark"]');
        self::assertSelectorExists('[data-vf-section="hero"][data-theme="dark"]');
        self::assertSelectorExists('[data-vf-component="navbar"] img[src="/assets/brand/logo-light.png"]');
        self::assertSelectorExists('[data-vf-component="footer"][data-theme="dark"] img[src="/assets/brand/logo-dark.png"]');

        foreach (['button', 'card', 'badge', 'alert', 'form-input', 'select', 'textarea', 'checkbox', 'accordion', 'breadcrumb', 'navbar', 'footer', 'cta'] as $component) {
            self::assertSelectorExists(sprintf('[data-vf-component="%s"]', $component));
        }
        foreach (['primary', 'secondary', 'ghost'] as $variant) {
            self::assertSelectorExists(sprintf('[data-vf-component="button"][data-vf-variant="%s"]', $variant));
        }
        foreach (['default', 'hover', 'focus', 'active'] as $state) {
            self::assertSelectorExists(sprintf('[data-vf-component="button"][data-vf-state="%s"]', $state));
        }
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-open][aria-expanded="false"]');
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-open][aria-label="Открыть меню"][aria-haspopup="dialog"][aria-controls]');
        self::assertSelectorExists('[data-vf-component="navbar"] dialog[data-vf-menu-dialog]');
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-close]');
        $drawerId = $crawler->filter('[data-vf-menu-open]')->attr('aria-controls');
        self::assertNotNull($drawerId);
        self::assertCount(1, $crawler->filter('dialog#'.$drawerId));
        self::assertSelectorExists('[data-vf-component="accordion"] details > summary h3');
        self::assertSelectorExists('script[src^="/assets/website/navigation.js?v="][defer]');
        self::assertSelectorCount(0, 'link[href*="bootstrap"], script[src*="bootstrap"]');
        self::assertSelectorExists('[data-vf-state="error"] [aria-invalid="true"]');
        self::assertSelectorExists('[data-vf-component="checkbox"] input:not([checked])');
        self::assertSelectorExists('[data-vf-component="navbar"] a[aria-current="page"]');
        foreach (['input' => 'form-input', 'select' => 'select', 'textarea' => 'textarea', 'checkbox' => 'checkbox'] as $group => $component) {
            foreach (['default', 'focus', 'disabled', 'error', 'success'] as $state) {
                self::assertSelectorExists(sprintf('[data-vf-form-group="%s"] [data-vf-component="%s"][data-vf-state="%s"]', $group, $component, $state));
            }
            self::assertSelectorExists(sprintf('[data-vf-form-group="%s"] [data-vf-component="%s"][data-vf-state="disabled"] :disabled', $group, $component));
        }
        self::assertSame(1, $crawler->filter('link[href^="/assets/website/app.css?v="]')->count());
    }

    public function testWebsiteAssetsAreBuiltFromOnePinnedEntrypoint(): void
    {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root.'/assets/styles/website/app.css');
        $compiled = file_get_contents($root.'/public/assets/website/app.css');
        self::assertIsString($source);
        self::assertIsString($compiled);
        self::assertStringContainsString('@import "tailwindcss" source(none);', $source);
        self::assertStringContainsString('--color-*: initial;', $source);
        self::assertStringContainsString('.bg-accent', $compiled);
        self::assertStringContainsString('.grid-auto-fit', $compiled);
        self::assertStringContainsString('.navigation-drawer-width', $compiled);
        self::assertStringNotContainsString('@import "tailwindcss"', $compiled);
        $wrapperPath = dirname(__DIR__, 3).'/scripts/tailwindcss.sh';
        if (!is_file($wrapperPath)) {
            $wrapperPath = '/workspace/scripts/tailwindcss.sh';
        }
        $wrapper = file_get_contents($wrapperPath);
        self::assertIsString($wrapper);
        self::assertStringContainsString("TAILWIND_VERSION='4.3.3'", $wrapper);
        self::assertStringContainsString('dc61b3ac6b8c9ca874c0cc4c57b2409791a64c5540404ca5f5367360babc313a', $wrapper);
        $stylesheets = glob($root.'/assets/styles/website/*.css');
        self::assertIsArray($stylesheets);
        self::assertCount(1, $stylesheets);
        foreach (['analytics.js', 'navigation.js', 'metrika.js'] as $script) {
            self::assertSame(file_get_contents($root.'/assets/scripts/website/'.$script), file_get_contents($root.'/public/assets/website/'.$script));
        }
    }

    public function testAssetVersionMatchesCompiledWebsiteAssets(): void
    {
        $root = dirname(__DIR__, 2);
        $contents = '';
        foreach (['app.css', 'analytics.js', 'navigation.js', 'metrika.js'] as $asset) {
            $content = file_get_contents($root.'/public/assets/website/'.$asset);
            self::assertIsString($content);
            $contents .= $content;
        }
        $layout = file_get_contents($root.'/templates/website/layouts/base.html.twig');
        self::assertIsString($layout);
        self::assertSame(1, preg_match("/{% set vf_asset_version = '([0-9a-f]{12})' %}/", $layout, $matches));
        self::assertSame(substr(hash('sha256', $contents), 0, 12), $matches[1] ?? '');
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
