<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use App\Documentation\Exception\DocToolFailure;

final readonly class DocMcpAccess
{
    public function __construct(public string $actor, public string $profile)
    {
        if (!preg_match('/^[a-zA-Z0-9._-]{1,64}$/D', $actor) || !in_array($profile, ['published-reader', 'draft-reader', 'draft-editor'], true)) {
            throw new \InvalidArgumentException('MCP requires a valid actor and profile.');
        }
    }

    public function assertAllowed(string $capability): void
    {
        $allowed = match ($capability) {
            'docs:read' => true,
            'docs:drafts:read' => 'published-reader' !== $this->profile,
            'docs:drafts:write' => 'draft-editor' === $this->profile,
            default => false,
        };
        if (!$allowed) {
            throw new DocToolFailure('FORBIDDEN', 'Недостаточно прав для этого действия.');
        }
    }

    public function canReadDrafts(): bool
    {
        return 'published-reader' !== $this->profile;
    }
}
