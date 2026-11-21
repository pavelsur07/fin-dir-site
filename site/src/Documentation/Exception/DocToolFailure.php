<?php

declare(strict_types=1);

namespace App\Documentation\Exception;

final class DocToolFailure extends \RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
