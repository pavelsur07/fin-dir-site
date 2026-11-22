<?php

declare(strict_types=1);

namespace App\Bitrix24\Exception;

use App\Shared\Exception\Conflict;

/** Подключение не настроено, выключено или сохранённый вебхук не расшифровывается (сменился APP_SECRET). */
final class Bitrix24NotConnected extends \RuntimeException implements Conflict
{
    public function __construct(public readonly string $reason = 'not_connected')
    {
        parent::__construct('Bitrix24 is not connected: '.$reason);
    }
}
