<?php

declare(strict_types=1);

namespace App\Tests\Publication\Entity;

use App\Publication\Entity\Post;
use App\Publication\Exception\PostCannotBeTransitioned;
use App\Publication\Exception\PostSlugIsLocked;
use App\Publication\ValueObject\PostStatus;
use App\Tests\Publication\Builder\PostBuilder;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    public function testNewPostIsDraftWithoutPublicationDate(): void
    {
        $post = PostBuilder::aPost()->build();

        self::assertSame(PostStatus::DRAFT, $post->status());
        self::assertNull($post->publishedAt());
    }

    public function testPublishSetsFirstPublicationDate(): void
    {
        $post = PostBuilder::aPost()->build();
        $post->publish(new \DateTimeImmutable('2026-02-01 09:00'));

        self::assertSame(PostStatus::PUBLISHED, $post->status());
        self::assertEquals(new \DateTimeImmutable('2026-02-01 09:00'), $post->publishedAt());
    }

    public function testRepublishKeepsFirstPublicationDate(): void
    {
        $post = PostBuilder::aPost()->published('2026-02-01 09:00')->build();

        $post->unpublish(new \DateTimeImmutable('2026-02-02'));
        self::assertSame(PostStatus::DRAFT, $post->status());
        self::assertEquals(new \DateTimeImmutable('2026-02-01 09:00'), $post->publishedAt());

        $post->publish(new \DateTimeImmutable('2026-02-03'));
        self::assertEquals(new \DateTimeImmutable('2026-02-01 09:00'), $post->publishedAt());
    }

    public function testRepeatedTransitionIsNoOp(): void
    {
        $post = PostBuilder::aPost()->published('2026-02-01')->build();
        $updatedAt = $post->updatedAt();

        $post->publish(new \DateTimeImmutable('2026-03-01'));

        self::assertSame(PostStatus::PUBLISHED, $post->status());
        self::assertEquals($updatedAt, $post->updatedAt());
    }

    public function testArchiveAndRestoreReturnsToDraft(): void
    {
        $post = PostBuilder::aPost()->published()->archived()->build();
        self::assertSame(PostStatus::ARCHIVED, $post->status());

        $post->restore(new \DateTimeImmutable('2026-03-01'));

        self::assertSame(PostStatus::DRAFT, $post->status());
    }

    public function testArchivedPostCannotBePublishedDirectly(): void
    {
        $post = PostBuilder::aPost()->archived()->build();

        $this->expectException(PostCannotBeTransitioned::class);
        $post->publish(new \DateTimeImmutable('2026-03-01'));
    }

    public function testArchivedPostCannotBeUnpublished(): void
    {
        $post = PostBuilder::aPost()->archived()->build();

        $this->expectException(PostCannotBeTransitioned::class);
        $post->unpublish(new \DateTimeImmutable('2026-03-01'));
    }

    public function testRestoreIsNotAWayToUnpublish(): void
    {
        $post = PostBuilder::aPost()->published()->build();

        $this->expectException(PostCannotBeTransitioned::class);
        $post->restore(new \DateTimeImmutable('2026-03-01'));
    }

    public function testPostWithEmptyBodyCannotBePublished(): void
    {
        $post = PostBuilder::aPost()->withBody("  \n ")->build();

        $this->expectException(PostCannotBeTransitioned::class);
        $post->publish(new \DateTimeImmutable('2026-03-01'));
    }

    public function testPublishedPostBodyCannotBeEmptied(): void
    {
        $post = PostBuilder::aPost()->published()->build();

        $this->expectException(\InvalidArgumentException::class);
        $post->edit('Заголовок', null, ' ', null, null, new \DateTimeImmutable('2026-03-01'));
    }

    public function testDraftSlugCanBeChanged(): void
    {
        $post = PostBuilder::aPost()->withSlug('old')->build();
        $post->changeSlug('new', new \DateTimeImmutable('2026-03-01'));

        self::assertSame('new', $post->slug());
    }

    public function testSlugIsLockedAfterFirstPublicationEvenWhenUnpublished(): void
    {
        $post = PostBuilder::aPost()->withSlug('old')->published()->build();
        $post->unpublish(new \DateTimeImmutable('2026-03-01'));

        $this->expectException(PostSlugIsLocked::class);
        $post->changeSlug('new', new \DateTimeImmutable('2026-03-02'));
    }

    public function testSameSlugIsAcceptedForPublishedPost(): void
    {
        $post = PostBuilder::aPost()->withSlug('same')->published()->build();
        $post->changeSlug('same', new \DateTimeImmutable('2026-03-01'));

        self::assertSame('same', $post->slug());
    }

    public function testInvalidSlugIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Post('Заголовок', 'Не Slug', null, 'текст', null, null, new \DateTimeImmutable());
    }

    public function testBlankTitleIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Post('   ', 'slug', null, 'текст', null, null, new \DateTimeImmutable());
    }
}
