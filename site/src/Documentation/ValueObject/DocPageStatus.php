<?php

declare(strict_types=1);

namespace App\Documentation\ValueObject;

enum DocPageStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return self::PUBLISHED === $this ? 'Опубликовано' : 'Черновик';
    }
}
