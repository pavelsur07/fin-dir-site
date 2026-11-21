<?php

declare(strict_types=1);

namespace App\Documentation\Mcp\Http;

use App\Documentation\Adapter\DocMcpEventLogger;
use App\Documentation\DTO\DocMcpHttpSettings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: 'kernel.request', priority: 40)]
final class DocMcpHttpRequestGuard
{
    public function __construct(private readonly DocMcpHttpSettings $settings, #[Autowire(service: 'limiter.documentation_mcp_ip')] private readonly RateLimiterFactory $ip, #[Autowire(service: 'limiter.documentation_mcp_metadata')] private readonly RateLimiterFactory $metadata, private readonly DocMcpEventLogger $events)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $rawPath = $request->getPathInfo();
        $path = rawurldecode($rawPath);
        $isMetadata = DocMcpHttpSettings::METADATA_PATH === $path;
        if (!in_array(rtrim($path, '/'), [DocMcpHttpSettings::PATH, DocMcpHttpSettings::METADATA_PATH], true)) {
            return;
        }
        if (!$this->settings->enabled || 'vashfindir.ru' !== $request->getHost() || $rawPath !== $path || (!in_array($path, [DocMcpHttpSettings::PATH, DocMcpHttpSettings::METADATA_PATH], true))) {
            $event->setResponse(new JsonResponse(['error' => 'not_found'], 404, ['Cache-Control' => 'private, no-store']));

            return;
        }
        $request->attributes->set('_doc_mcp_started', microtime(true));
        $issuer = parse_url($this->settings->issuer);
        if (false === $issuer || 'https' !== ($issuer['scheme'] ?? '') || empty($issuer['host']) || isset($issuer['user']) || isset($issuer['pass']) || isset($issuer['query']) || isset($issuer['fragment']) || in_array('*', $this->settings->origins, true)) {
            $event->setResponse(new JsonResponse(['error' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'private, no-store', 'Retry-After' => '10']));

            return;
        }
        $origin = $request->headers->get('Origin');
        if (null !== $origin && !in_array($origin, ['https://vashfindir.ru', ...array_filter($this->settings->origins)], true)) {
            $event->setResponse(new JsonResponse(['error' => 'forbidden_origin'], 403, ['Cache-Control' => 'private, no-store']));

            return;
        }
        try {
            $limit = ($isMetadata ? $this->metadata : $this->ip)->create($request->getClientIp() ?? 'unknown')->consume();
        } catch (\Throwable) {
            $event->setResponse(new JsonResponse(['error' => 'temporarily_unavailable'], 503, ['Retry-After' => '10']));

            return;
        }
        if (!$limit->isAccepted()) {
            $event->setResponse(new JsonResponse(['error' => 'rate_limited'], 429, ['Retry-After' => (string) max(1, $limit->getRetryAfter()->getTimestamp() - time()), 'Cache-Control' => 'private, no-store']));

            return;
        }
        if ('OPTIONS' === $request->getMethod() && !$isMetadata) {
            $event->setResponse(new Response('', 204, ['Allow' => 'POST, OPTIONS', 'Cache-Control' => 'private, no-store']));

            return;
        }
        $headers = $request->headers->all('Authorization');
        if (count($headers) > 1 || strlen($headers[0] ?? '') > 8192 || (isset($headers[0]) && !preg_match('/^Bearer +[A-Za-z0-9._~+\/-]+=* *$/iD', $headers[0]))) {
            $event->setResponse(new JsonResponse(['error' => 'invalid_token'], 401, ['WWW-Authenticate' => 'Bearer resource_metadata="'.DocMcpHttpSettings::METADATA_URL.'", scope="docs:read"', 'Cache-Control' => 'private, no-store']));

            return;
        }
        if ('POST' === $request->getMethod() && !$isMetadata) {
            $stream = $request->getContent(true);
            $body = stream_get_contents($stream, 1024 * 1024 + 1);
            if (false === $body || strlen($body) > 1024 * 1024) {
                $event->setResponse(new JsonResponse(['error' => 'payload_too_large'], 413, ['Cache-Control' => 'private, no-store']));

                return;
            }
            $request->attributes->set('_doc_mcp_body', $body);
        }
    }

    #[AsEventListener(event: 'kernel.response', priority: -256)]
    public function audit(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $started = $request->attributes->get('_doc_mcp_started');
        if (!$event->isMainRequest() || !is_float($started)) {
            return;
        }
        $this->events->record(['type' => 'http', 'actor' => $request->attributes->get('_doc_mcp_actor'), 'tool' => $request->attributes->get('_doc_mcp_tool'), 'httpStatus' => $event->getResponse()->getStatusCode(), 'durationMs' => (int) ((microtime(true) - $started) * 1000)]);
    }
}
