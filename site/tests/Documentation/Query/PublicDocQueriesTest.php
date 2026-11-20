<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Query;

use App\Documentation\Query\PublicDocSearchQuery;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PublicDocQueriesTest extends KernelTestCase
{
    public function testSearchPaginatesSyntheticCorpusAndExcludesDraft(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $body = mb_strcut(str_repeat('Операции платежи продажи отчёты. ', 160), 0, 5000, 'UTF-8');
        for ($i = 0; $i < 200; ++$i) {
            $page = DocPageBuilder::aPage($section, 'measurement-'.$i);
            $page->edit($section, 'Операции '.$i, 'measurement-'.$i, 'Описание', $body, $body, $i, null, null, new \DateTimeImmutable('2026-01-01'));
            $page->publish(new \DateTimeImmutable('2026-01-01'));
            $em->persist($page);
        }
        $em->persist(DocPageBuilder::aPage($section, 'hidden'));
        $em->flush();
        $query = self::getContainer()->get(PublicDocSearchQuery::class);
        $start = hrtime(true);
        $pager = $query->paginate('операции', 1);
        $items = iterator_to_array((function () use ($pager) { yield from $pager->getCurrentPageResults(); })());
        self::assertSame(200, $pager->getNbResults());
        self::assertCount(20, $items);
        $elapsed = (hrtime(true) - $start) / 1000000;
        $last = $query->paginate('операции', 10);
        self::assertCount(20, iterator_to_array((function () use ($last) { yield from $last->getCurrentPageResults(); })()));
        self::assertSame([], $query->paginate('несуществующееслово', 1)->getCurrentPageResults());
        $plan = $em->getConnection()->fetchFirstColumn("EXPLAIN (ANALYZE, BUFFERS) SELECT id FROM documentation_page WHERE status='published' AND (setweight(to_tsvector('russian',title),'A') || setweight(to_tsvector('russian',search_text),'B')) @@ plainto_tsquery('russian','операции')");
        $directory = dirname(__DIR__, 3).'/var/tests';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($directory.'/documentation-search.json', json_encode(['pages' => 200, 'body_bytes' => strlen($body), 'first_page_ms' => $elapsed, 'explain' => $plan], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
