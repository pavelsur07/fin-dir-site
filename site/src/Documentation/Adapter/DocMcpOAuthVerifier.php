<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

use App\Documentation\DTO\DocMcpHttpSettings;
use App\Documentation\DTO\DocMcpTokenIdentity;
use App\Documentation\Exception\DocMcpOAuthFailure;
use Mcp\Server\Transport\Http\OAuth\JwtTokenValidator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class DocMcpOAuthVerifier
{
    public function __construct(private readonly DocMcpHttpSettings $settings, private readonly DocMcpIssuerHttpClient $http, #[Autowire(service: 'cache.documentation_mcp_jwks')] private readonly CacheItemPoolInterface $cache, private readonly ClockInterface $clock)
    {
    }

    public function verify(#[\SensitiveParameter] string $token): DocMcpTokenIdentity
    {
        if (strlen($token) > 8192) {
            throw new DocMcpOAuthFailure();
        }
        $parts = parse_url($this->settings->issuer);
        if (false === $parts || 'https' !== ($parts['scheme'] ?? '') || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new DocMcpOAuthFailure(true);
        }
        try {
            $validator = JwtTokenValidator::fromIssuer($this->settings->issuer, DocMcpHttpSettings::RESOURCE, $this->cache, $this->http, new Psr17Factory(), ['RS256'], 'scope', 'at+jwt', 30);
            $result = $validator->validate($token);
        } catch (\Throwable) {
            throw new DocMcpOAuthFailure(true);
        }
        $access = $result->getAccessToken();
        if (!$result->isAllowed() || null === $access) {
            throw new DocMcpOAuthFailure(503 === $result->getStatusCode());
        }
        $claims = $access->getClaims();
        $now = $this->clock->now()->getTimestamp();
        $client = $claims['client_id'] ?? null;
        if (!is_string($client) || '' === $client || mb_strlen($client) > 255 || !is_int($claims['iat'] ?? null) || !is_int($claims['exp'] ?? null)
            || $claims['iat'] > $now + 30 || $claims['exp'] <= $claims['iat'] || $claims['exp'] - $claims['iat'] > 300
            || !is_string($claims['scope'] ?? null) || (isset($claims['azp']) && $claims['azp'] !== $client)
            || (isset($claims['nbf']) && (!is_int($claims['nbf']) || $claims['nbf'] > $now + 30))) {
            throw new DocMcpOAuthFailure();
        }

        return new DocMcpTokenIdentity($this->settings->issuer, $client, $access->getScopes(), $claims['exp']);
    }
}
