<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Service;

use App\Documentation\DTO\DocDraftWriteInput;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Exception\DocToolFailure;
use App\Documentation\Query\AdminDocQuery;
use App\Documentation\Service\DocDraftCreator;
use App\Documentation\Service\DocDraftEditor;
use App\Documentation\Service\DocMcpPersistenceResetter;
use App\Documentation\Service\DocPagePublisher;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocMcpDraftWritesTest extends KernelTestCase
{
    /** @param array<string,mixed> $fields */
    private function input(string $key, array $fields = []): DocDraftWriteInput
    {
        $section = self::getContainer()->get(Connection::class)->fetchOne('SELECT id FROM documentation_section LIMIT 1');
        if (false === $section) {
            $em = self::getContainer()->get(EntityManagerInterface::class);
            $s = DocSectionBuilder::aSection();
            $em->persist($s);
            $em->flush();
            $section = $s->id();
        }

        return DocDraftWriteInput::fromArray($key, $fields + ['title' => 'Инструкция', 'slug' => 'new-page', 'sectionId' => (int) $section, 'description' => 'Описание', 'body' => "    [Пример](doc:999999)\n\n## Настройка\n\nТекст"]);
    }

    public function testCreateAndUpdateRetriesDoNotDuplicateOrOverwriteNewerContent(): void
    {
        $creator = self::getContainer()->get(DocDraftCreator::class);
        $editor = self::getContainer()->get(DocDraftEditor::class);
        $input = $this->input('11111111-1111-4111-8111-111111111111');
        $created = $creator->create($input, 'agent');
        self::assertFalse($created->replayed);
        self::assertSame($created->pageId, $creator->create($input, 'agent')->pageId);
        self::assertTrue($creator->create($input, 'agent')->replayed);
        $db = self::getContainer()->get(Connection::class);
        self::assertSame(1, (int) $db->fetchOne('SELECT COUNT(*) FROM documentation_page'));
        $update = $this->input('22222222-2222-4222-8222-222222222222', ['title' => 'Изменено']);
        $editor->edit($created->pageId, 1, $update, 'agent');
        self::getContainer()->get(DocMcpPersistenceResetter::class)->reset();
        $page = self::getContainer()->get(AdminDocQuery::class)->page($created->pageId);
        self::assertSame($input->page->body, $page['body']);
        self::getContainer()->get(DocPagePublisher::class)->publish($created->pageId, (int) $page['version']);
        self::assertTrue($editor->edit($created->pageId, 1, $update, 'agent')->replayed);
        self::assertSame(2, (int) $db->fetchOne('SELECT COUNT(*) FROM documentation_tool_receipt'));
        self::assertSame('published', self::getContainer()->get(AdminDocQuery::class)->page($created->pageId)['status']);
    }

    public function testReusedKeyWithDifferentPayloadConflictsAndManagerRecovers(): void
    {
        $creator = self::getContainer()->get(DocDraftCreator::class);
        $key = '11111111-1111-4111-8111-111111111111';
        $creator->create($this->input($key), 'agent');
        try {
            $creator->create($this->input($key, ['title' => 'Другое']), 'agent');
            self::fail();
        } catch (DocToolFailure $e) {
            self::assertSame('IDEMPOTENCY_CONFLICT', $e->reason);
        }
        self::getContainer()->get(DocMcpPersistenceResetter::class)->reset();
        self::assertFalse($creator->create($this->input('22222222-2222-4222-8222-222222222222', ['slug' => 'second']), 'agent')->replayed);
    }

    public function testPublishedPageCannotBeEditedAndCreatesNoReceipt(): void
    {
        $created = self::getContainer()->get(DocDraftCreator::class)->create($this->input('11111111-1111-4111-8111-111111111111'), 'agent');
        self::getContainer()->get(DocPagePublisher::class)->publish($created->pageId, 1);
        try {
            self::getContainer()->get(DocDraftEditor::class)->edit($created->pageId, 1, $this->input('22222222-2222-4222-8222-222222222222'), 'agent');
            self::fail();
        } catch (DocToolFailure $e) {
            self::assertSame('PAGE_NOT_DRAFT', $e->reason);
        }
        self::assertSame(1, (int) self::getContainer()->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM documentation_tool_receipt'));
    }

    public function testStaleVersionAndInvalidLinkRollbackAllChanges(): void
    {
        $creator = self::getContainer()->get(DocDraftCreator::class);
        $created = $creator->create($this->input('11111111-1111-4111-8111-111111111111'), 'agent');
        $editor = self::getContainer()->get(DocDraftEditor::class);
        $editor->edit($created->pageId, 1, $this->input('22222222-2222-4222-8222-222222222222', ['title' => 'Новое']), 'agent');
        self::getContainer()->get(DocMcpPersistenceResetter::class)->reset();
        try {
            $editor->edit($created->pageId, 1, $this->input('33333333-3333-4333-8333-333333333333'), 'agent');
            self::fail();
        } catch (DocToolFailure $e) {
            self::assertSame('VERSION_CONFLICT', $e->reason);
        }
        self::getContainer()->get(DocMcpPersistenceResetter::class)->reset();
        try {
            $creator->create($this->input('44444444-4444-4444-8444-444444444444', ['slug' => 'bad-link', 'body' => '[Цель](doc:999999)']), 'agent');
            self::fail();
        } catch (DocConflict) {
            self::assertSame(1, (int) self::getContainer()->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM documentation_page'));
        }
        self::assertSame(2, (int) self::getContainer()->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM documentation_tool_receipt'));
    }
}
