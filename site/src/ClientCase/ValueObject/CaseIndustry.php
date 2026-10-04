<?php

declare(strict_types=1);

namespace App\ClientCase\ValueObject;

/**
 * Фиксированный список отраслей клиентов: не свободные теги. Значение -- slug для ?industry=.
 */
enum CaseIndustry: string
{
    case CONSTRUCTION = 'stroitelstvo';
    case TRADE = 'torgovlya';
    case MARKETPLACES = 'marketplejsy';
    case SERVICES = 'uslugi';
    case PRODUCTION = 'proizvodstvo';
    case IT = 'it';

    public function label(): string
    {
        return match ($this) {
            self::CONSTRUCTION => 'Строительство',
            self::TRADE => 'Торговля',
            self::MARKETPLACES => 'Маркетплейсы',
            self::SERVICES => 'Услуги',
            self::PRODUCTION => 'Производство',
            self::IT => 'IT',
        };
    }
}
