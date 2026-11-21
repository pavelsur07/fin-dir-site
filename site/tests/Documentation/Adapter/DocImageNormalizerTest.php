<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Adapter;

use App\Documentation\Adapter\DocImageNormalizer;
use App\Documentation\Exception\DocConflict;
use PHPUnit\Framework\TestCase;

final class DocImageNormalizerTest extends TestCase
{
    public function testDimensionAndPixelLimitsApplyBeforeDecode(): void
    {
        $normalizer = new DocImageNormalizer();
        foreach ([[4097, 1], [4096, 1954]] as [$width, $height]) {
            $source = $this->image($width, $height);
            try {
                $normalizer->normalize($this->png($source));
                self::fail('An oversized image was accepted.');
            } catch (DocConflict $error) {
                self::assertStringContainsString('8 мегапикселями', $error->getMessage());
            }
        }
    }

    public function testTruncatedPngIsRejectedAfterHeaderInspection(): void
    {
        $png = $this->png($this->image(16, 16));
        $header = substr($png, 0, 33);
        self::assertIsArray(getimagesizefromstring($header));
        $this->expectException(DocConflict::class);
        $this->expectExceptionMessage('повреждено');
        (new DocImageNormalizer())->normalize($header);
    }

    public function testNormalizedSizeLimitProtectsStorage(): void
    {
        $source = $this->image(1000, 1000);
        $random = new \Random\Randomizer(new \Random\Engine\Mt19937(4815));
        for ($y = 0; $y < 1000; ++$y) {
            for ($x = 0; $x < 1000; ++$x) {
                imagesetpixel($source, $x, $y, $random->getInt(0, 0xFFFFFF));
            }
        }

        $this->expectException(DocConflict::class);
        $this->expectExceptionMessage('2 МиБ');
        (new DocImageNormalizer())->normalize($this->png($source));
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    private function image(int $width, int $height): \GdImage
    {
        $source = imagecreatetruecolor($width, $height);
        self::assertInstanceOf(\GdImage::class, $source);

        return $source;
    }

    private function png(\GdImage $source): string
    {
        ob_start();
        try {
            self::assertTrue(imagepng($source));

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
