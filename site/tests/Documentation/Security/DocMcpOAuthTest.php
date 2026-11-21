<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Security;

use App\Documentation\Adapter\DocMcpIssuerHttpClient;
use App\Documentation\Adapter\DocMcpIssuerUnavailable;
use App\Documentation\Adapter\DocMcpOAuthVerifier;
use App\Documentation\DTO\DocMcpHttpSettings;
use App\Documentation\Exception\DocMcpOAuthFailure;
use App\Tests\Documentation\Builder\DocMcpOAuthFixture;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DocMcpOAuthTest extends TestCase
{
    private function verifier(?MockHttpClient $http = null): DocMcpOAuthVerifier
    {
        $settings = new DocMcpHttpSettings(true, DocMcpOAuthFixture::ISSUER, [], []);

        return new DocMcpOAuthVerifier($settings, new DocMcpIssuerHttpClient($http ?? DocMcpOAuthFixture::http(), $settings), new ArrayAdapter(), new NativeClock());
    }

    public function testRealSignatureAndStableIdentityAcrossTokenRefresh(): void
    {
        $verify = $this->verifier();
        $first = $verify->verify(DocMcpOAuthFixture::token());
        $second = $verify->verify(DocMcpOAuthFixture::token(['jti' => 'refresh']));
        self::assertSame($first->actor(), $second->actor());
        self::assertSame(['docs:read'], $first->scopes);
        self::assertSame(64, strlen($first->actor()));
        $parts = explode('.', DocMcpOAuthFixture::token());
        $signature = $parts[2];
        $parts[2] = ('a' === $signature[0] ? 'b' : 'a').substr($signature, 1);
        $this->expectException(DocMcpOAuthFailure::class);
        $verify->verify(implode('.', $parts));
    }

    public function testInvalidClaimsCannotBecomeAuthenticated(): void
    {
        $verify = $this->verifier();
        foreach ([['iss' => 'https://evil.example'], ['aud' => 'https://vashfindir.ru/'], ['exp' => time() - 90], ['exp' => null], ['iat' => (string) time()], ['exp' => time() + 900], ['iat' => time() + 90], ['nbf' => time() + 90], ['client_id' => null], ['azp' => 'other-client'], ['scope' => ['docs:read']]] as $claims) {
            try {
                $verify->verify(DocMcpOAuthFixture::token($claims));
                self::fail('Invalid claims accepted.');
            } catch (DocMcpOAuthFailure $e) {
                self::assertFalse($e->unavailable);
            }
        }
        try {
            $verify->verify(DocMcpOAuthFixture::token(headers: ['typ' => 'JWT']));
            self::fail('ID token type accepted.');
        } catch (DocMcpOAuthFailure $e) {
            self::assertFalse($e->unavailable);
        }
    }

    public function testMalformedAndWrongAlgorithmDoNotFetchKeys(): void
    {
        $http = DocMcpOAuthFixture::http();
        $verify = $this->verifier($http);
        $tokens = ['broken', str_repeat('x', 8193), \Firebase\JWT\JWT::encode(['exp' => time() + 90], str_repeat('x', 64), 'HS256', 'bad', ['typ' => 'at+jwt'])];
        foreach ($tokens as $token) {
            try {
                $verify->verify($token);
                self::fail();
            } catch (DocMcpOAuthFailure $e) {
                self::assertFalse($e->unavailable);
            }
        }
        self::assertSame(0, $http->getRequestsCount());
    }

    public function testIssuerOutageIsUnavailableAndFailuresAreCached(): void
    {
        $http = new MockHttpClient(static fn (): MockResponse => new MockResponse('', ['http_code' => 503]));
        $verify = $this->verifier($http);
        for ($i = 0; $i < 2; ++$i) {
            try {
                $verify->verify(DocMcpOAuthFixture::token());
                self::fail();
            } catch (DocMcpOAuthFailure $e) {
                self::assertTrue($e->unavailable);
            }
        }
        self::assertSame(2, $http->getRequestsCount());
    }

    public function testNewSigningKeyRefreshesAndUnknownKidFloodIsBounded(): void
    {
        $keys = [DocMcpOAuthFixture::jwk()];
        $http = new MockHttpClient(static function (string $method, string $url) use (&$keys): MockResponse {
            $data = str_contains($url, '.well-known') ? ['issuer' => DocMcpOAuthFixture::ISSUER, 'jwks_uri' => DocMcpOAuthFixture::ISSUER.'/jwks'] : ['keys' => $keys];

            return new MockResponse(json_encode($data, JSON_THROW_ON_ERROR));
        });
        $verify = $this->verifier($http);
        $verify->verify(DocMcpOAuthFixture::token());
        $keys[] = DocMcpOAuthFixture::jwk('key-b');
        self::assertSame('reader-one', $verify->verify(DocMcpOAuthFixture::token(keyId: 'key-b'))->clientId);
        self::assertSame(3, $http->getRequestsCount());
        for ($i = 0; $i < 25; ++$i) {
            try {
                $verify->verify(DocMcpOAuthFixture::token(keyId: 'missing-'.$i));
                self::fail('Unknown key accepted.');
            } catch (DocMcpOAuthFailure) {
                self::assertLessThanOrEqual(11, $http->getRequestsCount());
            }
        }
    }

    public function testUntrustedJwksHostAndOversizedDocumentAreRefused(): void
    {
        $settings = new DocMcpHttpSettings(true, DocMcpOAuthFixture::ISSUER, [], []);
        $http = DocMcpOAuthFixture::http();
        $client = new DocMcpIssuerHttpClient($http, $settings);
        try {
            $client->sendRequest(new Request('GET', 'https://internal.example/secret'));
            self::fail();
        } catch (DocMcpIssuerUnavailable) {
            self::assertSame(0, $http->getRequestsCount());
        }
        $huge = new MockHttpClient(static fn (): MockResponse => new MockResponse(str_repeat('x', 262145)));
        $client = new DocMcpIssuerHttpClient($huge, $settings);
        $this->expectException(DocMcpIssuerUnavailable::class);
        $client->sendRequest(new Request('GET', DocMcpOAuthFixture::ISSUER.'/jwks'));
    }
}
