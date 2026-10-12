<?php

declare(strict_types=1);

namespace App\Publication\ValueObject;

/**
 * Обложка статьи: файл в public/uploads/posts и обязательный alt.
 * Формат один на весь сайт -- PNG 1200×675 (16:9); сервер картинку не меняет.
 */
final readonly class PostCover
{
    public const int WIDTH = 1200;
    public const int HEIGHT = 675;
    public const string MAX_SIZE = '2M';
    public const int ALT_MAX_LENGTH = 160;
    /** Имя генерирует сервер: 32 hex-символа. Имя от пользователя не используется никогда. */
    public const string FILE_PATTERN = '/^[a-f0-9]{32}\.png$/';
    public const string URL_PREFIX = '/uploads/posts/';

    public function __construct(
        public string $file,
        public string $alt,
    ) {
    }

    /** Проекции читают две nullable-колонки: обложка есть, только если есть файл. */
    public static function fromColumns(?string $file, ?string $alt): ?self
    {
        return null === $file ? null : new self($file, (string) $alt);
    }

    public static function isValidFile(string $file): bool
    {
        return 1 === preg_match(self::FILE_PATTERN, $file);
    }

    public function url(): string
    {
        return self::URL_PREFIX.$this->file;
    }
}
