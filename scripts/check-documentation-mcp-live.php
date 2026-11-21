<?php

declare(strict_types=1);

require '/app/vendor/autoload.php';

use Mcp\Client\Auth\BearerToken;
use Mcp\Client\Client;
use Mcp\Client\Transport\HttpTransport;
use Mcp\Schema\Enum\ProtocolVersion;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

function liveCheck(bool $condition): void
{
    if (!$condition) {
        throw new LogicException('Live MCP contract check failed.');
    }
}

try {
    $http = HttpClient::create(['timeout' => 5, 'max_duration' => 10, 'max_redirects' => 0]);
    $tokenResponse = $http->request('POST', 'https://auth.vashfindir.ru/realms/projects/protocol/openid-connect/token', ['body' => ['grant_type' => 'client_credentials', 'client_id' => 'docs-mcp-smoke-prod', 'client_secret' => trim(file_get_contents('/run/secrets/client')), 'scope' => 'docs:read', 'resource' => 'https://vashfindir.ru/mcp/docs']]);
    liveCheck(200 === $tokenResponse->getStatusCode());
    $token = $tokenResponse->toArray()['access_token'];
    if ('replicas' === getenv('MCP_LIVE_MODE')) {
        $expected = (int) getenv('MCP_LIVE_EXPECTED_STATUS');
        liveCheck(in_array($expected, [200, 403], true));
        foreach ([8081, 8082] as $port) {
            $response = $http->request('POST', 'http://identity-mcp-replicas:'.$port.'/mcp/docs', ['headers' => ['Host' => 'vashfindir.ru', 'Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream', 'Authorization' => 'Bearer '.$token], 'body' => '{"jsonrpc":"2.0","id":1,"method":"ping"}']);
            liveCheck($expected === $response->getStatusCode());
            $response->getContent(false);
        }
        echo 'Both production FPM replicas returned expected status '.$expected.".\n";
        exit(0);
    }
    $recording = new class(new Psr18Client($http)) implements ClientInterface {
        public function __construct(private ClientInterface $inner) {}
        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $response = $this->inner->sendRequest($request);
            liveCheck(!$response->hasHeader('Set-Cookie') && !$response->hasHeader('Mcp-Session-Id'));
            return $response;
        }
    };
    $factory = new Psr17Factory();
    $expectedTools = ['docs_get_links', 'docs_get_page', 'docs_list_sections', 'docs_preview_page', 'docs_search', 'docs_validate_page'];
    foreach ([ProtocolVersion::V2025_11_25, ProtocolVersion::V2026_07_28] as $version) {
        $client = Client::builder()->setClientInfo('identity-live-readonly-check', '1')->setProtocolVersion($version)->setMaxRetries(0)->setInitTimeout(10)->setRequestTimeout(10)->build();
        try {
            $client->connect(new HttpTransport('https://vashfindir.ru/mcp/docs', [], $recording, $factory, $factory, auth: new BearerToken($token)));
            $names = array_map(static fn ($tool): string => $tool->name, $client->listTools()->tools);
            sort($names);
            liveCheck($expectedTools === $names);
            liveCheck(!$client->callTool('docs_search')->isError);
            liveCheck($client->callTool('docs_search', ['status' => 'draft'])->isError);
            echo 'Production MCP SDK '.$version->value.": six read tools, draft denial and no sessions passed.\n";
        } finally {
            $client->disconnect();
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Live MCP check failed: '.$error::class.".\n");
    exit(1);
}
