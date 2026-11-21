<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocMcpTokenIdentity
{
    /** @param list<string> $scopes */
    public function __construct(public string $issuer, public string $clientId, public array $scopes, public int $expiresAt)
    {
    }

    /** @return non-empty-string */
    public function actor(): string
    {
        return 'http.'.substr(hash('sha256', json_encode([$this->issuer, $this->clientId], JSON_THROW_ON_ERROR)), 0, 59);
    }
}
