<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Service;

use App\Documentation\DTO\DocPageInput;
use App\Documentation\DTO\DocSectionInput;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Query\AdminDocQuery;
use App\Documentation\Service\DocPageCreator;
use App\Documentation\Service\DocPageEditor;
use App\Documentation\Service\DocPagePublisher;
use App\Documentation\Service\DocPageUnpublisher;
use App\Documentation\Service\DocSectionCreator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocWriteScenariosTest extends KernelTestCase
{
    private function section(): int
    {
        $input = new DocSectionInput();
        $input->title = 'Начало';
        $input->slug = 'start';

        return self::getContainer()->get(DocSectionCreator::class)->create($input);
    }

    private function page(int $section, string $slug, string $body = 'Текст'): int
    {
        $i = new DocPageInput();
        $i->sectionId = $section;
        $i->title = 'Отчёт';
        $i->slug = $slug;
        $i->description = 'Описание';
        $i->body = $body;

        return self::getContainer()->get(DocPageCreator::class)->create($i);
    }

    public function testCannotUnpublishTargetWithPublishedIncomingLink(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target');
        self::getContainer()->get(DocPagePublisher::class)->publish($target, 1);
        $source = $this->page($section, 'source', '[Цель](doc:'.$target.')');
        self::getContainer()->get(DocPagePublisher::class)->publish($source, 1);
        $this->expectException(DocConflict::class);
        self::getContainer()->get(DocPageUnpublisher::class)->unpublish($target, 2);
    }

    public function testDraftTargetBlocksPublishing(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target');
        $source = $this->page($section, 'source', '[Цель](doc:'.$target.')');
        $this->expectException(DocConflict::class);
        self::getContainer()->get(DocPagePublisher::class)->publish($source, 1);
    }

    public function testStaleFormCannotOverwriteNewerPage(): void
    {
        $section = $this->section();
        $id = $this->page($section, 'report');
        $i = new DocPageInput();
        $i->sectionId = $section;
        $i->title = 'Новое';
        $i->slug = 'report';
        $i->version = 1;
        $editor = self::getContainer()->get(DocPageEditor::class);
        $editor->edit($id, $i);
        $this->expectException(DocConflict::class);
        $editor->edit($id, $i);
    }

    public function testLinksAreReplacedWithoutUniqueConstraintFailure(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target');
        $id = $this->page($section, 'source', '[Цель](doc:'.$target.')');
        $i = new DocPageInput();
        $i->sectionId = $section;
        $i->slug = 'source';
        $i->title = 'Другое';
        $i->version = 1;
        $i->body = '[Цель](doc:'.$target.')';
        self::getContainer()->get(DocPageEditor::class)->edit($id, $i);
        self::assertCount(1, self::getContainer()->get(AdminDocQuery::class)->targets($id));
    }

    public function testCannotRemoveHeadingWithPublishedIncomingAnchor(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target', "## Настройка\n\nТекст");
        self::getContainer()->get(DocPagePublisher::class)->publish($target, 1);
        $processor = self::getContainer()->get(\App\Documentation\Adapter\DocMarkdownProcessor::class);
        $anchor = $processor->analyze("## Настройка\n\nТекст")->headings[0]['id'];
        $source = $this->page($section, 'source', '[Цель](doc:'.$target.'#'.$anchor.')');
        self::getContainer()->get(DocPagePublisher::class)->publish($source, 1);
        $i = new DocPageInput();
        $i->sectionId = $section;
        $i->slug = 'target';
        $i->title = 'Цель';
        $i->description = 'Описание';
        $i->body = '## Новое название';
        $i->version = 2;
        $this->expectException(DocConflict::class);
        self::getContainer()->get(DocPageEditor::class)->edit($target, $i);
    }

    public function testDuplicateRelatedTargetIsRejected(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target');
        $i = new DocPageInput();
        $i->sectionId = $section;
        $i->slug = 'source';
        $i->title = 'Источник';
        $i->relatedIds = [$target, $target];
        $this->expectException(DocConflict::class);
        self::getContainer()->get(DocPageCreator::class)->create($i);
    }

    public function testRelatedOrderIsPreserved(): void
    {
        $section = $this->section();
        $a = $this->page($section, 'a');
        $b = $this->page($section, 'b');
        $i = new DocPageInput();
        $i->sectionId = $section;
        $i->slug = 'source';
        $i->title = 'Источник';
        $i->relatedIds = [$b, $a];
        $id = self::getContainer()->get(DocPageCreator::class)->create($i);
        $rows = self::getContainer()->get(AdminDocQuery::class)->targets($id);
        self::assertSame([$b, $a], array_map(static fn (array $row): int => (int) $row['target_id'], $rows));
    }

    public function testDraftIncomingLinkDoesNotBlockUnpublish(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target');
        self::getContainer()->get(DocPagePublisher::class)->publish($target, 1);
        $this->page($section, 'draft', '[Цель](doc:'.$target.')');
        self::getContainer()->get(DocPageUnpublisher::class)->unpublish($target, 2);
        self::assertSame('draft', self::getContainer()->get(AdminDocQuery::class)->page($target)['status']);
    }

    public function testRealConnectionsSerializeModuleWritesAndReleaseLockOnCommit(): void
    {
        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $first = \Doctrine\DBAL\DriverManager::getConnection($em->getConnection()->getParams());
        $second = \Doctrine\DBAL\DriverManager::getConnection($em->getConnection()->getParams());
        try {
            $isolated = new \Doctrine\ORM\EntityManager($first, $em->getConfiguration());
            $transaction = new \App\Documentation\Service\DocWriteTransaction($isolated);
            $transaction->run(function () use ($second): void {
                self::assertFalse($second->fetchOne('SELECT pg_try_advisory_xact_lock(1146045267,1)'));
            });
            self::assertTrue($second->fetchOne('SELECT pg_try_advisory_xact_lock(1146045267,1)'));
        } finally {
            $first->close();
            $second->close();
        }
    }

    public function testLeadingIndentedCodeRemainsCodeAfterSave(): void
    {
        $section = $this->section();
        $body = '    [Пример](doc:999999)';
        $id = $this->page($section, 'example', $body);
        self::assertSame($body, self::getContainer()->get(AdminDocQuery::class)->page($id)['body']);
        self::getContainer()->get(DocPagePublisher::class)->publish($id, 1);
        $view = self::getContainer()->get(\App\Documentation\Query\PublicDocPageQuery::class)->getBySlug('example');
        $html = self::getContainer()->get(\App\Documentation\Adapter\DocMarkdownProcessor::class)->renderTargets((string) $view['page']['body'], $view['targets'])->html;
        self::assertStringContainsString('<code ', $html);
        self::assertStringNotContainsString('<a ', $html);
    }

    public function testEscapedAndAutomaticLinksFollowTargetIdAfterDraftRename(): void
    {
        $section = $this->section();
        $target = $this->page($section, 'target');
        $body = "[Цель](/articles/tar&#103;et)\n\n<https://docs.vashfindir.ru/articles/target>\n\n[Вторая](/articles/target)";
        $source = $this->page($section, 'source', $body);
        $input = new DocPageInput();
        $input->sectionId = $section;
        $input->title = 'Цель';
        $input->slug = 'renamed';
        $input->description = 'Описание';
        $input->body = 'Текст';
        $input->version = 1;
        self::getContainer()->get(DocPageEditor::class)->edit($target, $input);
        self::getContainer()->get(DocPagePublisher::class)->publish($target, 2);
        self::getContainer()->get(DocPagePublisher::class)->publish($source, 1);
        $edit = new DocPageInput();
        $edit->sectionId = $section;
        $edit->title = 'Источник';
        $edit->slug = 'source';
        $edit->description = 'Описание';
        $edit->body = $body;
        $edit->version = 2;
        self::getContainer()->get(DocPageEditor::class)->edit($source, $edit);
        $view = self::getContainer()->get(\App\Documentation\Query\PublicDocPageQuery::class)->getBySlug('source');
        self::assertSame($body, $view['page']['body']);
        $html = self::getContainer()->get(\App\Documentation\Adapter\DocMarkdownProcessor::class)->renderTargets((string) $view['page']['body'], $view['targets'])->html;
        self::assertSame(3, substr_count($html, 'href="https://docs.vashfindir.ru/articles/renamed"'));
    }

    public function testSixRecommendationsRollbackWholeCreation(): void
    {
        $section = $this->section();
        $ids = [];
        for ($i = 0; $i < 6; ++$i) {
            $ids[] = $this->page($section, 'related-'.$i);
        }
        $input = new DocPageInput();
        $input->sectionId = $section;
        $input->title = 'Источник';
        $input->slug = 'source';
        $input->relatedIds = $ids;
        $db = self::getContainer()->get(\Doctrine\DBAL\Connection::class);
        try {
            self::getContainer()->get(DocPageCreator::class)->create($input);
            self::fail('Expected recommendation limit conflict.');
        } catch (DocConflict $error) {
            self::assertStringContainsString('до пяти', $error->getMessage());
        }
        self::assertSame(0, (int) $db->fetchOne("SELECT COUNT(*) FROM documentation_page WHERE slug='source'"));
    }
}
