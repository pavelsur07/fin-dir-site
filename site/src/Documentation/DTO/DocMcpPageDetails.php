<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocMcpPageDetails
{
    /** @param array<string,mixed> $data */
    public function __construct(public array $data)
    {
    }
}
