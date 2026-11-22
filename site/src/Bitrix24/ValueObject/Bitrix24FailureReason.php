<?php

declare(strict_types=1);

namespace App\Bitrix24\ValueObject;

/** Понятная админу подпись для категории сбоя (Bitrix24RequestFailed::$reason). */
final class Bitrix24FailureReason
{
    private const array LABELS = [
        'timeout' => 'Битрикс24 не ответил вовремя',
        'network' => 'нет связи с порталом',
        'not_connected' => 'подключение не настроено или выключено',
        'undecryptable' => 'сохранённый адрес не читается — подключите вебхук заново',
        'bad_response' => 'неожиданный ответ портала',
        'http_401' => 'вебхук не принят — проверьте адрес',
        'NO_AUTH_FOUND' => 'вебхук не принят — проверьте адрес',
        'INVALID_CREDENTIALS' => 'вебхук не принят — проверьте адрес',
        'insufficient_scope' => 'у вебхука нет права «CRM»',
        'ACCESS_DENIED' => 'у вебхука нет права «CRM»',
        'QUERY_LIMIT_EXCEEDED' => 'превышен лимит запросов Битрикс24',
        'PORTAL_DELETED' => 'портал удалён',
    ];

    public static function label(string $reason): string
    {
        return self::LABELS[$reason] ?? 'ошибка Битрикс24 ('.$reason.')';
    }
}
