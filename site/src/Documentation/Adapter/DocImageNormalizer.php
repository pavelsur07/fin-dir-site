<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

use App\Documentation\DTO\DocImageUpload;
use App\Documentation\Exception\DocConflict;

final class DocImageNormalizer
{
    private const int MAX_OUTPUT_BYTES = 2 * 1024 * 1024;
    private const int MAX_SIDE = 4096;
    private const int MAX_PIXELS = 8_000_000;

    /** @return array{contents:string,width:int,height:int} */
    public function normalize(string $input): array
    {
        if ('' === $input || strlen($input) > DocImageUpload::MAX_INPUT_BYTES) {
            throw new DocConflict('Загрузите PNG или WebP размером до 8 МиБ.');
        }

        $info = @getimagesizefromstring($input);
        if (false === $info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new DocConflict('Формат файла должен быть PNG или WebP.');
        }
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE || $width * $height > self::MAX_PIXELS) {
            throw new DocConflict('Размер изображения ограничен 4096 пикселями на сторону и 8 мегапикселями.');
        }

        $image = @imagecreatefromstring($input);
        if (false === $image || !imagepalettetotruecolor($image) || !imagesavealpha($image, true)) {
            throw new DocConflict('Изображение повреждено или не поддерживается.');
        }

        ob_start();
        try {
            $encoded = @imagewebp($image, null, IMG_WEBP_LOSSLESS);
            $contents = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if (!$encoded || '' === $contents) {
            throw new DocConflict('Не удалось обработать изображение.');
        }
        if (strlen($contents) > self::MAX_OUTPUT_BYTES) {
            throw new DocConflict('Уменьшите или обрежьте снимок, чтобы WebP занимал не более 2 МиБ.');
        }

        return ['contents' => $contents, 'width' => $width, 'height' => $height];
    }
}
