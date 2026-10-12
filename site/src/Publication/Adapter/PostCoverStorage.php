<?php

declare(strict_types=1);

namespace App\Publication\Adapter;

use App\Publication\ValueObject\PostCover;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Файлы обложек в public/uploads/posts: nginx раздаёт их как статику.
 * Имя каждый раз новое -- файл можно кешировать навсегда, а чужой не перезаписать.
 */
final class PostCoverStorage
{
    public function __construct(
        #[Autowire('%vf.post_cover.dir%')] private readonly string $directory,
    ) {
    }

    /**
     * Переносит уже проверенный PNG в хранилище.
     *
     * @return string имя сохранённого файла
     */
    public function store(File $file): string
    {
        $name = bin2hex(random_bytes(16)).'.png';
        $file->move($this->directory, $name);

        return $name;
    }

    /**
     * Удаление не ломает сценарий: оставшийся файл -- только мусор на диске, не ошибка данных.
     */
    public function delete(string $file): void
    {
        // Удаляем только имена, которые выдаёт store(): путь вроде ../ сюда не пройдёт.
        if (!PostCover::isValidFile($file)) {
            return;
        }

        $path = $this->directory.'/'.$file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
