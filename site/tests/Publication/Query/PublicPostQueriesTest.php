<?php

declare(strict_types=1);

namespace App\Tests\Publication\Query;

use App\Publication\Exception\PostNotFound;
use App\Publication\Query\PublicPost\PublicPostQuery;
use App\Publication\Query\PublicPostList\PublicPostListItem;
use App\Publication\Query\PublicPostList\PublicPostListQuery;
use App\Publication\Query\PublicPostSitemap\PublicPostSitemapQuery;
use App\Publication\ValueObject\PostRubric;
use App\Tests\Publication\Builder\PostBuilder;
use App\Tests\Publication\PostTableCleaner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Публичные Query: черновики и архив наружу не попадают ни в одном сценарии.
 */
final class PublicPostQueriesTest extends KernelTestCase
{
    use PostTableCleaner;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::clearPosts($this->entityManager);

        foreach ([
            PostBuilder::aPost()->withSlug('draft')->build(),
            PostBuilder::aPost()->withSlug('archived')->published('2026-03-01')->archived()->build(),
            PostBuilder::aPost()->withSlug('older')->published('2026-01-01')->build(),
            PostBuilder::aPost()->withSlug('newer')->published('2026-02-01')->build(),
        ] as $post) {
            $this->entityManager->persist($post);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testListContainsOnlyPublishedNewestFirst(): void
    {
        $pager = self::getContainer()->get(PublicPostListQuery::class)->paginate(1);

        self::assertSame(2, $pager->getNbResults());
        self::assertSame(['newer', 'older'], $this->slugs($pager->getCurrentPageResults()));
    }

    public function testRubricFilterKeepsOnlyPublishedOfThatRubric(): void
    {
        foreach ([
            PostBuilder::aPost()->withSlug('tax-draft')->withRubric(PostRubric::TAXES)->build(),
            PostBuilder::aPost()->withSlug('tax-pub')->withRubric(PostRubric::TAXES)->published('2026-04-01')->build(),
            PostBuilder::aPost()->withSlug('rep-pub')->withRubric(PostRubric::REPORTING)->published('2026-04-02')->build(),
        ] as $post) {
            $this->entityManager->persist($post);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $pager = self::getContainer()->get(PublicPostListQuery::class)->paginate(1, PostRubric::TAXES);

        $items = iterator_to_array($pager->getCurrentPageResults(), false);
        self::assertSame(['tax-pub'], $this->slugs($items));
        self::assertSame(PostRubric::TAXES, $items[0]->rubric);
    }

    public function testListItemReadingTimeIsEstimatedFromBodyLength(): void
    {
        $this->entityManager->persist(PostBuilder::aPost()->withSlug('long')->withBody(str_repeat('а', 2900))->published('2026-04-01')->build());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $items = self::getContainer()->get(PublicPostListQuery::class)->paginate(1)->getCurrentPageResults();
        $byTitle = [];
        foreach ($items as $item) {
            $byTitle[$item->slug] = $item->readingMinutes();
        }

        self::assertSame(3, $byTitle['long']);
        self::assertSame(1, $byTitle['newer']);
    }

    public function testLatestExceptSkipsCurrentPostAndUnpublished(): void
    {
        $newer = self::getContainer()->get(PublicPostQuery::class)->getBySlug('newer');

        $related = self::getContainer()->get(PublicPostListQuery::class)->latestExcept($newer->id, 3);

        self::assertSame(['older'], $this->slugs($related));
    }

    public function testPublishedPostIsFoundBySlug(): void
    {
        $post = self::getContainer()->get(PublicPostQuery::class)->getBySlug('older');

        self::assertSame('older', $post->slug);
        self::assertEquals(new \DateTimeImmutable('2026-01-01'), $post->publishedAt);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hiddenSlugs(): iterable
    {
        yield 'черновик' => ['draft'];
        yield 'архив' => ['archived'];
        yield 'нет такой' => ['missing'];
    }

    #[DataProvider('hiddenSlugs')]
    public function testUnpublishedPostIsNotFound(string $slug): void
    {
        $this->expectException(PostNotFound::class);
        self::getContainer()->get(PublicPostQuery::class)->getBySlug($slug);
    }

    public function testSitemapListsOnlyPublished(): void
    {
        $rows = self::getContainer()->get(PublicPostSitemapQuery::class)->all();

        self::assertSame(['newer', 'older'], array_column($rows, 'slug'));
        self::assertInstanceOf(\DateTimeImmutable::class, $rows[0]['updatedAt']);
    }

    /**
     * @param iterable<PublicPostListItem> $items
     *
     * @return list<string>
     */
    private function slugs(iterable $items): array
    {
        $slugs = [];
        foreach ($items as $item) {
            $slugs[] = $item->slug;
        }

        return $slugs;
    }
}
