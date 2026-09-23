<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Формы сайта. Новая форма -- новая запись здесь, без миграций: ответы
 * хранятся снимком в Lead::answers.
 */
final class LeadFormCatalog
{
    public static function has(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    public static function get(string $key): LeadFormDefinition
    {
        return self::all()[$key] ?? throw new \InvalidArgumentException(\sprintf('Unknown lead form "%s".', $key));
    }

    /**
     * @return array<string, LeadFormDefinition>
     */
    public static function all(): array
    {
        return [
            'consultation' => new LeadFormDefinition('consultation', 'Консультация'),
            'diagnostics' => new LeadFormDefinition('diagnostics', 'Диагностика', [
                new LeadQuestion('channel', 'Где продаёте?', [
                    'wildberries' => 'Wildberries',
                    'ozon' => 'Ozon',
                    'yandex_market' => 'Яндекс Маркет',
                    'own_site' => 'Свой сайт',
                    'multichannel' => 'Несколько каналов',
                ]),
                new LeadQuestion('turnover', 'Оборот в месяц', [
                    'under_1m' => 'до 1 млн ₽',
                    '1m_5m' => '1–5 млн ₽',
                    '5m_20m' => '5–20 млн ₽',
                    'over_20m' => 'больше 20 млн ₽',
                ]),
                new LeadQuestion('need', 'Что нужно?', [
                    'finance_review' => 'Разобраться в финансах',
                    'outsourced_cfo' => 'Финдиректор на аутсорсе',
                    'saas' => 'Учёт в SaaS',
                    'not_sure' => 'Пока не знаю',
                ]),
            ]),
        ];
    }
}
