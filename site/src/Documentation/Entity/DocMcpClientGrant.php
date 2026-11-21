<?php

declare(strict_types=1);

namespace App\Documentation\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documentation_mcp_client_grant')]
#[ORM\UniqueConstraint(name: 'doc_mcp_client_grant_uniq', columns: ['issuer_key', 'client_id'])]
class DocMcpClientGrant
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 64)] private string $issuerKey;
    #[ORM\Column(length: 255)] private string $clientId;
    #[ORM\Column] private bool $enabled = false;
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column] private \DateTimeImmutable $updatedAt;
    public function __construct(string $issuer, string $clientId, \DateTimeImmutable $now)
    {
        if ('https' !== parse_url($issuer, PHP_URL_SCHEME) || !parse_url($issuer, PHP_URL_HOST) || '' === $clientId || mb_strlen($clientId) > 255) {
            throw new \InvalidArgumentException('A configured HTTPS issuer and nonempty client ID (max255) are required.');
        }
        $this->issuerKey = hash('sha256', $issuer);
        $this->clientId = $clientId;
        $this->createdAt = $this->updatedAt = $now;
    }

    public function allow(\DateTimeImmutable $now): void
    {
        $this->enabled = true;
        $this->updatedAt = $now;
    }

    public function disable(\DateTimeImmutable $now): void
    {
        $this->enabled = false;
        $this->updatedAt = $now;
    }
}
