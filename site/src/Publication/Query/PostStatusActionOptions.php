<?php

declare(strict_types=1);

namespace App\Publication\Query;

use App\Publication\ValueObject\PostStatus;

final class PostStatusActionOptions
{
    /** @return list<array{action: string, label: string, class: string}> */
    public static function forStatus(PostStatus $status): array
    {
        return match ($status) {
            PostStatus::DRAFT => [
                ['action' => 'publish', 'label' => 'Опубликовать', 'class' => ''],
                ['action' => 'archive', 'label' => 'В архив', 'class' => 'secondary'],
            ],
            PostStatus::PUBLISHED => [
                ['action' => 'unpublish', 'label' => 'Снять с публикации', 'class' => 'secondary'],
                ['action' => 'archive', 'label' => 'В архив', 'class' => 'secondary'],
            ],
            PostStatus::ARCHIVED => [
                ['action' => 'restore', 'label' => 'Вернуть в черновики', 'class' => 'secondary'],
            ],
        };
    }
}
