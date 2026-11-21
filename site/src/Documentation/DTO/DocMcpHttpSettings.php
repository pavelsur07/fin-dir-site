<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DocMcpHttpSettings
{
    public const string PATH = '/mcp/docs';
    public const string METADATA_PATH = '/.well-known/oauth-protected-resource/mcp/docs';
    public const string RESOURCE = 'https://vashfindir.ru/mcp/docs';
    public const string METADATA_URL = 'https://vashfindir.ru/.well-known/oauth-protected-resource/mcp/docs';

    /**
     * @param list<string> $origins
     * @param list<string> $jwksHosts
     */
    public function __construct(
        #[Autowire('%env(bool:DOCS_MCP_HTTP_ENABLED)%')] public bool $enabled,
        #[Autowire('%env(DOCS_MCP_OAUTH_ISSUER)%')] public string $issuer,
        #[Autowire('%env(csv:DOCS_MCP_HTTP_ORIGINS)%')] public array $origins,
        #[Autowire('%env(csv:DOCS_MCP_JWKS_HOSTS)%')] public array $jwksHosts,
        #[Autowire('%env(DOCS_MCP_OAUTH_CA_FILE)%')] public string $caFile = '',
    ) {
    }
}
