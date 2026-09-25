<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebsiteFoundationTest extends WebTestCase
{
    public function testUiKitFollowsTheDesignSystemReferenceLayout(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/ui-kit');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-vf-ui-kit-intro] h1', 'Ink & Crimson');
        self::assertSelectorExists('[data-vf-ui-kit-intro][data-theme="dark"]');
        self::assertSelectorExists('[data-vf-ui-kit-intro] [data-vf-component="badge"][data-vf-tone="accent-solid"]');
        self::assertSelectorCount(0, 'body > [data-vf-component="navbar"]');
        self::assertSelectorExists('[data-vf-ui-kit-intro] a[href="/"][aria-label]');
        foreach (['logo', 'palette', 'status-colors', 'roles', 'contrast', 'typography', 'radii', 'spacing', 'elevation', 'icons', 'interactive-states', 'data-format', 'forms', 'buttons', 'data-palette', 'alerts', 'navigation-preview', 'empty-state', 'motion', 'article-cards', 'article-preview', 'component-colors', 'section-previews'] as $section) {
            self::assertSelectorExists(sprintf('[data-vf-ui-kit-section="%s"]', $section));
        }
        foreach (['typography' => '05 ·', 'interactive-states' => '10 ·', 'forms' => '12 ·'] as $section => $number) {
            self::assertSelectorTextContains(sprintf('[data-vf-ui-kit-section="%s"] > div > p', $section), $number);
        }
        $swatches = $crawler->filter('[data-vf-swatch]');
        self::assertCount(32, $swatches);
        $css = file_get_contents(dirname(__DIR__, 2).'/assets/styles/website/app.css');
        self::assertIsString($css);
        foreach ($swatches as $swatch) {
            self::assertInstanceOf(\DOMElement::class, $swatch);
            self::assertStringContainsString('[data-vf-swatch="'.$swatch->getAttribute('data-vf-swatch').'"]', $css);
        }
        self::assertSelectorCount(22, '[data-vf-palette-swatch]');
        self::assertSelectorCount(4, '[data-vf-status-card]');
        self::assertSelectorCount(14, '[data-vf-role-row]');
        self::assertSelectorCount(8, '[data-vf-contrast-row]');
        self::assertSelectorCount(8, '[data-vf-type-row]');
        self::assertSelectorCount(5, '[data-vf-logo-size]');
        self::assertSelectorCount(2, '[data-vf-logo-theme]');
        self::assertSelectorExists('[data-vf-ui-kit-section="logo"] a[href="/assets/brand/logo-light.png"][download]');
        self::assertSelectorExists('[data-vf-ui-kit-section="logo"] [data-theme="dark"] a[href="/assets/brand/logo-dark.png"][download]');
        self::assertSelectorCount(1, 'main h1');
        self::assertSelectorCount(0, 'main [style], main style, main script');
    }

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
        self::assertSelectorExists('[data-vf-ui-kit-intro] a[href="/"]');
        self::assertSelectorExists('[data-vf-component="footer"][data-theme="dark"] img[src="/assets/brand/logo-dark.png"]');

        foreach (['button', 'card', 'badge', 'alert', 'form-input', 'select', 'textarea', 'checkbox', 'accordion', 'breadcrumb', 'footer', 'cta'] as $component) {
            self::assertSelectorExists(sprintf('[data-vf-component="%s"]', $component));
        }
        foreach (['primary', 'secondary', 'ghost'] as $variant) {
            self::assertSelectorExists(sprintf('[data-vf-component="button"][data-vf-variant="%s"]', $variant));
        }
        foreach (['default', 'hover', 'focus', 'active'] as $state) {
            self::assertSelectorExists(sprintf('[data-vf-component="button"][data-vf-state="%s"]', $state));
        }
        self::assertSelectorCount(0, '[data-vf-component="navbar"]');
        self::assertSelectorExists('#navigation-preview [aria-controls="vf-ui-nav-features"][aria-expanded="false"]');
        self::assertSelectorExists('[data-vf-component="accordion"] details > summary h3');
        self::assertSelectorExists('script[src^="/assets/website/navigation.js?v="][defer]');
        self::assertSelectorCount(0, 'link[href*="bootstrap"], script[src*="bootstrap"]');
        self::assertSelectorExists('[data-vf-state="error"] [aria-invalid="true"]');
        self::assertSelectorExists('[data-vf-component="checkbox"] input:not([checked])');
        foreach (['input' => 'form-input', 'select' => 'select', 'textarea' => 'textarea', 'checkbox' => 'checkbox'] as $group => $component) {
            foreach (['default', 'focus', 'disabled', 'error', 'success'] as $state) {
                self::assertSelectorExists(sprintf('[data-vf-form-group="%s"] [data-vf-component="%s"][data-vf-state="%s"]', $group, $component, $state));
            }
            self::assertSelectorExists(sprintf('[data-vf-form-group="%s"] [data-vf-component="%s"][data-vf-state="disabled"] :disabled', $group, $component));
        }
        self::assertSame(1, $crawler->filter('link[href^="/assets/website/app.css?v="]')->count());
    }

    public function testProductionNavbarRetainsMobileDialog(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/services');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-open][aria-expanded="false"]');
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-open][aria-label="Открыть меню"][aria-haspopup="dialog"][aria-controls]');
        self::assertSelectorExists('[data-vf-component="navbar"] dialog[data-vf-menu-dialog]');
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-close]');
        $drawerId = $crawler->filter('[data-vf-menu-open]')->attr('aria-controls');
        self::assertNotNull($drawerId);
        self::assertCount(1, $crawler->filter('dialog#'.$drawerId));
    }

    public function testUiKitNavigationPanelsHaveUniqueAccessibleControls(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/ui-kit');

        self::assertResponseIsSuccessful();
        self::assertCount(4, $crawler->filter('#navigation-preview .vf-ui-nav-block > h3'));
        $toggles = $crawler->filter('#navigation-preview [data-vf-ui-kit-nav-toggle]');
        self::assertCount(7, $toggles);
        $ids = [];
        foreach ($toggles as $toggle) {
            self::assertInstanceOf(\DOMElement::class, $toggle);
            self::assertSame('false', $toggle->getAttribute('aria-expanded'));
            $id = $toggle->getAttribute('aria-controls');
            self::assertNotContains($id, $ids);
            self::assertCount(1, $crawler->filter('#'.$id.'[hidden]'));
            $ids[] = $id;
        }
        self::assertCount(3, $crawler->filter('#navigation-preview [data-vf-ui-kit-company-option]'));
        self::assertCount(1, $crawler->filter('#navigation-preview [data-vf-ui-kit-company-empty][role="status"][aria-live="polite"]'));
        self::assertCount(7, $crawler->filter('#navigation-preview .vf-ui-nav-sidebar li'));
        self::assertCount(5, $crawler->filter('#navigation-preview .vf-ui-nav-tabbar > *'));
        self::assertCount(2, $crawler->filter('#navigation-preview [data-vf-component="breadcrumb"]'));
        $separators = $crawler->filter('#navigation-preview [data-vf-component="breadcrumb"] span[aria-hidden="true"]');
        self::assertCount(4, $separators);
        foreach ($separators as $separator) {
            self::assertSame('›', $separator->textContent);
        }
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
        foreach (['analytics.js', 'navigation.js', 'metrika.js', 'ui-kit-logo.js', 'ui-kit-navigation.js'] as $script) {
            self::assertSame(file_get_contents($root.'/assets/scripts/website/'.$script), file_get_contents($root.'/public/assets/website/'.$script));
        }
    }

    public function testAssetVersionMatchesCompiledWebsiteAssets(): void
    {
        $root = dirname(__DIR__, 2);
        $contents = '';
        foreach (['app.css', 'analytics.js', 'navigation.js', 'metrika.js', 'ui-kit-logo.js', 'ui-kit-navigation.js'] as $asset) {
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
