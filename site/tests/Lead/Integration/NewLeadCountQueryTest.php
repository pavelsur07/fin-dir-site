<?php

declare(strict_types=1);

namespace App\Tests\Lead\Integration;

use App\Lead\Query\NewLeadCount\NewLeadCountQuery;
use App\Lead\ValueObject\LeadStatus;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class NewLeadCountQueryTest extends KernelTestCase
{
    public function testCountsOnlyNewLeads(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $inProgress = LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000003')->build();
        $inProgress->changeStatus(LeadStatus::IN_PROGRESS, new \DateTimeImmutable('2026-09-02'));

        foreach ([
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000001')->build(),
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->build(),
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000004')->spam()->build(),
            $inProgress,
        ] as $lead) {
            $entityManager->persist($lead);
        }
        $entityManager->flush();

        self::assertSame(2, self::getContainer()->get(NewLeadCountQuery::class)->count());
    }
}
