<?php

declare(strict_types=1);

namespace App\ClientCase\ValueObject;

enum CaseStatus: string
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

    /**
     * Действия админки для статуса.
     *
     * @return list<array{action: string, label: string, class: string}>
     */
    public function actions(): array
    {
        return match ($this) {
            self::DRAFT => [
                ['action' => 'publish', 'label' => 'Опубликовать', 'class' => ''],
                ['action' => 'archive', 'label' => 'В архив', 'class' => 'secondary'],
            ],
            self::PUBLISHED => [
                ['action' => 'unpublish', 'label' => 'Снять с публикации', 'class' => 'secondary'],
                ['action' => 'archive', 'label' => 'В архив', 'class' => 'secondary'],
            ],
            self::ARCHIVED => [
                ['action' => 'restore', 'label' => 'Вернуть в черновики', 'class' => 'secondary'],
            ],
        };
    }
}
