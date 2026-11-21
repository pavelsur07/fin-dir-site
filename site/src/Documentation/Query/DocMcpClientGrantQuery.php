<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use Doctrine\DBAL\Connection;

final class DocMcpClientGrantQuery
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function isEnabled(string $issuerKey, string $clientId): bool
    {
        return false !== $this->db->fetchOne('SELECT 1 FROM documentation_mcp_client_grant WHERE issuer_key=:issuer AND client_id=:client AND enabled=true', ['issuer' => $issuerKey, 'client' => $clientId]);
    }
}
