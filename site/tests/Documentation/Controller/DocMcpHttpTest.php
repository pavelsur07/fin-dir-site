<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Controller;

use App\Documentation\Adapter\DocMcpEventLogger;
use App\Documentation\DTO\DocMcpHttpSettings;
use App\Documentation\Service\DocMcpClientGrantEditor;
use App\Tests\Documentation\Builder\DocMcpOAuthFixture;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Log\Logger;

final class DocMcpHttpTest extends WebTestCase
{
    private KernelBrowser $client;
    /** @var array<string,mixed> */ private array $previous = [];

    protected function setUp(): void
    {
        foreach (['DOCS_MCP_HTTP_ENABLED' => '1', 'DOCS_MCP_OAUTH_ISSUER' => DocMcpOAuthFixture::ISSUER] as $key => $value) {
            $this->previous[$key] = $_ENV[$key] ?? null;
            $_ENV[$key] = $value;
        }
        $this->client = self::createClient();
        $this->client->disableReboot();
        self::getContainer()->set('http_client', DocMcpOAuthFixture::http());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->previous as $key => $value) {
            if (null === $value) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * @param array<string,mixed> $args
     *
     * @return array<string,mixed>
     */
    private function rpc(string $method, array $args = [], ?string $token = null, bool $modern = false): array
    {
        $payload = ['jsonrpc' => '2.0', 'id' => 12, 'method' => $method, 'params' => (object) $args];
        if ($modern) {
            $payload['params'] = (object) ($args + ['_meta' => ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientInfo' => ['name' => 'http-tests', 'version' => '1'], 'io.modelcontextprotocol/clientCapabilities' => new \stdClass()]]);
        }
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream', 'HTTP_MCP_PROTOCOL_VERSION' => $modern ? '2026-07-28' : '2025-11-25'];
        if ($modern) {
            $server['HTTP_MCP_METHOD'] = $method;
            if (isset($args['name'])) {
                $server['HTTP_MCP_NAME'] = $args['name'];
            }
        }
        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        $this->client->request('POST', DocMcpHttpSettings::RESOURCE, server: $server, content: json_encode($payload, JSON_THROW_ON_ERROR));

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function allow(): string
    {
        self::getContainer()->get(DocMcpClientGrantEditor::class)->allow(DocMcpOAuthFixture::ISSUER, 'reader-one');

        return DocMcpOAuthFixture::token();
    }

    public function testMetadataAndMissingTokenChallengeNeverStartSession(): void
    {
        $this->client->request('GET', 'https://vashfindir.ru'.DocMcpHttpSettings::METADATA_PATH);
        self::assertResponseIsSuccessful();
        self::assertSame(['docs:read'], json_decode((string) $this->client->getResponse()->getContent(), true)['scopes_supported']);
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
        $this->rpc('tools/list');
        self::assertResponseStatusCodeSame(401);
        self::assertStringContainsString(DocMcpHttpSettings::METADATA_URL, (string) $this->client->getResponse()->headers->get('WWW-Authenticate'));
        self::assertNull($this->client->getResponse()->headers->get('Location'));
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
    }

    public function testBothRevisionsExposeOnlySixPublishedReadToolsAndNoCookie(): void
    {
        $token = $this->allow();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $public = DocPageBuilder::aPage($section, 'visible', true);
        $draft = DocPageBuilder::aPage($section, 'private');
        $em->persist($public);
        $em->persist($draft);
        $em->flush();
        foreach ([false, true] as $modern) {
            $tools = $this->rpc('tools/list', token: $token, modern: $modern);
            self::assertResponseIsSuccessful();
            self::assertCount(6, $tools['result']['tools']);
            $data = $this->rpc('tools/call', ['name' => 'docs_search', 'arguments' => new \stdClass()], $token, $modern);
            self::assertResponseIsSuccessful();
            self::assertSame(1, $data['result']['structuredContent']['total']);
            self::assertStringNotContainsString('private', json_encode($data, JSON_THROW_ON_ERROR));
            self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
            self::assertSame([], $this->client->getResponse()->headers->getCookies());
            self::assertNull($this->client->getResponse()->headers->get('Mcp-Session-Id'));
        }
        $data = $this->rpc('tools/call', ['name' => 'docs_get_page', 'arguments' => ['id' => $draft->id()]], $token);
        self::assertSame('NOT_FOUND', $data['result']['structuredContent']['code']);
        $write = $this->rpc('tools/call', ['name' => 'docs_create_draft', 'arguments' => new \stdClass()], $token);
        self::assertArrayHasKey('error', $write);
        self::assertSame(0, (int) self::getContainer()->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM documentation_tool_receipt'));
    }

    public function testClientRevocationAndMissingNextTokenCannotReuseAuthentication(): void
    {
        $token = $this->allow();
        $this->rpc('tools/list', token: $token);
        self::assertResponseIsSuccessful();
        $this->rpc('tools/list');
        self::assertResponseStatusCodeSame(401);
        self::getContainer()->get(DocMcpClientGrantEditor::class)->disable(DocMcpOAuthFixture::ISSUER, 'reader-one');
        $this->rpc('tools/list', token: $token);
        self::assertResponseStatusCodeSame(403);
        self::getContainer()->get(DocMcpClientGrantEditor::class)->allow(DocMcpOAuthFixture::ISSUER, 'reader-one');
        $this->rpc('tools/list', token: DocMcpOAuthFixture::token(['jti' => 'refreshed']));
        self::assertResponseIsSuccessful();
    }

    public function testScopeUnknownClientAndAdminSessionCannotEscalate(): void
    {
        $this->allow();
        $this->rpc('tools/list', token: DocMcpOAuthFixture::token(['scope' => 'admin']));
        self::assertResponseStatusCodeSame(403);
        self::assertStringContainsString('insufficient_scope', (string) $this->client->getResponse()->headers->get('WWW-Authenticate'));
        $this->rpc('tools/list', token: DocMcpOAuthFixture::token(['client_id' => 'unknown']));
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', 'https://vashfindir.ru/admin/docs/pages', server: ['HTTP_AUTHORIZATION' => 'Bearer '.DocMcpOAuthFixture::token()]);
        self::assertResponseRedirects('https://vashfindir.ru/admin/login');
    }

    public function testOriginMethodsSizeAndEncodedPathsAreRejected(): void
    {
        $token = $this->allow();
        $this->client->request('OPTIONS', DocMcpHttpSettings::RESOURCE);
        self::assertResponseStatusCodeSame(204);
        $this->client->request('POST', DocMcpHttpSettings::RESOURCE, server: ['HTTP_ORIGIN' => 'https://evil.example']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', DocMcpHttpSettings::RESOURCE, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseStatusCodeSame(405);
        $this->client->request('POST', DocMcpHttpSettings::RESOURCE, content: str_repeat('x', 1024 * 1024 + 1));
        self::assertResponseStatusCodeSame(413);
        foreach (['https://docs.vashfindir.ru/mcp/docs', 'https://vashfindir.ru/mcp/docs/', 'https://vashfindir.ru/mcp%2fdocs'] as $url) {
            $this->client->request('POST', $url);
            self::assertResponseStatusCodeSame(404);
        }
        $data = $this->rpc('subscriptions/listen', token: $token, modern: true);
        self::assertSame(-32601, $data['error']['code']);
        self::assertSame(12, $data['id']);
    }

    public function testSharedRateLimitFactoriesRejectBeforeTool(): void
    {
        self::getContainer()->get('limiter.documentation_mcp_ip')->create('127.0.0.1')->consume(60);
        $this->rpc('tools/list');
        self::assertResponseStatusCodeSame(429);
        self::assertNotNull($this->client->getResponse()->headers->get('Retry-After'));
    }

    public function testPerClientLimitRejectsAnOtherwiseValidToken(): void
    {
        $token = $this->allow();
        $identity = self::getContainer()->get(\App\Documentation\Adapter\DocMcpOAuthVerifier::class)->verify($token);
        self::getContainer()->get('limiter.documentation_mcp_client')->create($identity->actor())->consume(120);
        $this->rpc('tools/list', token: $token);
        self::assertResponseStatusCodeSame(429);
    }

    public function testDisabledFeatureRejectsBeforeOAuthDiscovery(): void
    {
        self::getContainer()->set(DocMcpHttpSettings::class, new DocMcpHttpSettings(false, '', [], []));
        $this->rpc('tools/list', token: DocMcpOAuthFixture::token());
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', 'https://vashfindir.ru'.DocMcpHttpSettings::METADATA_PATH);
        self::assertResponseStatusCodeSame(404);
    }

    public function testHttpAuditIncludesCorrelationAndStatusWithoutCredentials(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertNotFalse($stream);
        $token = $this->allow();
        self::getContainer()->set(DocMcpEventLogger::class, new DocMcpEventLogger(self::getContainer()->get('request_stack'), new Logger('info', $stream)));
        $this->rpc('tools/list', token: $token);
        $this->rpc('tools/list');
        rewind($stream);
        $log = (string) stream_get_contents($stream);
        self::assertStringContainsString('"httpStatus":200', $log);
        self::assertStringContainsString('"httpStatus":401', $log);
        self::assertStringContainsString('"requestId":12', $log);
        self::assertStringContainsString('"actor":"http.', $log);
        self::assertStringNotContainsString($token, $log);
        self::assertStringNotContainsString('Authorization', $log);
        fclose($stream);
    }

    public function testUnknownMethodCannotExpandResponsePastBudget(): void
    {
        $data = $this->rpc(str_repeat('<', 200000), token: $this->allow());
        self::assertResponseStatusCodeSame(400);
        self::assertSame(12, $data['id']);
        self::assertSame(-32601, $data['error']['code']);
        self::assertLessThanOrEqual(1024 * 1024, strlen((string) $this->client->getResponse()->getContent()));
    }

    public function testLargeInvalidInputKeepsBoundedCorrelatedProtocolResponse(): void
    {
        $data = $this->rpc('tools/call', ['name' => 'docs_get_page', 'arguments' => ['id' => 1, str_repeat('x', 900000) => 'invalid']], $this->allow());
        self::assertSame(12, $data['id']);
        self::assertLessThanOrEqual(1024 * 1024, strlen((string) $this->client->getResponse()->getContent()));
        self::assertArrayHasKey('error', $data);
    }
}
