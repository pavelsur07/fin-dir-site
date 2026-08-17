<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebsiteFoundationTest extends WebTestCase
{
    public function testUiKitRendersProductionComponentsAndSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/ui-kit');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'UI-kit «Ваш Финдир»');
        self::assertSelectorCount(1, 'link[href^="/assets/website/app.css?v="]');
        self::assertSelectorCount(1, 'script[src^="/assets/website/navigation.js?v="][defer]');
        self::assertSelectorCount(0, 'link[href*="bootstrap"], script[src*="bootstrap"]');

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

        self::assertSelectorExists('[data-vf-color-combinations] [data-vf-color-context="light"]');
        self::assertSelectorExists('[data-vf-color-combinations] [data-vf-color-context="dark"]');
        self::assertSelectorExists('[data-vf-color-combinations] [data-vf-component="alert"][data-vf-tone="info"]');
        self::assertSelectorExists('[data-vf-component="badge"][data-vf-tone="info"]');
        self::assertSelectorExists('[data-vf-state="active"].bg-brand-red-active');
        self::assertSelectorExists('section[data-vf-section="hero"].bg-brand-dark [data-vf-variant="dark"]');
        self::assertSelectorExists('footer.bg-surface-dark[data-vf-variant="dark"]');

        self::assertSelectorExists('label[for="demo-name"]');
        self::assertSelectorExists('[data-vf-state="error"] [aria-invalid="true"]');
        self::assertSelectorExists('[data-vf-state="success"] .text-success');
        self::assertSelectorExists('[data-vf-component="accordion"] details > summary h3');
        self::assertSelectorCount(0, '[data-vf-component="accordion"] [role="heading"]');
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-open][aria-label="Открыть меню"][aria-haspopup="dialog"][aria-controls][aria-expanded="false"]');
        self::assertSelectorExists('[data-vf-component="navbar"] dialog[data-vf-menu-dialog][aria-label="Меню"]');
        self::assertSelectorExists('[data-vf-component="navbar"] button[data-vf-menu-close][aria-label="Закрыть меню"]');
        self::assertSelectorExists('[data-vf-component="navbar"] nav[data-vf-mobile-navigation]');
        self::assertSelectorExists('[data-vf-component="navbar"] nav[aria-label="Основная навигация"] [data-vf-desktop-navigation]');
        self::assertSelectorExists('[data-vf-component="navbar"] a[aria-current="page"]');
        self::assertSelectorCount(0, '[data-vf-component="navbar"] details, [data-vf-component="navbar"] summary');

        $drawerId = $crawler->filter('[data-vf-menu-open]')->attr('aria-controls');
        self::assertNotNull($drawerId);
        self::assertNotSame('', $drawerId);
        self::assertCount(1, $crawler->filter('dialog#'.$drawerId));

        foreach ([
            'input' => 'form-input',
            'select' => 'select',
            'textarea' => 'textarea',
            'checkbox' => 'checkbox',
        ] as $group => $component) {
            $groupSelector = sprintf('[data-vf-form-group="%s"]', $group);
            self::assertSelectorCount(5, $groupSelector.' [data-vf-component="'.$component.'"]');
            foreach (['default', 'focus', 'disabled', 'error', 'success'] as $state) {
                self::assertSelectorExists(sprintf(
                    '%s [data-vf-component="%s"][data-vf-state="%s"]',
                    $groupSelector,
                    $component,
                    $state,
                ));
            }

            self::assertSelectorExists(sprintf(
                '%s [data-vf-component="%s"][data-vf-state="disabled"] :disabled',
                $groupSelector,
                $component,
            ));
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
            self::assertDoesNotMatchRegularExpression('/bootstrap|data-bs-|--bs-/i', $contents, $file->getPathname());
            self::assertDoesNotMatchRegularExpression(
                '/\b(?:bg|text|border)-(?:red|blue|slate|gray|grey|zinc|neutral|stone)-\d{2,3}\b/i',
                $contents,
                $file->getPathname(),
            );
            ++$checked;
        }

        self::assertGreaterThan(0, $checked);
    }

    public function testTailwindTemplatesUseStaticApprovedUtilities(): void
    {
        $checked = 0;
        foreach ($this->websiteTemplateFiles() as $path) {
            $contents = $this->read($path);

            self::assertDoesNotMatchRegularExpression(
                '/\b[a-z][a-z0-9-]*-\[[^\]\r\n]+\]/i',
                $contents,
                $path,
            );
            self::assertDoesNotMatchRegularExpression(
                '/\b(?:bg|text|border|outline|ring|fill|stroke)-\s*(?:\{\{|[\'\"]\s*~)/i',
                $contents,
                $path,
            );

            $count = preg_match_all(
                '/(?<![a-z0-9])(?:-)?(?:[a-z0-9-]+:)*(?:p[trblxy]?|m[trblxy]?|gap[xy]?|space-[xy]|w|h|size|inset|top|right|bottom|left|translate-[xy])-(\d+)(?:\/\d+)?\b/i',
                $contents,
                $matches,
            );
            self::assertNotFalse($count);
            foreach ($matches[1] as $spacing) {
                self::assertContains((int) $spacing, [0, 1, 2, 3, 4, 6, 8, 12, 16, 20, 24], $path);
            }

            ++$checked;
        }

        self::assertGreaterThan(0, $checked);
    }

    public function testWebsiteAssetsArePinnedAndPublishedFromProjectSources(): void
    {
        $source = $this->read($this->projectPath('assets/styles/website/app.css'));
        $compiled = $this->read($this->projectPath('public/assets/website/app.css'));
        $navigationSource = $this->read($this->projectPath('assets/scripts/website/navigation.js'));
        $navigationPublic = $this->read($this->projectPath('public/assets/website/navigation.js'));
        $wrapperPath = dirname(__DIR__, 3).'/scripts/tailwindcss.sh';
        if (!is_file($wrapperPath)) {
            $wrapperPath = '/workspace/scripts/tailwindcss.sh';
        }
        $wrapper = $this->read($wrapperPath);

        self::assertStringContainsString('@import "tailwindcss" source(none);', $source);
        self::assertStringContainsString('@source "../../../templates/website";', $source);
        self::assertStringContainsString('--color-*: initial;', $source);
        self::assertDoesNotMatchRegularExpression('/bootstrap|data-bs-|--bs-/i', $source);
        self::assertStringNotContainsString('@import "tailwindcss"', $compiled);
        self::assertStringContainsString('.bg-brand-red', $compiled);
        self::assertStringContainsString('.grid-auto-fit', $compiled);
        self::assertStringContainsString('.navigation-drawer-width', $compiled);
        self::assertStringContainsString("TAILWIND_VERSION='4.3.3'", $wrapper);
        self::assertStringContainsString('dc61b3ac6b8c9ca874c0cc4c57b2409791a64c5540404ca5f5367360babc313a', $wrapper);
        self::assertSame($navigationSource, $navigationPublic);
        self::assertDoesNotMatchRegularExpression('/bootstrap|data-bs-|flowbite|daisyui|alpine|react|vue/i', $navigationSource);

        $withoutColorTokens = preg_replace(
            '/^\s*--vf-color-[a-z0-9-]+:\s*#[0-9a-f]{6};\R?/mi',
            '',
            $source,
        );
        self::assertIsString($withoutColorTokens);
        self::assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', $withoutColorTokens);

        $published = glob($this->projectPath('public/assets/website/*'));
        self::assertIsArray($published);
        self::assertSame([
            $this->projectPath('public/assets/website/app.css'),
            $this->projectPath('public/assets/website/navigation.js'),
        ], $published);
    }

    public function testDocumentScrollbarUsesBrowserDefaults(): void
    {
        $sourceFiles = [
            $this->projectPath('assets/styles/website/app.css'),
            $this->projectPath('assets/scripts/website/navigation.js'),
            $this->projectPath('public/assets/website/app.css'),
            $this->projectPath('public/assets/website/navigation.js'),
            ...$this->websiteTemplateFiles(),
        ];
        $forbidden = '/::-webkit-scrollbar(?:-track|-thumb)?|scrollbar-(?:color|width|gutter)|overflow-y\s*:\s*scroll\b|color-scheme\s*:/i';

        foreach ($sourceFiles as $path) {
            $contents = $this->read($path);
            self::assertDoesNotMatchRegularExpression($forbidden, $contents, $path);
            self::assertStringNotContainsString('--vf-scrollbar-compensation', $contents, $path);
        }

        $javascript = $this->read($this->projectPath('assets/scripts/website/navigation.js'));
        self::assertDoesNotMatchRegularExpression(
            '/(?:document\.documentElement|document\.body)\.style\b|classList\.(?:add|toggle)\(|scrollbar/i',
            $javascript,
        );
        self::assertDoesNotMatchRegularExpression(
            '/(?:html|body)[^{]*\{[^}]*overflow(?:-[xy])?\s*:\s*hidden/i',
            $this->read($this->projectPath('assets/styles/website/app.css')),
        );

        $siteRules = $this->read($this->siteRulesPath());
        self::assertStringContainsString(
            'Public Website MUST use the browser/OS native scrollbar for document scrolling.',
            $siteRules,
        );
        foreach ([
            'NO custom document scrollbar',
            'NO custom scrollbar track color',
            'NO forced scrollbar width',
            'NO fake scrollbar gutter',
        ] as $rule) {
            self::assertStringContainsString($rule, $siteRules);
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
            '--vf-color-surface-dark' => '#1e2331',
            '--vf-color-background' => '#f5f6f8',
            '--vf-color-surface' => '#ffffff',
            '--vf-color-text' => '#0b1020',
            '--vf-color-muted' => '#5c677d',
            '--vf-color-text-on-dark' => '#ffffff',
            '--vf-color-muted-on-dark' => '#8e99af',
            '--vf-color-on-primary' => '#ffffff',
            '--vf-color-focus' => '#005fcc',
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
            ['--vf-color-text-on-dark', '--vf-color-dark', 4.5],
            ['--vf-color-muted-on-dark', '--vf-color-dark', 4.5],
            ['--vf-color-focus-on-dark', '--vf-color-dark', 3.0],
            ['--vf-color-on-primary', '--vf-color-primary', 4.5],
            ['--vf-color-on-primary', '--vf-color-primary-hover', 4.5],
            ['--vf-color-on-primary', '--vf-color-primary-active', 4.5],
            ['--vf-color-focus', '--vf-color-surface', 3.0],
            ['--vf-color-focus', '--vf-color-background', 3.0],
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
        self::assertNotSame($colors['--vf-color-dark'], $colors['--vf-color-surface-dark']);
        self::assertNotSame($colors['--vf-color-focus'], $colors['--vf-color-danger']);
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

        $tokensCss = $this->read($this->projectPath('assets/styles/website/app.css'));
        self::assertSame(
            1,
            preg_match('/--vf-shadow-card:[^;]*rgb\((\d+)\s+(\d+)\s+(\d+)\s*\/\s*8%\);/', $tokensCss, $shadowRgb),
        );
        self::assertSame(
            $this->hexChannels($colors['--vf-color-text']),
            array_map('intval', array_slice($shadowRgb, 1, 3)),
        );

        $button = $this->read($this->projectPath('templates/website/components/_button.html.twig'));
        self::assertStringContainsString('bg-brand-red-hover', $button);
        self::assertStringContainsString('bg-brand-red-active', $button);
        self::assertStringContainsString('outline-focus-on-dark', $button);
        $form = $this->read($this->projectPath('templates/website/components/_form_input.html.twig'));
        self::assertStringContainsString('border-focus', $form);
        self::assertStringContainsString('border-danger focus:border-danger', $form);
        self::assertStringContainsString('border-success focus:border-success', $form);
        self::assertStringContainsString('bg-brand-dark', $this->read($this->projectPath('templates/website/sections/_hero.html.twig')));
        self::assertStringContainsString('bg-surface-dark', $this->read($this->projectPath('templates/website/components/_footer.html.twig')));

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

    public function testAssetVersionMatchesWebsiteAssetContent(): void
    {
        $css = $this->read($this->projectPath('public/assets/website/app.css'));
        $navigation = $this->read($this->projectPath('public/assets/website/navigation.js'));

        $layout = $this->read($this->projectPath('templates/website/layouts/base.html.twig'));
        self::assertSame(1, preg_match("/{% set vf_asset_version = '([0-9a-f]{12})' %}/", $layout, $matches));
        $assetVersion = $matches[1] ?? '';
        self::assertNotSame('', $assetVersion);
        self::assertSame(substr(hash('sha256', $css.$navigation), 0, 12), $assetVersion);
    }

    /** @return list<string> */
    private function websiteTemplateFiles(): array
    {
        $directory = $this->projectPath('templates/website');
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        $templates = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || 'twig' !== $file->getExtension()) {
                continue;
            }

            $templates[] = $file->getPathname();
        }

        sort($templates, SORT_STRING);

        return $templates;
    }

    /** @return array<string, string> */
    private function colorTokens(): array
    {
        $css = $this->read($this->projectPath('assets/styles/website/app.css'));
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
        $siteRules = $this->read($this->siteRulesPath());
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
