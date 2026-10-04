<?php

declare(strict_types=1);

namespace App\Publication\ValueObject;

/**
 * Фиксированный список рубрик «Газеты»: не свободные теги. Значение -- slug для ?rubric=.
 */
enum PostRubric: string
{
    case TAXES = 'nalogi';
    case REPORTING = 'otchetnost';
    case MANAGEMENT_ACCOUNTING = 'upravlencheskij-uchet';
    case UNIT_ECONOMICS = 'unit-ekonomika';
    case MARKETPLACES = 'marketplejsy';

    public function label(): string
    {
        return match ($this) {
            self::TAXES => 'Налоги',
            self::REPORTING => 'Отчётность',
            self::MANAGEMENT_ACCOUNTING => 'Управленческий учёт',
            self::UNIT_ECONOMICS => 'Unit-экономика',
            self::MARKETPLACES => 'Маркетплейсы',
        };
    }
}
