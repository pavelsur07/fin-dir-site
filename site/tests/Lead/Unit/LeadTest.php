<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\ValueObject\ContactType;
use App\Lead\ValueObject\LeadStatus;
use App\Tests\Lead\Builder\LeadBuilder;
use PHPUnit\Framework\TestCase;

final class LeadTest extends TestCase
{
    public function testNewLeadNeedsNotification(): void
    {
        $lead = LeadBuilder::aLead()->build();

        self::assertSame(LeadStatus::NEW, $lead->status());
        self::assertTrue($lead->needsNotification());
    }

    public function testSpamIsMarkedAndNeverNotified(): void
    {
        $lead = LeadBuilder::aLead()->spam('too_fast')->build();

        self::assertSame(LeadStatus::SPAM, $lead->status());
        self::assertFalse($lead->needsNotification());
    }

    public function testSuccessfulNotificationClearsPreviousError(): void
    {
        $lead = LeadBuilder::aLead()->build();
        $lead->markNotificationFailed('Telegram API responded 502');
        self::assertSame('Telegram API responded 502', $lead->notificationError());

        $lead->markNotified(new \DateTimeImmutable('2026-09-01 10:01'));

        self::assertNull($lead->notificationError());
        self::assertFalse($lead->needsNotification());
    }

    public function testEmptyNoteIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LeadBuilder::aLead()->build()->addNote('  ', new \DateTimeImmutable());
    }

    public function testContactTypeIsDetected(): void
    {
        $email = LeadBuilder::aLead()->withContact('Owner@Shop.ru')->build();

        $reflection = new \ReflectionProperty($email, 'contactType');
        self::assertSame(ContactType::EMAIL, $reflection->getValue($email));
    }
}
