<?php

declare(strict_types=1);

namespace App\Tests\Lead\Integration;

use App\Lead\Entity\Lead;
use App\Lead\Exception\LeadExportInProgress;
use App\Lead\Repository\LeadErasureRepository;
use App\Lead\Repository\LeadRepository;
use App\Lead\Service\ExpiredLeadPurger;
use App\Lead\Service\LeadEraser;
use App\Lead\ValueObject\LeadErasureReason;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Срок хранения из согласия -- «3 года с даты отправки заявки»: по его истечении
 * обращение удаляется с записью в журнале уничтожения, свежие не трогаются.
 */
final class ExpiredLeadPurgeTest extends KernelTestCase
{
    private const string NOW = '2029-11-24 12:00:00';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testErasesOnlyLeadsOlderThanThreeYearsAndLogsEachErasure(): void
    {
        $expired = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000101')->createdAt('2026-11-24 11:59:59')->build());
        $expiredInCrm = LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000102')->createdAt('2026-09-01 10:00')->build();
        $expiredInCrm->markCrmExported(4217, new \DateTimeImmutable('2026-09-01 10:05'));
        $expiredInCrm->addNote('Перезвонить', new \DateTimeImmutable('2026-09-02'));
        $expiredInCrm = $this->persist($expiredInCrm);
        // Ровно 3 года назад -- ещё в сроке: удаляется только то, что строго старше.
        $boundary = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000103')->createdAt('2026-11-24 12:00:00')->build());
        $fresh = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000104')->createdAt('2029-11-01 10:00')->build());

        $result = $this->purger()->purge(200);

        self::assertSame(2, $result['erased']);
        self::assertSame([4217], $result['crmLeadIds']);
        self::assertNull($this->find($expired));
        self::assertNull($this->find($expiredInCrm));
        self::assertNotNull($this->find($boundary));
        self::assertNotNull($this->find($fresh));

        $log = $this->entityManager->getConnection()->fetchAllAssociative('SELECT lead_id, reason, crm_lead_id, erased_at FROM lead_erasure ORDER BY lead_id');
        self::assertSame([
            ['lead_id' => $expired, 'reason' => 'retention_expired', 'crm_lead_id' => null, 'erased_at' => self::NOW],
            ['lead_id' => $expiredInCrm, 'reason' => 'retention_expired', 'crm_lead_id' => 4217, 'erased_at' => self::NOW],
        ], $log);
        self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM lead_note'));
    }

    public function testLimitLeavesRestForNextRun(): void
    {
        $first = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000111')->createdAt('2025-01-01 10:00')->build());
        $second = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000112')->createdAt('2025-01-02 10:00')->build());

        self::assertSame(1, $this->purger()->purge(1)['erased']);
        self::assertNull($this->find($first));
        self::assertNotNull($this->find($second));

        self::assertSame(1, $this->purger()->purge(1)['erased']);
        self::assertSame(0, $this->purger()->purge(1)['erased']);
    }

    /**
     * Пока обращение передаётся в CRM (блокировку держит другой процесс), удалять его нельзя:
     * иначе созданный в Битрикс24 лид не попал бы в журнал. Автоудаление оставляет его на потом.
     */
    public function testLeadBeingExportedToCrmIsNotErased(): void
    {
        $id = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000121')->createdAt('2025-01-01 10:00')->build());
        $exporter = DriverManager::getConnection($this->entityManager->getConnection()->getParams());
        self::assertTrue((bool) $exporter->fetchOne('SELECT pg_try_advisory_lock(7340024, ?)', [$id]));

        try {
            self::assertSame(0, $this->purger()->purge(200)['erased']);
            $this->expectException(LeadExportInProgress::class);
            $this->eraser()->erase($id, LeadErasureReason::SUBJECT_REQUEST);
        } finally {
            self::assertNotNull($this->find($id));
            self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM lead_erasure'));
            $exporter->close();
        }
    }

    public function testCommandReportsCountAndCrmCopiesToDelete(): void
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('app:lead:purge-expired'));

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Удалено обращений: 0.', $tester->getDisplay());
        self::assertStringNotContainsString('Битрикс24', $tester->getDisplay());
    }

    private function purger(): ExpiredLeadPurger
    {
        return new ExpiredLeadPurger(self::getContainer()->get(LeadRepository::class), $this->eraser(), new MockClock(self::NOW));
    }

    private function eraser(): LeadEraser
    {
        return new LeadEraser(self::getContainer()->get(LeadRepository::class), self::getContainer()->get(LeadErasureRepository::class), $this->entityManager, new MockClock(self::NOW));
    }

    private function persist(Lead $lead): int
    {
        $this->entityManager->persist($lead);
        $this->entityManager->flush();

        return (int) $lead->id();
    }

    private function find(int $id): ?Lead
    {
        $this->entityManager->clear();

        return $this->entityManager->find(Lead::class, $id);
    }
}
