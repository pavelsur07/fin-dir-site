<?php

declare(strict_types=1);

namespace App\Bitrix24\Exception;

/**
 * Вызов REST Битрикс24 не удался. reason -- короткая категория (timeout, http_401,
 * QUERY_LIMIT_EXCEEDED…): её можно хранить и показывать. Ни адреса вебхука, ни ПД в ней нет.
 * outcomeUnknown -- запрос мог дойти (таймаут, 5xx): повтор должен сначала проверить, не создан ли лид.
 */
final class Bitrix24RequestFailed extends \RuntimeException
{
    public function __construct(public readonly string $reason, public readonly bool $outcomeUnknown = false)
    {
        parent::__construct('Bitrix24 request failed: '.$reason);
    }
}
