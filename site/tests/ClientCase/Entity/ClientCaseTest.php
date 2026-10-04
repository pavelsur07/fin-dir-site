<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Entity;

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
        $case->markAsFeatured('Задача', [], [], null, new \DateTimeImmutable('2026-01-11'));
    }
}
