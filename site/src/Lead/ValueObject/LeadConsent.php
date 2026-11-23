<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Версия текста согласия и политики обработки персональных данных, на которую
 * согласился человек. Менять вместе с текстом /privacy и /consent; прежний текст
 * сохранять в docs/legal/consent/<версия>.md (LEGAL_RULES.md, раздел 9).
 */
final class LeadConsent
{
    public const string VERSION = '2026-11-23';

    /**
     * Срок действия согласия и хранения обращения: «3 года с даты отправки заявки»
     * (/consent, пункт 5). По истечении обращение удаляет app:lead:purge-expired.
     */
    public const string RETENTION = 'P3Y';

    /**
     * Версии согласия, текст которых разрешает поручить обработку CRM-сервису (Битрикс24).
     * 2026-11-10 не входит: тот текст обещал не передавать данные третьим лицам, такие
     * обращения в CRM не уходят никогда (docs/plan/Stage_Bitrix24_Integration.md).
     */
    private const array CRM_TRANSFER_VERSIONS = ['2026-11-23'];

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

    /** Момент, раньше которого данное согласие считается истёкшим. */
    public static function expiredBefore(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->sub(new \DateInterval(self::RETENTION));
    }
}
