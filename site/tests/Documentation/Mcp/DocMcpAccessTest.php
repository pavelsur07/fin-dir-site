<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Mcp;

use App\Documentation\Exception\DocToolFailure;
use App\Documentation\Mcp\DocMcpAccess;
use App\Documentation\Mcp\DocMcpSchema;
use Mcp\Server\Capability\Discovery\SchemaValidator;
use PHPUnit\Framework\TestCase;

final class DocMcpAccessTest extends TestCase
{
    public function testOutputCapAlsoCoversProtocolFramesAndResumesAfterOversizedFrame(): void
    {
        $name = 'documentation.mcp.test-output';
        if (!in_array($name, stream_get_filters(), true)) {
            stream_filter_register($name, \App\Documentation\Mcp\DocMcpOutputFilter::class);
        }
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        stream_filter_append($stream, $name, STREAM_FILTER_WRITE);
        fwrite($stream, str_repeat('x', 1024 * 1024 + 1)."\n");
        fwrite($stream, "{\"jsonrpc\":\"2.0\",\"id\":1,\"result\":{}}\n");
        rewind($stream);
        $first = json_decode((string) fgets($stream), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(-32603, $first['error']['code']);
        self::assertSame(1, json_decode((string) fgets($stream), true, 512, JSON_THROW_ON_ERROR)['id']);
        fclose($stream);
    }

    public function testHugeDomainErrorRemainsABoundedToolResult(): void
    {
        $mapper = new \App\Documentation\Mcp\DocMcpResultMapper();
        $result = $mapper->error(new \App\Documentation\Exception\DocConflict(str_repeat('я', 90000)));
        self::assertLessThan(1024 * 1024 - 8192, strlen(json_encode($result, JSON_THROW_ON_ERROR)));
        self::assertSame('LIMIT_EXCEEDED', $result->structuredContent['code']);
    }

    public function testProfilesCannotEscalateOrGrantUnknownCapabilities(): void
    {
        foreach (['published-reader', 'draft-reader', 'draft-editor'] as $profile) {
            $access = new DocMcpAccess('integration.one', $profile);
            $access->assertAllowed('docs:read');
            self::assertSame('published-reader' !== $profile, $access->canReadDrafts());
            try {
                $access->assertAllowed('docs:publish');
                self::fail();
            } catch (DocToolFailure $e) {
                self::assertSame('FORBIDDEN', $e->reason);
            }
            if ('draft-editor' !== $profile) {
                try {
                    $access->assertAllowed('docs:drafts:write');
                    self::fail();
                } catch (DocToolFailure $e) {
                    self::assertSame('FORBIDDEN', $e->reason);
                }
            }
        }
        $this->expectException(\InvalidArgumentException::class);
        new DocMcpAccess('client', 'admin');
    }

    public function testSchemasRejectUnknownFieldsCoercionAndTooManyRelatedIds(): void
    {
        $validator = new SchemaValidator();
        $schemas = DocMcpSchema::inputs();
        self::assertCount(8, $schemas);
        $valid = ['requestKey' => '11111111-1111-4111-8111-111111111111', 'page' => ['title' => 'Статья', 'slug' => 'article', 'sectionId' => 1]];
        self::assertSame([], $validator->validateAgainstJsonSchema(json_decode(json_encode($valid, JSON_THROW_ON_ERROR)), $schemas['docs_create_draft']));
        foreach ([['id' => '1'], ['id' => 0], ['id' => 1, 'actor' => 'admin']] as $invalid) {
            self::assertNotEmpty($validator->validateAgainstJsonSchema((object) $invalid, $schemas['docs_get_page']));
        }
        foreach ([['status' => 'published'], ['relatedLinks' => '[x](doc:1)'], ['relatedIds' => [1, 1]], ['relatedIds' => [1, 2, 3, 4, 5, 6]], ['position' => true]] as $extra) {
            $args = $valid;
            $args['page'] += $extra;
            self::assertNotEmpty($validator->validateAgainstJsonSchema(json_decode(json_encode($args, JSON_THROW_ON_ERROR)), $schemas['docs_create_draft']));
        }
        $this->expectException(DocToolFailure::class);
        DocMcpSchema::assertIntegerTypes(['id' => 1.0]);
    }
}
