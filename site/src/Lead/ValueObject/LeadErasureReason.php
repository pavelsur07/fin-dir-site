<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Основание уничтожения обращения -- для журнала уничтожения (152-ФЗ, ст. 21;
 * приказ Роскомнадзора от 28.10.2022 № 179).
 */
enum LeadErasureReason: string
{
    case CONSENT_WITHDRAWN = 'consent_withdrawn';
    case SUBJECT_REQUEST = 'subject_request';
    case RETENTION_EXPIRED = 'retention_expired';
    case SPAM_OR_TEST = 'spam_or_test';

    public function label(): string
    {
        return match ($this) {
            self::CONSENT_WITHDRAWN => 'Отзыв согласия',
            self::SUBJECT_REQUEST => 'Требование человека удалить данные',
            self::RETENTION_EXPIRED => 'Истёк срок хранения',
            self::SPAM_OR_TEST => 'Спам или тестовая заявка',
        };
    }

    /**
     * Основания, которые выбирает администратор. Истечение срока ставит только команда.
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return [self::CONSENT_WITHDRAWN, self::SUBJECT_REQUEST, self::SPAM_OR_TEST];
    }
}
