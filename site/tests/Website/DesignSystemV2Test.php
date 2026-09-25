<?php

declare(strict_types=1);

namespace App\Tests\Website;

use PHPUnit\Framework\TestCase;

final class DesignSystemV2Test extends TestCase
{
    public function testUiKitNavigationHasAllFourReferenceLevels(): void
    {
        $root = dirname(__DIR__, 2);
        $page = file_get_contents($root.'/templates/website/pages/ui_kit.html.twig');
        self::assertIsString($page);
        self::assertStringContainsString('_ui_kit_navigation.html.twig', $page);
        self::assertStringContainsString('ui-kit-navigation.js', $page);

        $navigation = file_get_contents($root.'/templates/website/pages/_ui_kit_navigation.html.twig');
        self::assertIsString($navigation);
        foreach (['16.1 · Основное меню сайта', '16.2 · Меню компании и пользователя', '16.3 · Каркас кабинета', '16.4 · Хлебные крошки сайта'] as $heading) {
            self::assertStringContainsString($heading, $navigation);
        }
        foreach (['features', 'audiences', 'mobile-features', 'mobile-audiences', 'company', 'user', 'crumbs'] as $panel) {
            self::assertStringContainsString('id="vf-ui-nav-'.$panel.'"', $navigation);
            self::assertStringContainsString('aria-controls="vf-ui-nav-'.$panel.'"', $navigation);
        }
        self::assertStringNotContainsString('<h1', $navigation);
        self::assertStringNotContainsString('href="#"', $navigation);
    }

    public function testLightThemeTextAndControlBoundariesMeetContrastThresholds(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/assets/styles/website/app.css');
        self::assertIsString($css);
        $lightTheme = strstr($css, '[data-theme="dark"]', true);
        self::assertIsString($lightTheme);
        preg_match_all('/(--vf-[a-z0-9-]+):\s*(#[0-9a-f]{6}|var\(--vf-[a-z0-9-]+\));/i', $lightTheme, $matches, PREG_SET_ORDER);
        $tokens = [];
        foreach ($matches as $match) {
            $tokens[$match[1]] = $match[2];
        }
        $resolve = static function (string $token) use (&$resolve, $tokens): string {
            $value = $tokens[$token] ?? '';
            if (str_starts_with($value, 'var(')) {
                return $resolve(substr($value, 4, -1));
            }

            return $value;
        };
        $contrast = static function (string $first, string $second, \Closure $resolver): float {
            $luminance = static function (string $hex): float {
                self::assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $hex);
                $rgb = array_map(static fn (string $part): float => hexdec($part) / 255, str_split(substr($hex, 1), 2));
                $linear = array_map(static fn (float $channel): float => $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4, $rgb);

                return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
            };
            $a = $luminance($resolver('--vf-color-'.$first));
            $b = $luminance($resolver('--vf-color-'.$second));

            return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
        };
        foreach ([['fg', 'bg'], ['fg-secondary', 'bg'], ['accent-text', 'bg'], ['fg-on-accent', 'accent'], ['success', 'success-bg'], ['warning', 'warning-bg'], ['error', 'error-bg'], ['info', 'info-bg']] as [$fg, $bg]) {
            self::assertGreaterThanOrEqual(4.5, $contrast($fg, $bg, $resolve), "$fg on $bg");
        }
        self::assertGreaterThanOrEqual(3.0, $contrast('border-strong', 'bg', $resolve), 'Light control boundary');

        self::assertSame(1, preg_match('/\[data-theme="dark"\]\s*\{([^}]+)\}/s', $css, $darkBlock));
        preg_match_all('/(--vf-[a-z0-9-]+):\s*(#[0-9a-f]{6}|var\(--vf-[a-z0-9-]+\));/i', $darkBlock[1] ?? '', $darkMatches, PREG_SET_ORDER);
        foreach ($darkMatches as $match) {
            $tokens[$match[1]] = $match[2];
        }
        $resolveDark = static function (string $token) use (&$resolveDark, $tokens): string {
            $value = $tokens[$token] ?? '';

            return str_starts_with($value, 'var(') ? $resolveDark(substr($value, 4, -1)) : $value;
        };
        self::assertGreaterThanOrEqual(3.0, $contrast('border-strong', 'bg', $resolveDark), 'Dark control boundary');
        foreach (['accent', 'accent-hover', 'accent-active'] as $background) {
            self::assertGreaterThanOrEqual(4.5, $contrast('fg-on-accent', $background, $resolveDark), "Dark button text on $background");
        }
    }

    public function testRuntimeSourcesContainOnlyV2Foundations(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root.'/assets/styles/website/app.css');
        self::assertIsString($css);
        self::assertStringContainsString('--vf-color-bg:', $css);
        self::assertStringContainsString('--vf-color-accent:', $css);
        self::assertStringContainsString('font-family: "Inter"', $css);
        self::assertStringContainsString('font-family: "Manrope"', $css);
        $withoutTokenDeclarations = preg_replace('/^\s*--vf-[a-z0-9-]+:\s*#[0-9a-f]{6};\R?/mi', '', $css);
        self::assertIsString($withoutTokenDeclarations);
        self::assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', $withoutTokenDeclarations);

        $sources = [$root.'/assets/styles/website/app.css'];
        foreach (glob($root.'/assets/scripts/website/*.js') ?: [] as $script) {
            $sources[] = $script;
        }
        $templates = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/templates/website'));
        foreach ($templates as $file) {
            if ($file->isFile() && 'twig' === $file->getExtension()) {
                $sources[] = $file->getPathname();
            }
        }

        foreach ($sources as $source) {
            $content = file_get_contents($source);
            self::assertIsString($content);
            self::assertDoesNotMatchRegularExpression('/Onest|(?<![\w-])(?:[a-z][a-z0-9-]*:)*(?:bg|text|border|outline|accent|tracking|shadow|rounded)-(?:brand-(?:red(?:-hover|-active|-soft)?|dark)|surface(?:-dark)?|page|content|muted(?:-on-dark)?|on-dark|on-primary|danger(?:-soft)?|(?:success|warning|info)-soft|focus-on-dark|border-default|h[1-4]|lead|caption|body|small|card|site)(?![\w-])/', $content, $source);
            self::assertDoesNotMatchRegularExpression('/bootstrap|\b(?:[a-z][a-z0-9-]*:)*[a-z][a-z0-9-]*-\[[^\]]+\]/i', $content, $source);
            self::assertDoesNotMatchRegularExpression('/::-webkit-scrollbar|scrollbar-(?:color|width|gutter)|overflow-y\s*:\s*scroll\b|color-scheme\s*:/i', $content, $source);
            if (!str_ends_with($source, '/_json_ld.html.twig')) {
                self::assertDoesNotMatchRegularExpression('/<script\b(?![^>]*\bsrc=)/i', $content, $source);
            }
            if (str_ends_with($source, '.twig')) {
                self::assertDoesNotMatchRegularExpression('/\s(?:style|on[a-z]+)\s*=|<style\b/i', $content, $source);
                self::assertDoesNotMatchRegularExpression('/\b(?:bg|text|border|outline)-\{\{|\b(?:bg|text|border|outline)-[\x27"]\s*~/', $content, $source);
                preg_match_all('/(?<![a-z0-9])(?:-)?(?:[a-z0-9-]+:)*(?:p[trblxy]?|m[trblxy]?|gap[xy]?|space-[xy]|w|h|size|inset|top|right|bottom|left|translate-[xy])-(\d+)(?:\/\d+)?\b/i', $content, $spacing);
                foreach ($spacing[1] as $step) {
                    self::assertContains((int) $step, [0, 1, 2, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24], $source);
                }
            }
        }

        $stylesheets = glob($root.'/assets/styles/website/*.css');
        self::assertIsArray($stylesheets);
        self::assertCount(1, $stylesheets);
        self::assertStringContainsString('@source "../../../templates/website";', $css);
        self::assertSame(['analytics.js', 'app.css', 'metrika.js', 'navigation.js', 'ui-kit-logo.js', 'ui-kit-navigation.js'], array_map('basename', glob($root.'/public/assets/website/*') ?: []));
    }
}
