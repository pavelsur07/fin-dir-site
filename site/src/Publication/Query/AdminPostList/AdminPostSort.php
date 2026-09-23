<?php

declare(strict_types=1);

namespace App\Publication\Query\AdminPostList;

/**
 * Белый список сортировки: пользователь выбирает вариант, а не поле DQL.
 */
enum AdminPostSort: string
{
    case UPDATED = 'updated';
    case PUBLISHED = 'published';
    case TITLE = 'title';

    public function dqlField(): string
    {
        return match ($this) {
            self::UPDATED => 'p.updatedAt',
            self::PUBLISHED => 'p.publishedAt',
            self::TITLE => 'p.title',
        };
    }

    public function direction(): string
    {
        return self::TITLE === $this ? 'ASC' : 'DESC';
    }

    public function label(): string
    {
        return match ($this) {
            self::UPDATED => 'по изменению',
            self::PUBLISHED => 'по публикации',
            self::TITLE => 'по заголовку',
        };
    }
}
