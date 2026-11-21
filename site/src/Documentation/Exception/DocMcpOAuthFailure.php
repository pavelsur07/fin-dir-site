<?php

declare(strict_types=1);

namespace App\Documentation\Exception;

final class DocMcpOAuthFailure extends \RuntimeException
{
    public function __construct(public readonly bool $unavailable = false)
    {
        parent::__construct($unavailable ? 'OAuth validation unavailable.' : 'Invalid access token.');
    }
}
