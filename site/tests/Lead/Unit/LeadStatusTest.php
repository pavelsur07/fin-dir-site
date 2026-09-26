<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\ValueObject\LeadStatus;
use PHPUnit\Framework\TestCase;

final class LeadStatusTest extends TestCase
{
    public function testNotificationNeedsAnUnsentNonSpamLead(): void
    {
        $sentAt = new \DateTimeImmutable('2026-09-01');
        foreach (LeadStatus::cases() as $status) {
            self::assertSame(LeadStatus::SPAM !== $status, $status->needsNotification(null), $status->value);
            self::assertFalse($status->needsNotification($sentAt), $status->value);
        }
    }
}
