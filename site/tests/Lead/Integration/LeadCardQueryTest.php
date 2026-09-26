<?php

declare(strict_types=1);

namespace App\Tests\Lead\Integration;

use App\Lead\Entity\Lead;
use App\Lead\Query\LeadCard\LeadCardQuery;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LeadCardQueryTest extends KernelTestCase
{
    public function testCardHasSourceVisitsAndDaysToLead(): void
    {
        $createdAt = new \DateTimeImmutable('2026-09-01 10:00:00');
        $lead = LeadBuilder::aLead()
            ->createdAt('2026-09-01 10:00:00')
            ->withAttribution([
                'first' => ['ts' => $createdAt->modify('-3 days -2 hours')->getTimestamp(), 'channel' => 'cpc', 'source' => 'yandex'],
                'last' => ['channel' => 'organic'],
                'visits' => 4,
            ], '1758600000123456789')
            ->build();

        $card = self::getContainer()->get(LeadCardQuery::class)->get($this->persist($lead));

        self::assertSame('yandex', $card->firstTouch['source'] ?? null);
        self::assertSame(['channel' => 'organic'], $card->lastTouch);
        self::assertSame(4, $card->visits);
        self::assertSame(3, $card->daysToLead);
        self::assertSame('1758600000123456789', $card->ymClientId);
    }

    public function testFirstVisitAfterLeadCountsAsZeroDays(): void
    {
        $lead = LeadBuilder::aLead()
            ->createdAt('2026-09-01 10:00:00')
            ->withAttribution(['first' => ['ts' => (new \DateTimeImmutable('2026-09-01 20:00:00'))->getTimestamp(), 'channel' => 'direct']])
            ->build();

        self::assertSame(0, self::getContainer()->get(LeadCardQuery::class)->get($this->persist($lead))->daysToLead);
    }

    public function testLeadWithoutAttributionHasEmptySource(): void
    {
        $card = self::getContainer()->get(LeadCardQuery::class)->get($this->persist(LeadBuilder::aLead()->build()));

        self::assertNull($card->firstTouch);
        self::assertNull($card->lastTouch);
        self::assertNull($card->visits);
        self::assertNull($card->daysToLead);
        self::assertNull($card->ymClientId);
    }

    public function testCardProjectsNotificationNeedAndUnknownSpamReason(): void
    {
        $pending = self::getContainer()->get(LeadCardQuery::class)->get($this->persist(LeadBuilder::aLead()->withSubmissionId('10000000-0000-4000-8000-000000000001')->build()));
        $spam = self::getContainer()->get(LeadCardQuery::class)->get($this->persist(LeadBuilder::aLead()->withSubmissionId('10000000-0000-4000-8000-000000000002')->spam('unexpected')->build()));

        self::assertTrue($pending->needsNotification);
        self::assertFalse($spam->needsNotification);
        self::assertSame('неизвестная причина', $spam->spamReasonLabel);
    }

    private function persist(Lead $lead): int
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($lead);
        $entityManager->flush();
        $entityManager->clear();

        return (int) $lead->id();
    }
}
