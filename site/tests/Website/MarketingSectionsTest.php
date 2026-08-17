<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MarketingSectionsTest extends WebTestCase
{
    public function testMarketingSectionsCatalogRendersProductionPatterns(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-kit/sections');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'Готовые секции');
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSelectorExists('link[href^="/assets/website/app.css?v="]');
        self::assertSelectorExists('script[src^="/assets/website/navigation.js?v="][defer]');
        self::assertSelectorExists('[data-vf-component="navbar"] dialog[data-vf-menu-dialog]');
        self::assertSelectorExists('[data-vf-component="navbar"] nav[aria-label="Основная навигация"] [data-vf-desktop-navigation]');
        self::assertSelectorExists('nav a[aria-current="page"][href="/ui-kit/sections"]');

        foreach ([
            'hero',
            'typography-stress',
            'problem',
            'benefits',
            'steps',
            'paths',
            'case-preview',
            'proof',
            'quote',
            'pricing',
            'comparison',
            'faq',
            'lead-form',
            'article-preview',
            'cta',
        ] as $section) {
            self::assertSelectorExists(sprintf('[data-vf-section="%s"]', $section));
        }

        self::assertSelectorCount(3, '[data-vf-section="feature"]');
        self::assertSelectorExists('[data-vf-section="feature"] [data-vf-media-position="left"]');
        self::assertSelectorExists('[data-vf-section="feature"] [data-vf-media-position="right"]');
        self::assertSelectorCount(2, '[data-vf-section="feature"] [data-vf-demo-media][aria-hidden="true"]');

        self::assertSelectorCount(6, '#problem-section [data-vf-layout="text-list"] > li');
        self::assertSelectorCount(2, '#benefits-section [data-vf-layout="grid"] > li');
        self::assertSelectorCount(5, '#steps-section [data-vf-layout="steps"] > li');
        self::assertSelectorCount(2, '#paths-section [data-vf-layout="paths"] > li');
        self::assertSelectorCount(3, '#case-preview-section [data-vf-layout="case-preview"] > div');
        self::assertSelectorCount(4, '#pricing-section [data-vf-layout="grid"] > li');
        self::assertSelectorCount(2, '#article-preview-section [data-vf-layout="grid"] > li');

        self::assertSelectorExists('#problem-section [data-vf-layout="text-list"][role="list"]');
        self::assertSelectorExists('#benefits-section [data-vf-layout="grid"][role="list"]');
        self::assertSelectorExists('#steps-section [data-vf-layout="steps"][role="list"]');
        self::assertSelectorCount(5, '#steps-section [data-vf-step-number]:not([aria-hidden])');
        self::assertSelectorExists('#paths-section [data-vf-layout="paths"][role="list"]');

        self::assertSelectorExists('[data-vf-layout="steps"]');
        self::assertSelectorExists('[data-vf-layout="paths"]');
        self::assertSelectorExists('[data-vf-layout="quote"] blockquote');
        self::assertSelectorExists('[data-vf-layout="comparison"][role="region"][tabindex="0"]');
        self::assertSelectorCount(4, '#comparison-section [data-vf-layout="comparison"] thead th[scope="col"]');
        self::assertSelectorCount(8, '#comparison-section [data-vf-layout="comparison"] tbody th[scope="row"]');
        self::assertSelectorCount(8, '#comparison-section [data-vf-layout="comparison"] tbody tr');

        self::assertSelectorCount(6, '[data-vf-section="faq"] [data-vf-component="accordion"] > details');
        self::assertSelectorCount(6, '[data-vf-section="faq"] [data-vf-component="accordion"] > details > summary h3');
        self::assertSelectorExists('[data-vf-section="faq"] [data-vf-component="accordion"]');
        self::assertSelectorCount(1, '[data-vf-section="cta"] [data-vf-component="cta"]');
    }

    public function testMarketingSectionBoundaryContractsAreRendered(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-kit/sections');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, '#text-content-stress ul');
        self::assertSelectorCount(3, '#problem-min-stress [data-vf-layout="text-list"] > li');
        self::assertSelectorCount(3, '#grid-three-stress [data-vf-layout="grid"] > li');
        self::assertSelectorCount(6, '#grid-six-stress [data-vf-layout="grid"] > li');
        self::assertSelectorCount(2, '#steps-min-stress [data-vf-layout="steps"] > li');
        self::assertSelectorCount(3, '#comparison-min-stress thead th[scope="col"]');
        self::assertSelectorCount(3, '#comparison-min-stress tbody tr');
        self::assertSelectorExists('#feature-no-media-stress [data-vf-has-media="false"]');
        self::assertSelectorCount(0, '#feature-no-media-stress [data-vf-demo-media], #feature-no-media-stress img');
        self::assertSelectorCount(0, '#feature-no-media-stress [data-vf-component="button"]');
    }

    public function testLeadFormIsAccessibleDemoWithoutSubmission(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-kit/sections');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-vf-demo-form]:not([action])');
        self::assertSelectorCount(2, '[data-vf-demo-form] [data-vf-component="form-input"]');
        self::assertSelectorCount(1, '[data-vf-demo-form] [data-vf-component="textarea"]');
        self::assertSelectorCount(1, '[data-vf-demo-form] [data-vf-component="checkbox"]');
        self::assertSelectorCount(4, '[data-vf-demo-form] label');
        self::assertSelectorCount(0, '[data-vf-demo-form] button[type="submit"]');
        self::assertSelectorCount(1, '[data-vf-demo-form] button[type="button"]');
        self::assertSelectorTextContains('[data-vf-demo-form] [data-vf-form-note]', 'обработчик отправки не подключён');
    }

    public function testOnestIsSelfHostedAndIsTheOnlyPrimaryWebsiteFont(): void
    {
        $font = $this->projectPath('public/assets/fonts/onest/Onest-Variable.woff2');
        $license = $this->projectPath('public/assets/fonts/onest/OFL.txt');

        self::assertFileExists($font);
        self::assertSame('wOF2', substr($this->read($font), 0, 4));
        self::assertGreaterThan(0, filesize($font));
        self::assertFileExists($license);
        self::assertStringContainsString('SIL OPEN FONT LICENSE Version 1.1', $this->read($license));

        $appCss = $this->read($this->projectPath('assets/styles/website/app.css'));
        self::assertStringContainsString('@font-face', $appCss);
        self::assertStringContainsString('font-family: "Onest";', $appCss);
        self::assertStringContainsString('font-style: normal;', $appCss);
        self::assertStringContainsString('font-weight: 100 900;', $appCss);
        self::assertStringContainsString('font-display: swap;', $appCss);
        self::assertStringContainsString('url("/assets/fonts/onest/Onest-Variable.woff2")', $appCss);
        self::assertStringContainsString('--vf-font-primary: "Onest", Arial, sans-serif;', $appCss);

        $activeTypography = $appCss.$this->read($this->siteRulesPath());
        self::assertDoesNotMatchRegularExpression('/TT\s+Norms/i', $activeTypography);
        self::assertDoesNotMatchRegularExpression('/fonts\.googleapis|fonts\.gstatic/i', $activeTypography);
    }

    public function testTypographyStressCasesUseTheProductionScaleWithoutForcedBreaks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-kit/sections');

        self::assertResponseIsSuccessful();
        foreach (['text-h1', 'text-h2', 'text-h3', 'text-h4', 'text-lead', 'text-small', 'text-caption'] as $class) {
            self::assertSelectorExists(sprintf('[data-vf-showcase="typography-stress"] .%s', $class));
        }
        self::assertSelectorCount(3, '[data-vf-showcase="typography-stress"] .text-h1');
        self::assertSelectorCount(2, '[data-vf-showcase="typography-stress"] .text-h2');
        self::assertSelectorCount(2, '[data-vf-showcase="typography-stress"] .text-h3');

        self::assertSelectorTextContains('[data-vf-showcase="typography-stress"]', '₽');
        self::assertSelectorTextContains('[data-vf-showcase="typography-stress"]', '%');
        self::assertSelectorCount(0, '[data-vf-showcase="typography-stress"] br');
    }

    private function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 2).'/'.$relativePath;
    }

    private function siteRulesPath(): string
    {
        $local = dirname(__DIR__, 3).'/SITE_RULES.md';

        return is_file($local) ? $local : '/workspace/SITE_RULES.md';
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertNotFalse($contents, $path);

        return $contents;
    }
}
