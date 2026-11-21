<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Mcp;

use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mcp\Client\Client;
use Mcp\Client\Exception\RequestException;
use Mcp\Client\Transport\StdioTransport;
use Mcp\Schema\Enum\ProtocolVersion;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocMcpProtocolTest extends KernelTestCase
{
    public function testRealClientBothProtocolErasAndCompleteDraftWorkflow(): void
    {
        $default = self::getContainer()->get(Connection::class);
        $params = $default->getParams();
        self::assertStringEndsWith('_test', (string) ($params['dbname'] ?? ''));
        foreach (['user', 'password', 'host', 'port'] as $parameter) {
            self::assertArrayHasKey($parameter, $params);
        }
        $admin = DriverManager::getConnection($params);
        $database = 'vf_mcp_'.bin2hex(random_bytes(6)).'_test';
        $admin->executeStatement('CREATE DATABASE '.$database);
        $clients = [];
        $sandbox = null;
        try {
            $env = getenv();
            $env['APP_ENV'] = 'test';
            $env['APP_DEBUG'] = '0';
            $env['DOCS_MCP_ENABLED'] = '1';
            $env['DOCS_MCP_ACTOR'] = 'protocol-test';
            $env['DATABASE_URL'] = sprintf('postgresql://%s:%s@%s:%d/%s?serverVersion=17&charset=utf8', rawurlencode($params['user'] ?? ''), rawurlencode($params['password'] ?? ''), $params['host'] ?? '', $params['port'] ?? 5432, substr($database, 0, -5));
            $migration = $this->process(['doctrine:migrations:migrate', '--no-interaction', '--allow-no-migration', '--no-debug'], $env);
            self::assertSame(0, $migration['exit'], $migration['stderr']);
            $params['dbname'] = $database;
            $sandbox = DriverManager::getConnection($params);
            $em = new EntityManager($sandbox, self::getContainer()->get(EntityManagerInterface::class)->getConfiguration());
            $section = DocSectionBuilder::aSection();
            $empty = DocSectionBuilder::aSection('empty');
            $target = DocPageBuilder::aPage($section, 'published', true);
            $private = DocPageBuilder::aPage($section, 'private');
            foreach ([$section, $empty, $target, $private] as $entity) {
                $em->persist($entity);
            }
            $em->flush();
            foreach ([ProtocolVersion::V2025_11_25, ProtocolVersion::V2026_07_28] as $revision) {
                $env['DOCS_MCP_PROFILE'] = 'draft-editor';
                $client = $this->client($env, $revision);
                $clients[] = $client;
                self::assertCount(8, $client->listTools()->tools);
                foreach ($client->listTools()->tools as $tool) {
                    self::assertNotNull($tool->outputSchema);
                }
                self::assertSame(2, $client->callTool('docs_list_sections')->structuredContent['total']);
                $suffix = ProtocolVersion::V2025_11_25 === $revision ? 'legacy' : 'modern';
                $key = '11111111-1111-4111-8111-'.('legacy' === $suffix ? '111111111111' : '222222222222');
                $body = "    [Пример](doc:999999)\n\n## Настройка\n\n[Инструкция](doc:".$target->id().")\n\n<script>alert('bad')</script>";
                $page = ['title' => 'Платёжный календарь', 'slug' => 'calendar-'.$suffix, 'sectionId' => $section->id(), 'description' => 'Проверка', 'body' => $body, 'relatedIds' => [$target->id()]];
                $write = ['requestKey' => $key, 'page' => $page];
                $created = $client->callTool('docs_create_draft', $write);
                self::assertFalse($created->isError, json_encode($created, JSON_THROW_ON_ERROR));
                $id = $created->structuredContent['pageId'];
                self::assertTrue($client->callTool('docs_create_draft', $write)->structuredContent['replayed']);
                $loaded = $client->callTool('docs_get_page', ['id' => $id]);
                self::assertSame($body, $loaded->structuredContent['body']);
                self::assertSame([$target->id()], $loaded->structuredContent['relatedIds']);
                self::assertTrue($client->callTool('docs_validate_page', ['id' => $id])->structuredContent['readyToPublish']);
                $preview = $client->callTool('docs_preview_page', ['id' => $id]);
                self::assertFalse($preview->isError);
                self::assertStringNotContainsString('<script', $preview->structuredContent['html']);
                self::assertStringContainsString('/articles/published', $preview->structuredContent['html']);
                self::assertSame(2, $client->callTool('docs_get_links', ['id' => $id])->structuredContent['total']);
                $update = ['id' => $id, 'expectedVersion' => $loaded->structuredContent['version'], 'requestKey' => '22222222-2222-4222-8222-'.('legacy' === $suffix ? '111111111111' : '222222222222'), 'page' => array_replace($page, ['title' => 'Изменённый календарь'])];
                self::assertFalse($client->callTool('docs_update_draft', $update)->isError);
                self::assertTrue($client->callTool('docs_update_draft', $update)->structuredContent['replayed']);
                $stale = $update;
                $stale['requestKey'] = '33333333-3333-4333-8333-'.('legacy' === $suffix ? '111111111111' : '222222222222');
                self::assertSame('VERSION_CONFLICT', $client->callTool('docs_update_draft', $stale)->structuredContent['code']);
                $collision = $write;
                $collision['requestKey'] = $stale['requestKey'];
                self::assertSame('CONTENT_CONFLICT', $client->callTool('docs_create_draft', $collision)->structuredContent['code']);
                self::assertFalse($client->callTool('docs_search', ['q' => 'календарь'])->isError);
                if ('modern' === $suffix) {
                    $large = ['requestKey' => '88888888-8888-4888-8888-888888888888', 'page' => array_replace($page, ['slug' => 'long-error', 'body' => '[x](#'.str_repeat('я', 90000).')'])];
                    self::assertSame('LIMIT_EXCEEDED', $client->callTool('docs_create_draft', $large)->structuredContent['code']);
                    self::assertFalse($client->callTool('docs_search')->isError);
                }

                // Simulate a committed Admin publication, then read through the same long-lived process.
                $sandbox->executeStatement("UPDATE documentation_page SET status='published', published_at=CURRENT_TIMESTAMP,version=version+1 WHERE id=:id", ['id' => $id]);
                self::assertSame('published', $client->callTool('docs_get_page', ['id' => $id])->structuredContent['status']);
                $stale['expectedVersion'] = $client->callTool('docs_get_page', ['id' => $id])->structuredContent['version'];
                self::assertSame('PAGE_NOT_DRAFT', $client->callTool('docs_update_draft', $stale)->structuredContent['code']);
                self::assertTrue($client->callTool('docs_update_draft', $update)->structuredContent['replayed']);
                try {
                    $client->callTool('docs_get_page', ['id' => (string) $id, 'actor' => 'admin']);
                    self::fail('Schema accepted coercion/unknown fields.');
                } catch (RequestException $error) {
                    self::assertSame(-32602, $error->getCode());
                }
                $client->disconnect();
            }
            $env['DOCS_MCP_PROFILE'] = 'draft-editor';
            $parallelCreate = ['requestKey' => '55555555-5555-4555-8555-555555555555', 'page' => array_replace($page, ['slug' => 'concurrent-draft'])];
            $race = $this->concurrentCalls('docs_create_draft', [$parallelCreate, $parallelCreate], $env, $sandbox);
            self::assertFalse($race[0]['isError']);
            self::assertFalse($race[1]['isError']);
            self::assertSame($race[0]['structuredContent']['pageId'], $race[1]['structuredContent']['pageId']);
            self::assertNotSame($race[0]['structuredContent']['replayed'], $race[1]['structuredContent']['replayed']);
            $raceId = $race[0]['structuredContent']['pageId'];
            $parallelUpdate = ['id' => $raceId, 'expectedVersion' => 1, 'requestKey' => '66666666-6666-4666-8666-666666666666', 'page' => $parallelCreate['page']];
            $secondUpdate = $parallelUpdate;
            $secondUpdate['requestKey'] = '77777777-7777-4777-8777-777777777777';
            $publishedRace = $this->concurrentCalls('docs_update_draft', [$parallelUpdate, $secondUpdate], $env, $sandbox, function () use ($sandbox, $raceId): void {
                $sandbox->executeStatement("UPDATE documentation_page SET status='published', published_at=CURRENT_TIMESTAMP,version=version+1 WHERE id=:id", ['id' => $raceId]);
            });
            self::assertSame(['PAGE_NOT_DRAFT', 'PAGE_NOT_DRAFT'], array_map(static fn (array $result): string => $result['structuredContent']['code'], $publishedRace));
            self::assertSame(1, (int) $sandbox->fetchOne('SELECT COUNT(*) FROM documentation_tool_receipt WHERE page_id=:id', ['id' => $raceId]));
            foreach (['published-reader', 'draft-reader'] as $profile) {
                $env['DOCS_MCP_PROFILE'] = $profile;
                $client = $this->client($env, ProtocolVersion::V2026_07_28);
                $clients[] = $client;
                self::assertCount(6, $client->listTools()->tools);
                if ('published-reader' === $profile) {
                    foreach (['docs_get_page', 'docs_get_links', 'docs_preview_page', 'docs_validate_page'] as $tool) {
                        self::assertSame('NOT_FOUND', $client->callTool($tool, ['id' => $private->id()])->structuredContent['code']);
                    }
                    self::assertSame('FORBIDDEN', $client->callTool('docs_search', ['status' => 'draft'])->structuredContent['code']);
                } else {
                    self::assertSame('draft', $client->callTool('docs_get_page', ['id' => $private->id()])->structuredContent['status']);
                }
                try {
                    $client->callTool('docs_create_draft', $write);
                    self::fail('Reader discovered a write tool.');
                } catch (RequestException $error) {
                    self::assertSame(-32602, $error->getCode());
                }
                $client->disconnect();
            }
            $env['DOCS_MCP_PROFILE'] = 'published-reader';
            $initialize = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-11-25', 'capabilities' => new \stdClass(), 'clientInfo' => ['name' => 'raw-fixture', 'version' => '1']]];
            $raw = $this->process(['app:docs:mcp', '--no-debug'], $env, str_repeat('x', 1024 * 1024 + 100)."\n".json_encode($initialize, JSON_THROW_ON_ERROR)."\n{bad-json}\n");
            self::assertSame(0, $raw['exit']);
            $frames = array_map(static fn (string $line): mixed => json_decode($line, true, 512, JSON_THROW_ON_ERROR), array_filter(explode("\n", $raw['stdout'])));
            self::assertCount(2, $frames);
            self::assertSame(1, $frames[0]['id']);
            self::assertSame(-32700, $frames[1]['error']['code']);
            self::assertStringNotContainsString($body, $raw['stderr']);
            $env['DOCS_MCP_ENABLED'] = '0';
            $disabled = $this->process(['app:docs:mcp', '--no-debug'], $env);
            self::assertSame(1, $disabled['exit']);
            self::assertSame('', $disabled['stdout']);
        } finally {
            foreach ($clients as $client) {
                $client->disconnect();
            }
            $sandbox?->close();
            $admin->executeStatement('DROP DATABASE '.$database.' WITH (FORCE)');
            $admin->close();
        }
    }

    /**
     * @param array<string,string> $env */
    private function client(array $env, ProtocolVersion $version): Client
    {
        $client = Client::builder()->setClientInfo('documentation-integration-tests', '1.0.0')->setProtocolVersion($version)->setMaxRetries(0)->setInitTimeout(10)->setRequestTimeout(15)->build();
        $client->connect(new StdioTransport(PHP_BINARY, ['bin/console', 'app:docs:mcp', '--no-debug', '--no-ansi', '--no-interaction'], dirname(__DIR__, 3), $env));

        return $client;
    }

    /** @param list<string> $args
     * @param array<string,string> $env
     *
     * @return array{exit:int,stdout:string,stderr:string} */
    private function process(array $args, array $env, string $stdin = ''): array
    {
        $process = proc_open([PHP_BINARY, 'bin/console', ...$args], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, dirname(__DIR__, 3), $env);
        self::assertIsResource($process);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * @param list<array<string,mixed>> $calls
     * @param array<string,string>      $env
     *
     * @return list<array<string,mixed>>
     */
    private function concurrentCalls(string $tool, array $calls, array $env, Connection $db, ?\Closure $beforeRelease = null): array
    {
        $processes = [];
        $db->beginTransaction();
        $db->executeQuery('SELECT pg_advisory_xact_lock(1146045267,1)');
        try {
            foreach ($calls as $args) {
                $process = proc_open([PHP_BINARY, 'bin/console', 'app:docs:mcp', '--no-debug'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, dirname(__DIR__, 3), $env);
                self::assertIsResource($process);
                $processes[] = [$process, $pipes];
                $initialize = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-11-25', 'capabilities' => new \stdClass(), 'clientInfo' => ['name' => 'concurrency-test', 'version' => '1']]];
                $call = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $args]];
                fwrite($pipes[0], json_encode($initialize, JSON_THROW_ON_ERROR)."\n".json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], JSON_THROW_ON_ERROR)."\n".json_encode($call, JSON_THROW_ON_ERROR)."\n");
                fclose($pipes[0]);
            }
            $deadline = microtime(true) + 10;
            do {
                $waiting = (int) $db->fetchOne("SELECT COUNT(*) FROM pg_locks WHERE locktype='advisory' AND classid=1146045267 AND objid=1 AND NOT granted");
                if ($waiting >= count($calls)) {
                    break;
                }
                usleep(20000);
            } while (microtime(true) < $deadline);
            self::assertSame(count($calls), $waiting, 'Both workers must actually wait for the shared write lock.');
            $beforeRelease?->__invoke();
            $db->commit();
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                self::assertSame(0, proc_close($process), $stderr);
                $frames = array_map(static fn (string $line): mixed => json_decode($line, true, 512, JSON_THROW_ON_ERROR), array_filter(explode("\n", $stdout)));
                self::assertCount(2, $frames);
                $results[] = $frames[1]['result'];
            }
            $processes = [];

            return $results;
        } finally {
            if ($db->isTransactionActive()) {
                $db->rollBack();
            }
            foreach ($processes as [$process, $pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }
        }
    }
}
