<?php

declare(strict_types=1);

namespace App\Publication\ValueObject;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => \in_array($target, [self::PUBLISHED, self::ARCHIVED], true),
            self::PUBLISHED => \in_array($target, [self::DRAFT, self::ARCHIVED], true),
            self::ARCHIVED => self::DRAFT === $target,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PUBLISHED => 'Опубликовано',
            self::ARCHIVED => 'В архиве',
        };
    }
}
