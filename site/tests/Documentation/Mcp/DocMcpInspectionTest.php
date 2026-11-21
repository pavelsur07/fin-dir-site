<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Mcp;

use App\Documentation\Mcp\DocMcpAccess;
use App\Documentation\Mcp\DocMcpServer;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocMcpInspectionTest extends KernelTestCase
{
    public function testInspectionDoesNotWriteAndDraftIsNeverPubliclyReadable(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $page = DocPageBuilder::aPage($section);
        $em->persist($page);
        $em->flush();
        $id = $page->id();
        $db = self::getContainer()->get(Connection::class);
        $before = $db->fetchAssociative('SELECT * FROM documentation_page WHERE id=:id', ['id' => $id]);
        $server = self::getContainer()->get(DocMcpServer::class);
        $editor = new DocMcpAccess('test', 'draft-editor');
        $report = $server->call('docs_validate_page', ['id' => $id], $editor);
        self::assertFalse($report->isError);
        self::assertTrue($report->structuredContent['readyToPublish']);
        $preview = $server->call('docs_preview_page', ['id' => $id], $editor);
        self::assertFalse($preview->isError);
        self::assertStringContainsString('<h2', $preview->structuredContent['html']);
        self::assertSame($before, $db->fetchAssociative('SELECT * FROM documentation_page WHERE id=:id', ['id' => $id]));
        self::assertSame(0, (int) $db->fetchOne('SELECT COUNT(*) FROM documentation_tool_receipt'));
        foreach (['docs_get_page', 'docs_get_links', 'docs_preview_page', 'docs_validate_page'] as $tool) {
            self::assertSame('NOT_FOUND', $server->call($tool, ['id' => $id], new DocMcpAccess('test', 'published-reader'))->structuredContent['code']);
        }
        self::assertSame('FORBIDDEN', $server->call('docs_create_draft', [], new DocMcpAccess('test', 'draft-reader'))->structuredContent['code']);
    }

    public function testValidationReportsDraftTargetsAndEscapesUnsafeHtml(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $target = DocPageBuilder::aPage($section, 'target');
        $source = DocPageBuilder::aPage($section, 'source');
        $em->persist($target);
        $em->persist($source);
        $em->flush();
        $source->replaceLinks([['target' => $target, 'type' => \App\Documentation\ValueObject\DocLinkType::INLINE, 'fragment' => 'missing', 'position' => 0, 'urls' => ['doc:'.$target->id().'#missing']]]);
        $em->flush();
        $server = self::getContainer()->get(DocMcpServer::class);
        $result = $server->call('docs_validate_page', ['id' => $source->id()], new DocMcpAccess('test', 'draft-reader'));
        self::assertFalse($result->isError);
        self::assertFalse($result->structuredContent['readyToPublish']);
        self::assertSame(['TARGET_NOT_PUBLISHED', 'MISSING_ANCHOR'], array_column($result->structuredContent['issues'], 'code'));
    }

    public function testInspectionDetectsUntrackedInternalTargetInsteadOfReportingReady(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $page = DocPageBuilder::aPage($section);
        $em->persist($page);
        $em->flush();
        self::getContainer()->get(Connection::class)->executeStatement('UPDATE documentation_page SET body=:body WHERE id=:id', ['body' => '[Ссылка](doc:999999#gone)', 'id' => $page->id()]);
        $result = self::getContainer()->get(DocMcpServer::class)->call('docs_validate_page', ['id' => $page->id()], new DocMcpAccess('test', 'draft-reader'));
        self::assertFalse($result->isError);
        self::assertFalse($result->structuredContent['readyToPublish']);
        self::assertSame('MISSING_TARGET', $result->structuredContent['issues'][0]['code']);
    }
}
