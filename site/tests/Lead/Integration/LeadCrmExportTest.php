<?php

declare(strict_types=1);

namespace App\Tests\Lead\Integration;

use App\Bitrix24\Adapter\Bitrix24Client;
use App\Bitrix24\Adapter\WebhookCipher;
use App\Bitrix24\Entity\Bitrix24Connection;
use App\Bitrix24\Repository\Bitrix24ConnectionRepository;
use App\Bitrix24\Service\Bitrix24LeadSender;
use App\Lead\Adapter\LeadAdminLink;
use App\Lead\Entity\Lead;
use App\Lead\Repository\LeadRepository;
use App\Lead\Service\LeadCrmExporter;
use App\Lead\ValueObject\LeadConsent;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Передача обращения в Битрикс24 на реальной базе; HTTP подменён.
 */
final class LeadCrmExportTest extends KernelTestCase
{
    private const string CRM_CONSENT = 'test-crm-consent';
    private const string WEBHOOK = 'https://vf.bitrix24.ru/rest/1/abcdefgh12/';

    private EntityManagerInterface $entityManager;
    private WebhookCipher $cipher;

    /** @var list<array{url: string, body: array<string, mixed>}> */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->cipher = self::getContainer()->get(WebhookCipher::class);
    }

    public function testCreatesLeadWithContactCommentsAndUtm(): void
    {
        $this->connect();
        $lead = $this->persist(LeadBuilder::aLead()->withConsentVersion(self::CRM_CONSENT)->build());

        self::assertTrue($this->exporter([$this->ok(5001)])->export($lead, mayExist: false));

        $stored = $this->reload($lead);
        self::assertSame(5001, $stored->crmLeadId());
        self::assertFalse($stored->hadCrmExportAttempt());
        self::assertCount(1, $this->requests);
        self::assertSame(self::WEBHOOK.'crm.lead.add.json', $this->requests[0]['url']);
        $fields = $this->requests[0]['body']['fields'];
        self::assertIsArray($fields);
        self::assertSame('Иван', $fields['NAME']);
        self::assertSame([['VALUE' => '+79001234567', 'VALUE_TYPE' => 'WORK']], $fields['PHONE']);
        self::assertSame((string) $lead->id(), $fields['ORIGIN_ID']);
        self::assertSame(Bitrix24Client::ORIGINATOR, $fields['ORIGINATOR_ID']);
        self::assertIsString($fields['COMMENTS']);
        self::assertStringContainsString('Задача: Нужен отчёт ДДС', $fields['COMMENTS']);
        self::assertStringContainsString('/admin/leads/'.$lead->id(), $fields['COMMENTS']);
    }

    public function testFailureIsRecordedWithoutSecretAndRetryFindsExistingLead(): void
    {
        $this->connect();
        $lead = $this->persist(LeadBuilder::aLead()->withConsentVersion(self::CRM_CONSENT)->build());

        self::assertFalse($this->exporter([new MockResponse('', ['error' => new TimeoutException()])])->export($lead, mayExist: false));
        $failed = $this->reload($lead);
        self::assertNull($failed->crmLeadId());
        self::assertTrue($failed->hadCrmExportAttempt());

        // Таймаут мог дойти до CRM: повтор сначала ищет лид по ORIGIN_ID и не создаёт второй.
        $this->requests = [];
        self::assertTrue($this->exporter([new MockResponse(json_encode(['result' => [['ID' => '777']]], \JSON_THROW_ON_ERROR))])->resend((int) $lead->id()));
        self::assertSame(777, $this->reload($lead)->crmLeadId());
        self::assertSame([self::WEBHOOK.'crm.lead.list.json'], array_column($this->requests, 'url'));
        self::assertSame([['=ORIGINATOR_ID' => Bitrix24Client::ORIGINATOR, '=ORIGIN_ID' => (string) $lead->id()]], array_column(array_column($this->requests, 'body'), 'filter'));
    }

    public function testBitrixErrorCodeIsStoredAsCategory(): void
    {
        $this->connect();
        $lead = $this->persist(LeadBuilder::aLead()->withConsentVersion(self::CRM_CONSENT)->build());

        $this->exporter([new MockResponse('{"error":"insufficient_scope","error_description":"secret details https://x"}', ['http_code' => 401])])->export($lead, mayExist: false);

        self::assertTrue($this->reload($lead)->hadCrmExportAttempt());
        $error = $this->entityManager->getConnection()->fetchOne('SELECT crm_export_error FROM lead_lead WHERE id = ?', [$lead->id()]);
        self::assertSame('insufficient_scope', $error);
    }

    public function testSpamCurrentConsentAndDisabledConnectionAreNotSent(): void
    {
        $this->connect();
        $spam = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->withConsentVersion(self::CRM_CONSENT)->spam()->build());
        $oldConsent = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000003')->build());

        self::assertFalse($this->exporter([])->export($spam));
        self::assertFalse($this->exporter([])->export($oldConsent));
        // Боевой список версий пуст: действующее согласие не разрешает передачу.
        self::assertFalse(LeadConsent::allowsCrmTransfer(LeadConsent::VERSION));

        $allowed = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000004')->withConsentVersion(self::CRM_CONSENT)->build());
        $connection = self::getContainer()->get(Bitrix24ConnectionRepository::class)->find();
        self::assertNotNull($connection);
        $connection->changeEnabled(false, new \DateTimeImmutable());
        $this->entityManager->flush();
        self::assertFalse($this->exporter([])->export($allowed));
        self::assertSame([], $this->requests);
    }

    public function testPendingExportSkipsAlreadySentAndOldLeads(): void
    {
        $this->connect();
        $sent = LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000005')->withConsentVersion(self::CRM_CONSENT)->createdAt('2026-11-20 10:00')->build();
        $sent->markCrmExported(1, new \DateTimeImmutable());
        $this->persist($sent);
        $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000006')->withConsentVersion(self::CRM_CONSENT)->createdAt('2026-10-01 10:00')->build());
        $pending = $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000007')->withConsentVersion(self::CRM_CONSENT)->createdAt('2026-11-21 10:00')->build());
        // Минуту назад: его ещё передаёт регистрация, досылка не трогает.
        $this->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000008')->withConsentVersion(self::CRM_CONSENT)->createdAt('2026-11-22 11:59')->build());

        $result = $this->exporter([new MockResponse('{"result":[]}'), $this->ok(9)])->exportPending(new \DateTimeImmutable('2026-11-15'), 50);

        self::assertSame(['sent' => 1, 'failed' => 0], $result);
        self::assertSame(9, $this->reload($pending)->crmLeadId());
    }

    public function testParallelExportOfSameLeadIsSkipped(): void
    {
        $this->connect();
        $lead = $this->persist(LeadBuilder::aLead()->withConsentVersion(self::CRM_CONSENT)->build());

        // Другой процесс держит блокировку этого обращения -- отдельное соединение с БД.
        $other = DriverManager::getConnection($this->entityManager->getConnection()->getParams());
        self::assertTrue((bool) $other->fetchOne('SELECT pg_try_advisory_lock(7340024, ?)', [$lead->id()]));
        try {
            self::assertFalse($this->exporter([])->export($lead));
            self::assertSame([], $this->requests);
        } finally {
            $other->executeQuery('SELECT pg_advisory_unlock(7340024, ?)', [$lead->id()]);
            $other->close();
        }

        self::assertTrue($this->exporter([new MockResponse('{"result":[]}'), $this->ok(42)])->export($lead));
    }

    private function connect(): void
    {
        $this->entityManager->persist(new Bitrix24Connection($this->cipher->encrypt(self::WEBHOOK), 'vf.bitrix24.ru', 'mask', new \DateTimeImmutable()));
        $this->entityManager->flush();
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function exporter(array $responses): LeadCrmExporter
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->requests[] = ['url' => $url, 'body' => json_decode((string) ($options['body'] ?? '{}'), true, flags: \JSON_THROW_ON_ERROR)];

            return array_shift($responses) ?? self::fail('Unexpected request '.$url);
        });

        return new LeadCrmExporter(
            new Bitrix24LeadSender(self::getContainer()->get(Bitrix24ConnectionRepository::class), new Bitrix24Client($http), $this->cipher),
            self::getContainer()->get(LeadRepository::class),
            self::getContainer()->get(LeadAdminLink::class),
            $this->entityManager,
            new MockClock('2026-11-22 12:00:00'),
            [self::CRM_CONSENT],
        );
    }

    private function ok(int $id): MockResponse
    {
        return new MockResponse(json_encode(['result' => $id], \JSON_THROW_ON_ERROR));
    }

    private function persist(Lead $lead): Lead
    {
        $this->entityManager->persist($lead);
        $this->entityManager->flush();

        return $lead;
    }

    private function reload(Lead $lead): Lead
    {
        $id = $lead->id();
        $this->entityManager->clear();

        return $this->entityManager->find(Lead::class, $id) ?? self::fail('Lead not found');
    }
}
