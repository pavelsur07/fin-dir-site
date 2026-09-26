<?php

declare(strict_types=1);

namespace App\Tests\Website;

use PHPUnit\Framework\TestCase;

final class TailwindOnlyTest extends TestCase
{
    public function testApplicationUsesOnlyUnmodifiedTailwind(): void
    {
        $root = dirname(__DIR__, 2);
        $css = (string) file_get_contents($root.'/assets/styles/website/app.css');
        self::assertSame("@import \"tailwindcss\" source(none);\n@source \"../../../templates/website\";\n@source \"../../../templates/admin\";\n@source \"../../../assets/scripts/website\";\n@source \"../../../src/Publication/Adapter\";\n", $css);
        self::assertFileDoesNotExist($root.'/public/assets/admin/admin.css');
        self::assertFileDoesNotExist($root.'/public/assets/admin/admin.js');
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
