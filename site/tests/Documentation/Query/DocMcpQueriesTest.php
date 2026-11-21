<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Query;

use App\Documentation\DTO\DocMcpSearchInput;
use App\Documentation\Exception\DocNotFound;
use App\Documentation\Query\EditorialDocMcpQuery;
use App\Documentation\Query\PublishedDocMcpQuery;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocMcpQueriesTest extends KernelTestCase
{
    public function testPublishedScopeFiltersPagesSectionsIncomingLinksAndCounts(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $empty = DocSectionBuilder::aSection('empty');
        $target = DocPageBuilder::aPage($section, 'target', true);
        $draft = DocPageBuilder::aPage($section, 'hidden-draft');
        foreach ([$section, $empty, $target, $draft] as $item) {
            $em->persist($item);
        }
        $em->flush();
        $draft->replaceLinks([['target' => $target, 'type' => \App\Documentation\ValueObject\DocLinkType::INLINE, 'fragment' => '', 'position' => 0, 'urls' => ['doc:'.$target->id()]]]);
        $em->flush();
        $public = self::getContainer()->get(PublishedDocMcpQuery::class);
        $editorial = self::getContainer()->get(EditorialDocMcpQuery::class);
        self::assertSame(1, $public->sections(1)->getNbResults());
        self::assertSame(2, $editorial->sections(1)->getNbResults());
        self::assertSame(1, $public->search(new DocMcpSearchInput())->getNbResults());
        self::assertSame(2, $editorial->search(new DocMcpSearchInput())->getNbResults());
        self::assertSame(0, $public->links($target->id(), 'incoming', 1)->getNbResults());
        self::assertSame(1, $editorial->links($target->id(), 'incoming', 1)->getNbResults());
        self::assertSame(1, $public->search(new DocMcpSearchInput('операции'))->getNbResults());
        self::assertSame(0, $public->search(new DocMcpSearchInput('!!!'))->getNbResults());
        $this->expectException(DocNotFound::class);
        $public->page($draft->id());
    }

    public function testPaginationHasStableOrderRealCountAndOutOfRange(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        for ($i = 0; $i < 42; ++$i) {
            $em->persist(DocPageBuilder::aPage($section, 'page-'.$i, true));
        }
        $em->flush();
        $query = self::getContainer()->get(PublishedDocMcpQuery::class);
        $first = $query->search(new DocMcpSearchInput());
        $middle = $query->search(new DocMcpSearchInput(page: 2));
        $last = $query->search(new DocMcpSearchInput(page: 3));
        self::assertSame(42, $first->getNbResults());
        self::assertCount(20, $first->getCurrentPageResults());
        self::assertCount(20, $middle->getCurrentPageResults());
        self::assertCount(2, $last->getCurrentPageResults());
        $ids = array_column([...$first->getCurrentPageResults(), ...$middle->getCurrentPageResults(), ...$last->getCurrentPageResults()], 'id');
        self::assertCount(42, array_unique($ids));
        $this->expectException(\Pagerfanta\Exception\OutOfRangeCurrentPageException::class);
        $query->search(new DocMcpSearchInput(page: 4));
    }

    public function testRelatedIdsDoNotRevealDraftChangesBetweenQueries(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $source = DocPageBuilder::aPage($section, 'source', true);
        $target = DocPageBuilder::aPage($section, 'target', true);
        foreach ([$section, $source, $target] as $entity) {
            $em->persist($entity);
        }
        $em->flush();
        $db = self::getContainer()->get(\Doctrine\DBAL\Connection::class);
        $afterRead = function () use ($db, $source, $target): void {
            $db->executeStatement("UPDATE documentation_page SET status='draft' WHERE id=:id", ['id' => $source->id()]);
            $db->executeStatement("INSERT INTO documentation_page_link (source_id,target_id,type,fragment,position,source_urls) VALUES (:source,:target,'related','',0,'[]')", ['source' => $source->id(), 'target' => $target->id()]);
        };
        $interleaved = new class($db->getParams(), $db->getDriver(), $db->getConfiguration()) extends \Doctrine\DBAL\Connection {
            public \Doctrine\DBAL\Connection $inner;
            public \Closure $afterRead;

            /**
             * @param array<mixed> $params
             * @param array<mixed> $types
             *
             * @return array<string,mixed>|false
             */
            public function fetchAssociative(string $query, array $params = [], array $types = []): array|false
            {
                $row = $this->inner->fetchAssociative($query, $params, $types);
                ($this->afterRead)();

                return $row;
            }

            /**
             * @param array<mixed> $params
             * @param array<mixed> $types
             *
             * @return list<mixed>
             */
            public function fetchFirstColumn(string $query, array $params = [], array $types = []): array
            {
                return $this->inner->fetchFirstColumn($query, $params, $types);
            }
        };
        $interleaved->inner = $db;
        $interleaved->afterRead = $afterRead;
        $query = new PublishedDocMcpQuery($interleaved, self::getContainer()->get(\App\Documentation\Query\DocPagination::class));
        self::assertSame([], $query->page($source->id())->data['relatedIds']);
    }
}
