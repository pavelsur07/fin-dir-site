<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Маркер доменного исключения "объект не найден". HTTP 404 -- DomainExceptionListener.
 */
interface NotFound extends \Throwable
{
}
