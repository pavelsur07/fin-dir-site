<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Builder;

use App\Documentation\DTO\DocMcpHttpSettings;
use Firebase\JWT\JWT;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DocMcpOAuthFixture
{
    public const string ISSUER = 'https://mcp-issuer.test';
    /** @var array<string,string> */ private static array $privateKeys = [];
    /** @var array<string,array<string,mixed>> */ private static array $jwks = [];

    /** @return array<string,mixed> */
    public static function jwk(string $id = 'key-a'): array
    {
        if (isset(self::$jwks[$id])) {
            return self::$jwks[$id];
        }
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (false === $key || !openssl_pkey_export($key, $private)) {
            throw new \RuntimeException('Cannot generate test key.');
        }
        $details = openssl_pkey_get_details($key);
        if (false === $details) {
            throw new \RuntimeException('Missing test key details.');
        }
        $encode = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        self::$privateKeys[$id] = $private;

        return self::$jwks[$id] = ['kty' => 'RSA', 'kid' => $id, 'use' => 'sig', 'alg' => 'RS256', 'n' => $encode($details['rsa']['n']), 'e' => $encode($details['rsa']['e'])];
    }

    /**
     * @param array<string,mixed> $claims
     * @param array<string,mixed> $headers
     */
    public static function token(array $claims = [], array $headers = [], string $keyId = 'key-a'): string
    {
        self::jwk($keyId);

        return JWT::encode($claims + ['iss' => self::ISSUER, 'aud' => DocMcpHttpSettings::RESOURCE, 'iat' => time() - 1, 'exp' => time() + 180, 'client_id' => 'reader-one', 'scope' => 'docs:read'], self::$privateKeys[$keyId], 'RS256', $keyId, $headers + ['typ' => 'at+jwt']);
    }

    public static function http(): MockHttpClient
    {
        return new MockHttpClient(static function (string $method, string $url): MockResponse {
            $data = str_contains($url, '.well-known') ? ['issuer' => self::ISSUER, 'jwks_uri' => self::ISSUER.'/jwks'] : ['keys' => [self::jwk()]];

            return new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), ['http_code' => 200, 'response_headers' => ['content-type: application/json']]);
        });
    }
}
