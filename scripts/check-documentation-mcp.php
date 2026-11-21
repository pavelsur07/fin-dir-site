<?php

declare(strict_types=1);

require '/app/vendor/autoload.php';

use Mcp\Client\Client;
use Mcp\Client\Transport\StdioTransport;
use Mcp\Schema\Enum\ProtocolVersion;

foreach ([ProtocolVersion::V2025_11_25, ProtocolVersion::V2026_07_28] as $version) {
    $env = getenv();
    $env['DOCS_MCP_ENABLED'] = '1';
    $env['DOCS_MCP_ACTOR'] = 'production-image-smoke';
    $env['DOCS_MCP_PROFILE'] = 'published-reader';
    $client = Client::builder()->setClientInfo('production-image-smoke', '1.0.0')->setProtocolVersion($version)->setMaxRetries(0)->setInitTimeout(15)->setRequestTimeout(15)->build();
    try {
        $client->connect(new StdioTransport(PHP_BINARY, ['/app/bin/console', 'app:docs:mcp', '--no-debug', '--no-ansi', '--no-interaction'], '/app', $env));
        $tools = $client->listTools()->tools;
        if (6 !== count($tools)) {
            throw new RuntimeException('Unexpected read-only MCP tool count.');
        }
        foreach (['docs_list_sections', 'docs_search'] as $tool) {
            $result = $client->callTool($tool);
            if ($result->isError || 20 !== $result->structuredContent['pageSize']) {
                throw new RuntimeException('Production MCP read failed.');
            }
        }
        echo 'MCP production CLI: '.$version->value." passed (read-only).\n";
    } finally {
        $client->disconnect();
    }
}
