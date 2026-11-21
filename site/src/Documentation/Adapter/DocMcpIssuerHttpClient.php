<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

use App\Documentation\DTO\DocMcpHttpSettings;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DocMcpIssuerHttpClient implements ClientInterface
{
    public function __construct(private readonly HttpClientInterface $http, private readonly DocMcpHttpSettings $settings, #[Autowire(service: 'limiter.documentation_mcp_issuer')] private readonly ?RateLimiterFactory $quota = null)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $url = $request->getUri();
        $issuerHost = parse_url($this->settings->issuer, PHP_URL_HOST);
        $hosts = array_filter([$issuerHost, ...$this->settings->jwksHosts]);
        if ('GET' !== $request->getMethod() || 'https' !== $url->getScheme() || '' !== $url->getUserInfo() || '' !== $url->getFragment() || !in_array($url->getHost(), $hosts, true)) {
            throw new DocMcpIssuerUnavailable('Issuer request refused.');
        }
        if (null !== $this->quota && !$this->quota->create(hash('sha256', (string) $url))->consume()->isAccepted()) {
            throw new DocMcpIssuerUnavailable('Issuer request quota exceeded.');
        }
        $options = ['headers' => ['Accept' => 'application/json'], 'timeout' => 2, 'max_duration' => 3, 'max_redirects' => 0, 'verify_peer' => true, 'verify_host' => true];
        if ('' !== $this->settings->caFile) {
            $options['cafile'] = $this->settings->caFile;
        }
        try {
            $response = $this->http->request('GET', (string) $url, $options);
            $status = $response->getStatusCode();
            if (200 !== $status) {
                $response->cancel();

                return new Response($status);
            }
            $body = '';
            foreach ($this->http->stream($response) as $chunk) {
                $body .= $chunk->getContent();
                if (strlen($body) > 262144) {
                    $response->cancel();
                    throw new DocMcpIssuerUnavailable('Issuer document too large.');
                }
            }

            return new Response(200, ['Content-Type' => 'application/json'], $body);
        } catch (\Throwable) {
            throw new DocMcpIssuerUnavailable('Issuer request unavailable.');
        }
    }
}
