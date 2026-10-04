<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Entity;

use App\ClientCase\Exception\CaseCannotBeTransitioned;
use App\ClientCase\Exception\CaseSlugIsLocked;
use App\ClientCase\ValueObject\CaseStatus;
use App\Tests\ClientCase\Builder\ClientCaseBuilder;
use PHPUnit\Framework\TestCase;

final class ClientCaseTest extends TestCase
{
    public function testNewCaseIsDraft(): void
    {
        self::assertSame(CaseStatus::DRAFT, ClientCaseBuilder::aCase()->build()->status());
    }

    public function testPublishKeepsFirstPublicationDate(): void
    {
        $case = ClientCaseBuilder::aCase()->published('2026-02-01 10:00:00')->build();

        $case->publish(new \DateTimeImmutable('2026-03-01 10:00:00'));

        self::assertSame('2026-02-01 10:00:00', $case->publishedAt()?->format('Y-m-d H:i:s'));
    }

    public function testFeaturedCaseRequiresTaskStepsAndMetrics(): void
    {
        $case = ClientCaseBuilder::aCase()->build();

        $this->expectException(\InvalidArgumentException::class);
        $case->markAsFeatured(new \DateTimeImmutable('2026-01-11'));
    }

    public function testFeaturedCaseCannotLoseItsDescription(): void
    {
        $case = ClientCaseBuilder::aCase()->featured()->build();

        $this->expectException(\InvalidArgumentException::class);
        $case->describe(null, [], [], null, new \DateTimeImmutable('2026-01-11'));
    }

    public function testSlugIsLockedAfterFirstPublication(): void
    {
        $case = ClientCaseBuilder::aCase()->withSlug('before')->build();
        $now = new \DateTimeImmutable('2026-02-01');

        $case->changeSlug('after', $now);
        self::assertSame('after', $case->slug());

        $case->publish($now);
        $case->unpublish($now);

        $this->expectException(CaseSlugIsLocked::class);
        $case->changeSlug('again', $now);
    }

    public function testStatusTransitionsFollowTheLifecycle(): void
    {
        $now = new \DateTimeImmutable('2026-02-01');
        $case = ClientCaseBuilder::aCase()->build();

        $case->publish($now);
        $case->publish($now); // повтор -- no-op
        self::assertSame(CaseStatus::PUBLISHED, $case->status());

        $case->archive($now);
        $case->restore($now);
        self::assertSame(CaseStatus::DRAFT, $case->status());
    }

    public function testArchivedCaseCannotBePublishedOrUnpublished(): void
    {
        $now = new \DateTimeImmutable('2026-02-01');
        $case = ClientCaseBuilder::aCase()->build();
        $case->archive($now);

        try {
            $case->publish($now);
            self::fail('Archived case must not be published directly.');
        } catch (CaseCannotBeTransitioned) {
        }

        $this->expectException(CaseCannotBeTransitioned::class);
        $case->unpublish($now);
    }
}
