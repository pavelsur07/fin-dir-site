<?php

declare(strict_types=1);

namespace App\Documentation\Security;

use App\Documentation\DTO\DocMcpTokenIdentity;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class DocMcpPrincipal implements UserInterface
{
    public function __construct(public DocMcpTokenIdentity $identity, private bool $enabled)
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->identity->actor();
    }

    public function getRoles(): array
    {
        return $this->enabled && in_array('docs:read', $this->identity->scopes, true) ? ['ROLE_DOCS_MCP'] : ['ROLE_DOCS_MCP_AUTHENTICATED'];
    }

    public function eraseCredentials(): void
    {
    }
}
