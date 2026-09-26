<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MarketingSectionsTest extends WebTestCase
{
    public function testWebsiteDoesNotRegisterCustomFonts(): void
    {
        $appCss = $this->read($this->projectPath('assets/styles/website/app.css'));
        self::assertStringNotContainsString('@font-face', $appCss);
        self::assertStringNotContainsString('fonts.googleapis', $appCss);
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
