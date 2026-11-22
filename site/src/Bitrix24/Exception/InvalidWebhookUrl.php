<?php

declare(strict_types=1);

namespace App\Bitrix24\Exception;

/** Введённый адрес не похож на входящий вебхук облачного Битрикс24. Сам адрес в сообщение не попадает. */
final class InvalidWebhookUrl extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid Bitrix24 webhook URL.');
    }
}
