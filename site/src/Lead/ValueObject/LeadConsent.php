<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Версия текста согласия и политики конфиденциальности, на которую согласился
 * человек. Менять вместе с текстом /privacy и /consent.
 */
final class LeadConsent
{
    public const string VERSION = '2026-11-10';

    /**
     * Версии согласия, текст которых разрешает поручить обработку CRM-сервису (Битрикс24).
     * Пока пусто: действующее согласие обещает не передавать данные третьим лицам,
     * поэтому в CRM не уходит ничего. Новую версию добавлять вместе с новым текстом
     * /consent и /privacy (docs/plan/Stage_Bitrix24_Integration.md).
     */
    private const array CRM_TRANSFER_VERSIONS = [];

    /**
     * @return list<string>
     */
    public static function crmTransferVersions(): array
    {
        return self::CRM_TRANSFER_VERSIONS;
    }

    public static function allowsCrmTransfer(string $version): bool
    {
        return \in_array($version, self::crmTransferVersions(), true);
    }
}
