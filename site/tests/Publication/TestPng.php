<?php

declare(strict_types=1);

namespace App\Tests\Publication;

/**
 * Настоящий PNG заданного размера без GD: в образе PHP расширения нет,
 * а Assert\Image проверяет файл по содержимому, поэтому подделка не годится.
 */
final class TestPng
{
    /**
     * @return string путь к новому временному файлу (загрузка его перемещает)
     */
    public static function create(int $width, int $height): string
    {
        // Чёрно-белый 1 бит на пиксель: строка = байт фильтра + пиксели.
        $raw = str_repeat("\0".str_repeat("\xAA", intdiv($width + 7, 8)), $height);

        $png = "\x89PNG\r\n\x1a\n"
            .self::chunk('IHDR', pack('NNCCCCC', $width, $height, 1, 0, 0, 0, 0))
            .self::chunk('IDAT', (string) gzcompress($raw))
            .self::chunk('IEND', '');

        return self::write($png);
    }

    public static function notAnImage(): string
    {
        return self::write('<?php echo "not an image";');
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', \strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }

    private static function write(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cover');
        file_put_contents($path, $content);

        return $path;
    }
}
