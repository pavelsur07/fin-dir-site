<?php

declare(strict_types=1);

namespace App\Tests\Website;

use PHPUnit\Framework\TestCase;

final class TailwindOnlyTest extends TestCase
{
    public function testApplicationUsesApprovedDesignSystemThemes(): void
    {
        $root = dirname(__DIR__, 2);
        $css = (string) file_get_contents($root.'/assets/styles/website/app.css');
        self::assertStringStartsWith('@import "tailwindcss" source(none);', $css);
        self::assertStringContainsString('../shared/vf-palette.css', $css);
        foreach (['templates/website', 'assets/scripts/website', 'src/Publication/Adapter'] as $source) {
            self::assertStringContainsString($source, $css);
        }
        self::assertStringNotContainsString('templates/admin', $css);
        self::assertStringNotContainsString('assets/scripts/admin', $css);
        self::assertFileDoesNotExist($root.'/assets/scripts/website/ui-kit-logo.js');

        foreach ([$root.'/templates/website', $root.'/templates/admin'] as $directory) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                if (!$file->isFile() || 'twig' !== $file->getExtension()) {
                    continue;
                }

                $content = (string) file_get_contents($file->getPathname());
                self::assertDoesNotMatchRegularExpression('/(?:\sstyle\s*=|<style\b)/', $content, $file->getPathname());
                preg_match_all('/\bclass\s*=\s*(["\'])(.*?)\1/s', $content, $classes);
                foreach ($classes[2] as $class) {
                    self::assertDoesNotMatchRegularExpression('/(?:^|\s)vf-[a-z0-9-]+/', $class, $file->getPathname());
                }
            }
        }
    }
}
