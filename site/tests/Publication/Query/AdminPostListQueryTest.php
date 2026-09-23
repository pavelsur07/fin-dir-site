<?php

declare(strict_types=1);

namespace App\Tests\Publication\Query;

use App\Publication\Query\AdminPostList\AdminPostListCriteria;
use App\Publication\Query\AdminPostList\AdminPostListItem;
use App\Publication\Query\AdminPostList\AdminPostListQuery;
use App\Publication\Query\AdminPostList\AdminPostSort;
use App\Publication\ValueObject\PostStatus;
use App\Tests\Publication\Builder\PostBuilder;
use App\Tests\Publication\PostTableCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AdminPostListQueryTest extends KernelTestCase
{
    use PostTableCleaner;

    private EntityManagerInterface $entityManager;
    private AdminPostListQuery $query;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->query = self::getContainer()->get(AdminPostListQuery::class);
        self::clearPosts($this->entityManager);
    }

    public function testListsAllStatuses(): void
    {
        $this->persist(
            PostBuilder::aPost()->withSlug('draft')->build(),
            PostBuilder::aPost()->withSlug('published')->published()->build(),
            PostBuilder::aPost()->withSlug('archived')->archived()->build(),
        );

        $pager = $this->query->paginate(new AdminPostListCriteria());

        self::assertSame(3, $pager->getNbResults());
        $slugs = $this->slugs($pager->getCurrentPageResults());
        sort($slugs);
        self::assertSame(['archived', 'draft', 'published'], $slugs);
    }

    public function testFiltersByStatus(): void
    {
        $this->persist(
            PostBuilder::aPost()->withSlug('draft')->build(),
            PostBuilder::aPost()->withSlug('published')->published()->build(),
        );

        $pager = $this->query->paginate(new AdminPostListCriteria(status: PostStatus::PUBLISHED));

        self::assertSame(['published'], $this->slugs($pager->getCurrentPageResults()));
    }

    public function testSortsByPublicationDateWithDraftsLast(): void
    {
        $this->persist(
            PostBuilder::aPost()->withSlug('draft')->createdAt('2026-03-01')->build(),
            PostBuilder::aPost()->withSlug('older')->published('2026-01-01')->build(),
            PostBuilder::aPost()->withSlug('newer')->published('2026-02-01')->build(),
        );

        $pager = $this->query->paginate(new AdminPostListCriteria(sort: AdminPostSort::PUBLISHED));

        self::assertSame(['newer', 'older', 'draft'], $this->slugs($pager->getCurrentPageResults()));
    }

    public function testSortsByTitleAscending(): void
    {
        $this->persist(
            PostBuilder::aPost()->withSlug('b')->withTitle('Бюджет')->build(),
            PostBuilder::aPost()->withSlug('a')->withTitle('Аналитика')->build(),
        );

        $pager = $this->query->paginate(new AdminPostListCriteria(sort: AdminPostSort::TITLE));

        self::assertSame(['a', 'b'], $this->slugs($pager->getCurrentPageResults()));
    }

    public function testPagesHaveStableOrderAndCorrectCount(): void
    {
        // Одинаковая дата изменения у всех: порядок держит второй ключ id.
        $posts = [];
        for ($i = 1; $i <= AdminPostListCriteria::PER_PAGE + 5; ++$i) {
            $posts[] = PostBuilder::aPost()->withSlug('post-'.$i)->build();
        }
        $this->persist(...$posts);

        $first = $this->query->paginate(new AdminPostListCriteria(page: 1));
        $last = $this->query->paginate(new AdminPostListCriteria(page: 2));

        self::assertSame(AdminPostListCriteria::PER_PAGE + 5, $first->getNbResults());
        self::assertSame(2, $first->getNbPages());
        self::assertCount(AdminPostListCriteria::PER_PAGE, $first->getCurrentPageResults());
        self::assertSame(['post-5', 'post-4', 'post-3', 'post-2', 'post-1'], $this->slugs($last->getCurrentPageResults()));
    }

    public function testPageOutOfRangeIsRejected(): void
    {
        $this->persist(PostBuilder::aPost()->build());

        $this->expectException(OutOfRangeCurrentPageException::class);
        $this->query->paginate(new AdminPostListCriteria(page: 2));
    }

    private function persist(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * @param iterable<AdminPostListItem> $items
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
