<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Маркер доменного исключения "состояние не позволяет операцию". HTTP 409 -- DomainExceptionListener.
 */
interface Conflict extends \Throwable
{
}
