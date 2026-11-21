<?php

declare(strict_types=1);

namespace App\Documentation\Mcp\Http;

use App\Documentation\DTO\DocMcpHttpSettings;
use App\Documentation\DTO\DocMcpTokenIdentity;
use App\Documentation\Mcp\DocMcpAccess;
use App\Documentation\Mcp\DocMcpSchema;
use App\Documentation\Mcp\DocMcpServer;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Server\Authorization\AccessToken;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Uri;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class DocMcpHttpGateway
{
    public function __construct(private readonly DocMcpServer $server, private readonly DocMcpHttpSettings $settings, #[Autowire(service: 'limiter.documentation_mcp_client')] private readonly RateLimiterFactory $clients)
    {
    }

    public function handle(Request $request, DocMcpTokenIdentity $identity): Response
    {
        $request->attributes->set('_doc_mcp_actor', $identity->actor());
        $headers = ['Cache-Control' => 'private, no-store', 'Vary' => 'Authorization', 'X-Content-Type-Options' => 'nosniff'];
        if (!in_array('docs:read', $identity->scopes, true)) {
            return new JsonResponse(['error' => 'insufficient_scope'], 403, $headers);
        }
        try {
            $limit = $this->clients->create($identity->actor())->consume();
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'temporarily_unavailable'], 503, $headers + ['Retry-After' => '10']);
        }
        if (!$limit->isAccepted()) {
            return new JsonResponse(['error' => 'rate_limited'], 429, $headers + ['Retry-After' => (string) max(1, $limit->getRetryAfter()->getTimestamp() - time())]);
        }
        if ('POST' !== $request->getMethod()) {
            return new Response('', 405, $headers + ['Allow' => 'POST, OPTIONS']);
        }
        $body = $request->attributes->get('_doc_mcp_body', '');
        $payload = json_decode($body, true);
        $id = is_array($payload) && (is_int($payload['id'] ?? null) || is_string($payload['id'] ?? null)) ? $payload['id'] : null;
        if (is_string($id) && strlen($id) > 4096) {
            return new JsonResponse(Error::forInvalidRequest('Request id exceeds limit.'), 400, $headers);
        }
        // String correlation IDs are hashed in logs so arbitrary client text stays out.
        $request->attributes->set('_doc_mcp_request_id', is_string($id) ? 'sha256:'.hash('sha256', $id) : $id);
        $name = $payload['params']['name'] ?? null;
        if (is_string($name) && isset(DocMcpSchema::inputs()[$name])) {
            $request->attributes->set('_doc_mcp_tool', $name);
        }
        // Restrict the application surface to finite synchronous requests; protocol parsing stays in SDK.
        $allowed = ['initialize', 'server/discover', 'ping', 'tools/list', 'tools/call', 'notifications/initialized', 'notifications/cancelled'];
        if (is_array($payload) && '2.0' === ($payload['jsonrpc'] ?? null) && is_string($payload['method'] ?? null) && !in_array($payload['method'], $allowed, true)) {
            return null === $id ? new Response('', 204, $headers) : new JsonResponse(Error::forMethodNotFound('Method not supported.', $id), 400, $headers);
        }
        $factory = new Psr17Factory();
        $bridge = new PsrHttpFactory($factory, $factory, $factory, $factory);
        $psr = $bridge->createRequest($request)->withUri(new Uri(DocMcpHttpSettings::RESOURCE))->withBody($factory->createStream($body))
            ->withAttribute(AccessToken::class, new AccessToken($identity->scopes, ['iss' => $identity->issuer, 'client_id' => $identity->clientId]));
        $transport = new StreamableHttpTransport($psr, $factory, $factory, middleware: [new DnsRebindingProtectionMiddleware(['vashfindir.ru'], array_values(array_filter($this->settings->origins)), $factory, $factory)], maxBodyBytes: 1024 * 1024);
        $response = $this->server->build(new DocMcpAccess($identity->actor(), 'published-reader'))->run($transport);
        $stream = $response->getBody();
        $output = '';
        while (!$stream->eof() && strlen($output) <= 1024 * 1024) {
            $chunk = $stream->read(min(8192, 1024 * 1024 + 1 - strlen($output)));
            if ('' === $chunk) {
                break;
            }
            $output .= $chunk;
        }
        if (strlen($output) > 1024 * 1024) {
            return new JsonResponse(Error::forInternalError('LIMIT_EXCEEDED', $id), 500, $headers);
        }
        $result = new Response($output, $response->getStatusCode(), $response->getHeaders());
        $result->headers->add($headers);

        return $result;
    }
}
