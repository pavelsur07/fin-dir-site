<?php

declare(strict_types=1);

namespace App\Tests\Lead\Integration;

use App\Lead\Adapter\TelegramLeadNotifier;
use App\Lead\DTO\LeadSubmission;
use App\Lead\Entity\Lead;
use App\Lead\Repository\LeadRepository;
use App\Lead\Service\LeadNotificationSender;
use App\Lead\Service\LeadRegistrar;
use App\Lead\ValueObject\LeadStatus;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Регистрация на реальной базе: идемпотентность держится на уникальном индексе,
 * а «сохранить, потом уведомить» проверяется с отказавшим Telegram.
 */
final class LeadRegistrationTest extends KernelTestCase
{
    private const string NOW = '2026-09-23 12:00:00';

    private EntityManagerInterface $entityManager;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->clock = new MockClock(self::NOW);
    }

    public function testRegistersLeadWithNormalizedContactAndAnswers(): void
    {
        $submission = $this->submission('diagnostics');
        $submission->answers = ['channel' => 'ozon', 'turnover' => '1m_5m', 'need' => 'saas'];
        $submission->utm = ['utm_source' => 'yandex', 'foo' => 'bar'];

        $lead = $this->lead($this->registrar()->register($submission));

        self::assertSame(LeadStatus::NEW, $lead->status());
        self::assertSame('Ozon', $lead->answers()[0]['answerLabel']);
        self::assertSame(['utm_source' => 'yandex'], $lead->utm());
    }

    public function testRepeatedSubmissionReturnsSameLead(): void
    {
        $first = $this->registrar()->register($this->submission());
        $second = $this->registrar()->register($this->submission());

        self::assertSame($first, $second);
        self::assertSame(1, $this->entityManager->getRepository(Lead::class)->count([]));
    }

    public function testSubmissionWithoutIdGetsServerGeneratedOne(): void
    {
        $submission = $this->submission();
        $submission->submissionId = '';

        $this->registrar()->register($submission);

        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $submission->submissionId);
    }

    public function testHoneypotAndTooFastSubmissionsAreSpam(): void
    {
        $honeypot = $this->submission();
        $honeypot->website = 'https://spam.example';
        $tooFast = $this->submission('consultation', '22222222-2222-4222-8222-222222222222');
        $tooFast->fillMs = 1500;

        self::assertSame(LeadStatus::SPAM, $this->lead($this->registrar()->register($honeypot))->status());
        self::assertSame(LeadStatus::SPAM, $this->lead($this->registrar()->register($tooFast))->status());
    }

    public function testSlowHumanIsNotSpamRegardlessOfClock(): void
    {
        $human = $this->submission();
        $human->fillMs = 45000;

        self::assertSame(LeadStatus::NEW, $this->lead($this->registrar()->register($human))->status());
    }

    public function testTelegramFailureKeepsLeadAndRecordsError(): void
    {
        $http = new MockHttpClient(new MockResponse('{"ok":false}', ['http_code' => 502]));

        $id = $this->registrar($this->sender($http))->register($this->submission());

        $lead = $this->lead($id);
        self::assertSame(LeadStatus::NEW, $lead->status());
        self::assertNull($lead->notifiedAt());
        self::assertSame('Telegram API responded 502', $lead->notificationError());
    }

    public function testSuccessfulNotificationIsSentWithoutPersonalData(): void
    {
        $sent = null;
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = ['url' => $url, 'body' => (string) $options['body']];

            return new MockResponse('{"ok":true}', ['http_code' => 200]);
        });

        $id = $this->registrar($this->sender($http))->register($this->submission());

        self::assertNotNull($this->lead($id)->notifiedAt());
        self::assertIsArray($sent);
        self::assertStringContainsString('/bottest-token/sendMessage', $sent['url']);
        self::assertStringNotContainsString('Иван', $sent['body']);
        self::assertStringNotContainsString('9001234567', $sent['body']);
        self::assertStringContainsString('\/admin\/leads\/'.$id, $sent['body']);
    }

    public function testPendingNotificationsAreResent(): void
    {
        foreach ([
            LeadBuilder::aLead()->withSubmissionId('33333333-3333-4333-8333-333333333333')->createdAt('2026-09-22 10:00')->build(),
            LeadBuilder::aLead()->withSubmissionId('44444444-4444-4444-8444-444444444444')->createdAt('2026-09-01 10:00')->build(),
            LeadBuilder::aLead()->withSubmissionId('55555555-5555-4555-8555-555555555555')->spam()->createdAt('2026-09-22 10:00')->build(),
        ] as $lead) {
            $this->entityManager->persist($lead);
        }
        $this->entityManager->flush();

        $calls = 0;
        $http = new MockHttpClient(static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse('{"ok":true}', ['http_code' => 200]);
        });

        $result = $this->sender($http)->sendPending($this->clock->now()->modify('-7 days'), 50);

        // Старое (за окном 7 дней) и спам не досылаются.
        self::assertSame(['sent' => 1, 'failed' => 0], $result);
        self::assertSame(1, $calls);
    }

    private function submission(string $form = 'consultation', string $id = '11111111-1111-4111-8111-111111111111'): LeadSubmission
    {
        $submission = new LeadSubmission();
        $submission->form = $form;
        $submission->submissionId = $id;
        $submission->name = 'Иван';
        $submission->contact = '8 900 123-45-67';
        $submission->agreement = true;
        $submission->pageUrl = '/';

        return $submission;
    }

    private function registrar(?LeadNotificationSender $sender = null): LeadRegistrar
    {
        return new LeadRegistrar(
            self::getContainer()->get(LeadRepository::class),
            $this->entityManager,
            $sender ?? $this->sender(new MockHttpClient()),
            $this->clock,
        );
    }

    private function sender(MockHttpClient $http): LeadNotificationSender
    {
        $notifier = new TelegramLeadNotifier(
            $http,
            self::getContainer()->get(UrlGeneratorInterface::class),
            'https://vashfindir.ru',
            'test-token',
            '-100123',
        );

        return new LeadNotificationSender($notifier, self::getContainer()->get(LeadRepository::class), $this->entityManager, $this->clock);
    }

    private function lead(int $id): Lead
    {
        $this->entityManager->clear();

        return $this->entityManager->find(Lead::class, $id) ?? self::fail('Lead not found');
    }
}
