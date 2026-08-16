<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebsiteFoundationTest extends WebTestCase
{
    public function testUiKitRendersProductionComponentsAndSections(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-kit');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'UI-kit «Ваш Финдир»');
        foreach (['tokens.css', 'base.css', 'components/index.css', 'sections/index.css', 'components/showcase.css'] as $stylesheet) {
            self::assertSelectorExists(sprintf('link[href^="/assets/website/%s?v="]', $stylesheet));
        }

        foreach ([
            'button',
            'card',
            'badge',
            'alert',
            'form-input',
            'select',
            'textarea',
            'checkbox',
            'accordion',
            'breadcrumb',
            'navbar',
            'footer',
            'cta',
        ] as $component) {
            self::assertSelectorExists(sprintf('[data-vf-component="%s"]', $component));
        }

        foreach (['hero', 'content', 'cta'] as $section) {
            self::assertSelectorExists(sprintf('[data-vf-section="%s"]', $section));
        }

        foreach (['typography', 'colors', 'spacing'] as $showcase) {
            self::assertSelectorExists(sprintf('[data-vf-showcase="%s"]', $showcase));
        }

        foreach (['brand', 'dark', 'neutral', 'semantic'] as $colorGroup) {
            self::assertSelectorExists(sprintf('[data-vf-color-group="%s"]', $colorGroup));
        }

        $documentedColorTokens = $this->colorTokens();
        self::assertSelectorCount(count($documentedColorTokens), '[data-vf-color-token]');
        foreach ($documentedColorTokens as $colorToken => $hex) {
            $selector = sprintf('[data-vf-color-token="%s"]', $colorToken);
            self::assertSelectorExists($selector);
            self::assertSelectorTextContains($selector, strtoupper($hex));
        }

        self::assertSelectorExists('[data-vf-color-combinations] .vf-color-context--light');
        self::assertSelectorExists('[data-vf-color-combinations] .vf-color-context--dark');
        self::assertSelectorExists('[data-vf-color-combinations] .vf-alert--info');
        self::assertSelectorExists('.vf-badge--info');
        self::assertSelectorExists('[data-vf-state="active"].vf-is-active');

        self::assertSelectorExists('label[for="demo-name"]');
        self::assertSelectorExists('[data-vf-state="error"] [aria-invalid="true"]');
        self::assertSelectorExists('[data-vf-state="success"] .valid-feedback');

        foreach (['select', 'textarea', 'checkbox'] as $component) {
            foreach (['default', 'focus', 'error', 'success'] as $state) {
                self::assertSelectorExists(sprintf(
                    '[data-vf-component="%s"][data-vf-state="%s"]',
                    $component,
                    $state,
                ));
            }

            self::assertSelectorExists(sprintf('[data-vf-component="%s"] :disabled', $component));
        }
    }

    public function testWebsiteTwigDoesNotContainInlineCssOrJavascript(): void
    {
        $templates = $this->projectPath('templates/website');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templates));
        $checked = 0;

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || 'twig' !== $file->getExtension()) {
                continue;
            }

            $contents = $this->read($file->getPathname());

            self::assertDoesNotMatchRegularExpression('/<style\b/i', $contents, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $contents, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/<script\b(?![^>]*\bsrc\s*=)[^>]*>/i', $contents, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $contents, $file->getPathname());
            self::assertDoesNotMatchRegularExpression(
                '/(?<!vf-)\b(?:text|bg|border)-(?:primary|secondary|success|danger|warning|info|light|dark|white|black|body|muted)\b/i',
                $contents,
                $file->getPathname(),
            );
            ++$checked;
        }

        self::assertGreaterThan(0, $checked);
    }

    public function testComponentAndSectionCssUseTokensForColors(): void
    {
        foreach ($this->websiteCssFiles('assets/styles/website') as $relativePath => $path) {
            if ('tokens.css' === $relativePath) {
                continue;
            }

            self::assertDoesNotMatchRegularExpression(
                '/#[0-9a-f]{3,8}\b|\b(?:rgb|hsl|oklch)a?\s*\(|\b(?:color-mix|light-dark)\s*\(|\b(?:white|black|red|green|blue|gray|grey|pink|orange|yellow|purple)\b|var\(--bs-/i',
                $this->read($path),
                $relativePath,
            );
            self::assertDoesNotMatchRegularExpression(
                '/^\s*(?:color|background(?:-color)?|border(?:-[a-z-]+)?-color|outline-color|fill|stroke)\s*:\s*(?!var\(|currentcolor\b|transparent\b|none\b|inherit\b|initial\b|unset\b)[a-z-]+\b/im',
                $this->read($path),
                $relativePath,
            );
        }
    }

    public function testComponentAndSectionCssDoNotDefineArbitraryPixelValues(): void
    {
        foreach ($this->websiteCssFiles('assets/styles/website') as $relativePath => $path) {
            if ('tokens.css' === $relativePath) {
                continue;
            }

            foreach (explode("\n", $this->read($path)) as $line) {
                if (1 === preg_match('/^\s*@media\s+\((?:min|max)-width:\s*(?:575\.98|767\.98|991\.98|1199\.98|1399\.98|576|768|992|1200|1400)px\)(?:\s+and\s+\((?:min|max)-width:\s*(?:575\.98|767\.98|991\.98|1199\.98|1399\.98|576|768|992|1200|1400)px\))?\s*\{\s*$/', $line)) {
                    continue;
                }

                self::assertDoesNotMatchRegularExpression(
                    '/\b\d+(?:\.\d+)?(?:px|rem|em|vh|vw|vmin|vmax|ch|ex)\b/',
                    $line,
                    $relativePath,
                );
            }
        }
    }

    public function testPublishedAssetsMatchTheirSource(): void
    {
        $sourceFiles = $this->websiteCssFiles('assets/styles/website');
        $publishedFiles = $this->websiteCssFiles('public/assets/website');

        self::assertSame(array_keys($sourceFiles), array_keys($publishedFiles));

        foreach ($sourceFiles as $relativePath => $sourcePath) {
            self::assertSame(
                $this->read($sourcePath),
                $this->read($publishedFiles[$relativePath]),
                $relativePath,
            );
        }
    }

    public function testColorSystemTokensAndContrast(): void
    {
        $colors = $this->colorTokens();
        $expectedColors = [
            '--vf-color-primary' => '#b00020',
            '--vf-color-primary-hover' => '#8a0019',
            '--vf-color-primary-active' => '#6c0014',
            '--vf-color-primary-soft' => '#f7e6e9',
            '--vf-color-dark' => '#0b1020',
            '--vf-color-surface-dark' => '#0b1020',
            '--vf-color-background' => '#f5f6f8',
            '--vf-color-surface' => '#ffffff',
            '--vf-color-text' => '#0b1020',
            '--vf-color-muted' => '#5c677d',
            '--vf-color-text-on-dark' => '#ffffff',
            '--vf-color-muted-on-dark' => '#8e99af',
            '--vf-color-on-primary' => '#ffffff',
            '--vf-color-focus' => '#b00020',
            '--vf-color-focus-on-dark' => '#ffffff',
            '--vf-color-border' => '#d9dce4',
            '--vf-color-border-strong' => '#767b85',
            '--vf-color-success' => '#146c43',
            '--vf-color-success-soft' => '#e9f5ee',
            '--vf-color-warning' => '#805b00',
            '--vf-color-warning-soft' => '#fff3cd',
            '--vf-color-danger' => '#b02a37',
            '--vf-color-danger-soft' => '#f8d7da',
            '--vf-color-info' => '#0b5cad',
            '--vf-color-info-soft' => '#e8f1fb',
        ];

        foreach ($expectedColors as $token => $hex) {
            self::assertSame($hex, $colors[$token] ?? null, $token);
        }

        $sortedColors = $colors;
        ksort($sortedColors, SORT_STRING);
        $sortedExpectedColors = $expectedColors;
        ksort($sortedExpectedColors, SORT_STRING);
        self::assertSame($sortedExpectedColors, $sortedColors);

        $contrastChecks = [
            ['--vf-color-text', '--vf-color-background', 4.5],
            ['--vf-color-muted', '--vf-color-background', 4.5],
            ['--vf-color-text', '--vf-color-surface', 4.5],
            ['--vf-color-muted', '--vf-color-surface', 4.5],
            ['--vf-color-text-on-dark', '--vf-color-surface-dark', 4.5],
            ['--vf-color-muted-on-dark', '--vf-color-surface-dark', 4.5],
            ['--vf-color-focus-on-dark', '--vf-color-surface-dark', 3.0],
            ['--vf-color-on-primary', '--vf-color-primary', 4.5],
            ['--vf-color-on-primary', '--vf-color-primary-hover', 4.5],
            ['--vf-color-on-primary', '--vf-color-primary-active', 4.5],
            ['--vf-color-focus', '--vf-color-surface', 3.0],
            ['--vf-color-border-strong', '--vf-color-surface', 3.0],
            ['--vf-color-success', '--vf-color-success-soft', 4.5],
            ['--vf-color-warning', '--vf-color-warning-soft', 4.5],
            ['--vf-color-danger', '--vf-color-danger-soft', 4.5],
            ['--vf-color-info', '--vf-color-info-soft', 4.5],
            ['--vf-color-success', '--vf-color-surface', 4.5],
            ['--vf-color-warning', '--vf-color-surface', 4.5],
            ['--vf-color-danger', '--vf-color-surface', 4.5],
            ['--vf-color-info', '--vf-color-surface', 4.5],
            ['--vf-color-text', '--vf-color-primary-soft', 4.5],
            ['--vf-color-primary-hover', '--vf-color-primary-soft', 4.5],
            ['--vf-color-primary-active', '--vf-color-primary-soft', 4.5],
        ];

        foreach ($contrastChecks as [$foreground, $background, $minimum]) {
            self::assertGreaterThanOrEqual(
                $minimum,
                $this->contrastRatio($colors[$foreground], $colors[$background]),
                $foreground.' / '.$background,
            );
        }

        self::assertLessThan(
            4.5,
            $this->contrastRatio($colors['--vf-color-primary'], $colors['--vf-color-dark']),
            'Brand Red is forbidden as normal text on Brand Dark.',
        );
        self::assertGreaterThan(
            $this->relativeLuminance($colors['--vf-color-primary-hover']),
            $this->relativeLuminance($colors['--vf-color-primary']),
        );
        self::assertGreaterThan(
            $this->relativeLuminance($colors['--vf-color-primary-active']),
            $this->relativeLuminance($colors['--vf-color-primary-hover']),
        );

        $siteRulesColors = $this->documentedSiteRulesColors();
        self::assertSame($sortedExpectedColors, $siteRulesColors);

        $tokensCss = $this->read($this->projectPath('assets/styles/website/tokens.css'));
        self::assertSame(
            1,
            preg_match('/--vf-color-primary-rgb:\s*(\d+),\s*(\d+),\s*(\d+);/', $tokensCss, $primaryRgb),
        );
        self::assertSame(
            $this->hexChannels($colors['--vf-color-primary']),
            array_map('intval', array_slice($primaryRgb, 1, 3)),
        );
        self::assertSame(
            1,
            preg_match('/--vf-shadow-card:[^;]*rgb\((\d+)\s+(\d+)\s+(\d+)\s*\/\s*8%\);/', $tokensCss, $shadowRgb),
        );
        self::assertSame(
            $this->hexChannels($colors['--vf-color-text']),
            array_map('intval', array_slice($shadowRgb, 1, 3)),
        );

        $componentsCss = $this->read($this->projectPath('assets/styles/website/components/index.css'));
        foreach (['.btn-primary', '.btn-outline-primary', '.vf-btn-on-primary'] as $buttonSelector) {
            self::assertStringContainsString($buttonSelector.":active,\n".$buttonSelector.'.vf-is-active', $componentsCss);
            $hoverPosition = strpos($componentsCss, $buttonSelector.':hover,');
            $activePosition = strpos($componentsCss, $buttonSelector.":active,\n");
            self::assertNotFalse($hoverPosition);
            self::assertNotFalse($activePosition);
            self::assertGreaterThan($hoverPosition, $activePosition);
        }
        self::assertStringContainsString('--bs-btn-active-color: var(--vf-color-on-primary);', $componentsCss);
        self::assertStringContainsString('--bs-btn-active-bg: var(--vf-color-on-primary);', $componentsCss);
        self::assertStringContainsString(
            ".vf-btn-on-primary:active,\n.vf-btn-on-primary.vf-is-active {\n    background: var(--vf-color-on-primary);",
            $componentsCss,
        );

        $sectionsCss = $this->read($this->projectPath('assets/styles/website/sections/index.css'));
        self::assertStringContainsString(
            ".vf-section--dark :focus-visible,\n.vf-section--dark .vf-is-focus {\n    outline-color: var(--vf-color-focus-on-dark);",
            $sectionsCss,
        );
        self::assertStringContainsString(
            ".vf-section--dark .vf-text-muted {\n    color: var(--vf-color-muted-on-dark);",
            $sectionsCss,
        );
        self::assertStringContainsString(
            ".vf-section--dark :is(a, code) {\n    color: var(--vf-color-text-on-dark);",
            $sectionsCss,
        );

        self::assertLessThan(
            4.5,
            $this->contrastRatio($colors['--vf-color-muted-on-dark'], $colors['--vf-color-surface']),
            'Muted on Dark is forbidden on a light Surface.',
        );
        self::assertLessThan(
            4.5,
            $this->contrastRatio($colors['--vf-color-text-on-dark'], $colors['--vf-color-surface']),
            'Text on Dark is forbidden on a light Surface.',
        );
    }

    public function testAssetVersionMatchesWebsiteCssContent(): void
    {
        $css = '';
        foreach ($this->websiteCssFiles('assets/styles/website') as $path) {
            $css .= $this->read($path);
        }

        $layout = $this->read($this->projectPath('templates/website/layouts/base.html.twig'));
        self::assertSame(1, preg_match("/{% set vf_asset_version = '([0-9a-f]{12})' %}/", $layout, $matches));
        $assetVersion = $matches[1] ?? '';
        self::assertNotSame('', $assetVersion);
        self::assertSame(substr(hash('sha256', $css), 0, 12), $assetVersion);
    }

    /** @return array<string, string> */
    private function websiteCssFiles(string $relativeDirectory): array
    {
        $directory = $this->projectPath($relativeDirectory);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        $cssFiles = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || 'css' !== $file->getExtension()) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            $cssFiles[$relativePath] = $file->getPathname();
        }

        ksort($cssFiles, SORT_STRING);

        return $cssFiles;
    }

    /** @return array<string, string> */
    private function colorTokens(): array
    {
        $css = $this->read($this->projectPath('assets/styles/website/tokens.css'));
        $result = preg_match_all(
            '/^\s*(--vf-color-[a-z0-9-]+):\s*(#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8}));/mi',
            $css,
            $matches,
            PREG_SET_ORDER,
        );
        self::assertNotFalse($result);

        $colors = [];
        foreach ($matches as $match) {
            $colors[$match[1]] = strtolower($match[2]);
        }

        return $colors;
    }

    /** @return array<string, string> */
    private function documentedSiteRulesColors(): array
    {
        $siteRulesPath = dirname(__DIR__, 3).'/SITE_RULES.md';
        if (!is_file($siteRulesPath)) {
            $siteRulesPath = '/workspace/SITE_RULES.md';
        }

        $siteRules = $this->read($siteRulesPath);
        $result = preg_match_all(
            '/`(--vf-color-[a-z0-9-]+)`(?:\s*\|\s*|\s+)`(#[0-9a-f]{6})`/i',
            $siteRules,
            $matches,
            PREG_SET_ORDER,
        );
        self::assertNotFalse($result);

        $colors = [];
        foreach ($matches as $match) {
            $colors[$match[1]] = strtolower($match[2]);
        }
        ksort($colors, SORT_STRING);

        return $colors;
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $foregroundLuminance = $this->relativeLuminance($foreground);
        $backgroundLuminance = $this->relativeLuminance($background);

        return (max($foregroundLuminance, $backgroundLuminance) + 0.05)
            / (min($foregroundLuminance, $backgroundLuminance) + 0.05);
    }

    /** @return list<int> */
    private function hexChannels(string $hex): array
    {
        return array_map(
            static fn (string $channel): int => (int) hexdec($channel),
            str_split(ltrim($hex, '#'), 2),
        );
    }

    private function relativeLuminance(string $hex): float
    {
        $channels = array_map(
            static fn (string $channel): float => hexdec($channel) / 255,
            str_split(ltrim($hex, '#'), 2),
        );
        $linear = array_map(
            static fn (float $channel): float => $channel <= 0.04045
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
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
