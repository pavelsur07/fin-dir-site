<?php

declare(strict_types=1);

namespace App\Documentation\Controller\Mcp;

use App\Documentation\DTO\DocMcpHttpSettings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DocMcpMetadataController
{
    #[Route('/.well-known/oauth-protected-resource/mcp/docs', name: 'docs_mcp_metadata', host: 'vashfindir.ru', methods: ['GET'], priority: 400)]
    public function __invoke(DocMcpHttpSettings $settings): JsonResponse
    {
        return new JsonResponse(['resource' => DocMcpHttpSettings::RESOURCE, 'authorization_servers' => [$settings->issuer], 'scopes_supported' => ['docs:read'], 'bearer_methods_supported' => ['header'], 'resource_name' => 'Documentation MCP'], 200, ['Cache-Control' => 'public, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
