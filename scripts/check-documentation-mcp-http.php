<?php

declare(strict_types=1);

require '/app/vendor/autoload.php';

use App\Documentation\Entity\DocPage;
use App\Documentation\Entity\DocSection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
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

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new LogicException($label);
    }
}

try {
    $tokens = json_decode(file_get_contents('/smoke-tokens.json'), true, 512, JSON_THROW_ON_ERROR);
    $kernel = new Kernel('prod', false);
    $kernel->boot();
    $container = $kernel->getContainer();
    $em = $container->get('doctrine')->getManager();
    check($em instanceof EntityManagerInterface, 'Fixture entity manager unavailable.');
    $now = new DateTimeImmutable();
    $section = new DocSection('HTTP smoke', 'http-smoke', 'Isolated fixture', 0, $now);
    $em->persist($section);
    $page = new DocPage('http-visible', $now);
    $page->edit($section, 'HTTP published', 'http-visible', 'Published fixture', "## Overview\n\n".str_repeat('Published response with UTF-8: проверка. ', 700), 'Published text', 0, null, null, $now);
    $page->publish($now);
    $draft = new DocPage('http-hidden', $now);
    $draft->edit($section, 'Private draft', 'http-hidden', 'Must stay hidden', 'PRIVATE-CONTENT-SENTINEL', 'Hidden text', 0, null, null, $now);
    $em->persist($page);
    $em->persist($draft);
    $em->flush();
    $setGrant = static function (bool $enabled) use ($kernel): void {
        $app = new Application($kernel);
        $app->setAutoExit(false);
        check(0 === $app->run(new ArrayInput(['command' => 'app:docs:mcp:client:'.($enabled ? 'allow' : 'disable'), 'client-id' => 'smoke-reader']), new BufferedOutput()), 'Grant CLI failed.');
    };
    $setGrant(true);
    $http = HttpClient::create(['timeout' => 5, 'max_duration' => 10, 'max_redirects' => 0]);
    $raw = static function (string $method, string $path = '/mcp/docs', ?string $token = null, string $body = '{}', array $extra = []) use ($http): array {
        $headers = ['Host' => 'vashfindir.ru', 'Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream'];
        if (null !== $token) {
            $headers['Authorization'] = 'Bearer '.$token;
        }
        $response = $http->request($method, 'http://site'.$path, ['headers' => $extra + $headers, 'body' => $body]);

        return [$response->getStatusCode(), $response->getHeaders(false), $response->getContent(false)];
    };
    [$status, $headers, $body] = $raw('GET', '/.well-known/oauth-protected-resource/mcp/docs');
    check(200 === $status && ['docs:read'] === json_decode($body, true)['scopes_supported'], 'Protected metadata failed.');
    check(!isset($headers['set-cookie']), 'Metadata created a session.');
    [$status, $headers] = $raw('POST');
    check(401 === $status && isset($headers['www-authenticate']) && !isset($headers['location']) && !isset($headers['set-cookie']), 'Missing token did not return a stateless challenge.');
    check(401 === $raw('POST', token: $tokens['wrong_audience'])[0], 'Wrong audience accepted.');
    check(403 === $raw('POST', token: $tokens['no_scope'])[0], 'Missing scope accepted.');
    check(403 === $raw('POST', token: $tokens['valid'], extra: ['Origin' => 'https://evil.example'])[0], 'Hostile origin accepted.');
    check(413 === $raw('POST', token: $tokens['valid'], body: str_repeat('x', 1048577))[0], 'Oversized request accepted.');
    check(405 === $raw('GET', token: $tokens['valid'])[0], 'Long-lived GET enabled.');
    $recording = new class(new Psr18Client($http)) implements ClientInterface {
        public array $upstreams = [];
        public function __construct(private ClientInterface $inner) {}
        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $response = $this->inner->sendRequest($request);
            check(!$response->hasHeader('Set-Cookie') && !$response->hasHeader('Mcp-Session-Id'), 'MCP created cookie/session.');
            check(str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'MCP response cacheable.');
            $this->upstreams[$response->getHeaderLine('X-Smoke-Upstream')] = true;

            return $response;
        }
    };
    $factory = new Psr17Factory();
    foreach ([ProtocolVersion::V2025_11_25, ProtocolVersion::V2026_07_28] as $version) {
        $client = Client::builder()->setClientInfo('http-production-smoke', '1')->setProtocolVersion($version)->setMaxRetries(0)->setInitTimeout(10)->setRequestTimeout(10)->build();
        try {
            $client->connect(new HttpTransport('http://site/mcp/docs', ['Host' => 'vashfindir.ru'], $recording, $factory, $factory, auth: new BearerToken($tokens['valid'])));
            for ($i = 0; $i < 8; ++$i) {
                check(6 === count($client->listTools()->tools), 'HTTP tool discovery differs.');
            }
            $search = $client->callTool('docs_search');
            check(!$search->isError && 1 === $search->structuredContent['total'], 'Draft leaked into search.');
            $get = $client->callTool('docs_get_page', ['id' => $page->id()]);
            check(!$get->isError && strlen(json_encode($get, JSON_THROW_ON_ERROR)) > 8192, 'Large valid response truncated.');
            $hidden = $client->callTool('docs_get_page', ['id' => $draft->id()]);
            check($hidden->isError && 'NOT_FOUND' === $hidden->structuredContent['code'], 'Draft content exposed.');
            echo 'MCP HTTP production images: '.$version->value." passed.\n";
        } finally {
            $client->disconnect();
        }
    }
    check(2 === count(array_filter(array_keys($recording->upstreams))), 'Requests did not exercise both FPM replicas.');
    $setGrant(false);
    for ($i = 0; $i < 6; ++$i) {
        check(403 === $raw('POST', token: $tokens['valid'])[0], 'Grant revocation not immediate.');
    }
    $setGrant(true);
    $ping = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], JSON_THROW_ON_ERROR);
    check(200 === $raw('POST', token: $tokens['valid'], body: $ping)[0], 'Grant re-enable failed.');
    // Per-IP quota is shared between the two observed replicas.
    for ($i = 0; $i < 61; ++$i) {
        [$status] = $raw('POST', extra: ['X-Forwarded-For' => '198.51.100.23']);
        check(($i < 60 ? 401 : 429) === $status, 'Shared IP limit differs.');
    }
    // Reset only this isolated DB's limiter pool, then hit the actor concurrently.
    $app = new Application($kernel);
    $app->setAutoExit(false);
    check(0 === $app->run(new ArrayInput(['command' => 'cache:pool:clear', 'pools' => ['cache.rate_limiter']]), new BufferedOutput()), 'Limiter reset failed.');
    $requests = [];
    for ($i = 0; $i < 121; ++$i) {
        $requests[] = $http->request('POST', 'http://site/mcp/docs', ['headers' => ['Host' => 'vashfindir.ru', 'Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream', 'Authorization' => 'Bearer '.$tokens['valid'], 'X-Forwarded-For' => '198.51.100.'.(40 + $i)], 'body' => $ping]);
    }
    $statuses = [];
    foreach ($requests as $response) {
        $status = $response->getStatusCode();
        $statuses[$status] = ($statuses[$status] ?? 0) + 1;
        $response->getContent(false);
    }
    ksort($statuses);
    check([200 => 120, 429 => 1] === $statuses, 'Concurrent shared client quota differs.');
    echo "Two FPM replicas: visibility, revocation, shared quotas and bounded HTTP passed.\n";
    $kernel->shutdown();
} catch (Throwable $error) {
    // Do not dump SDK/HTTP exception context containing synthetic credentials.
    fwrite(STDERR, 'MCP HTTP smoke failed: '.$error::class.' ('.($error instanceof LogicException ? $error->getMessage() : 'see isolated test').").\n");
    exit(1);
}
