<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

enum LeadStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case QUALIFIED = 'qualified';
    case SPAM = 'spam';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Новое',
            self::IN_PROGRESS => 'В работе',
            self::QUALIFIED => 'Квалифицировано',
            self::SPAM => 'Спам',
            self::CLOSED => 'Закрыто',
        };
    }
}
