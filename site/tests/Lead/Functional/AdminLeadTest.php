<?php

declare(strict_types=1);

namespace App\Tests\Lead\Functional;

use App\Lead\Entity\Lead;
use App\Lead\ValueObject\LeadStatus;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AdminLeadTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedRoutes(): iterable
    {
        yield 'список' => ['GET', '/admin/leads'];
        yield 'карточка' => ['GET', '/admin/leads/1'];
        yield 'статус' => ['POST', '/admin/leads/1/update'];
        yield 'заметка' => ['POST', '/admin/leads/1/notes'];
        yield 'уведомление' => ['POST', '/admin/leads/1/notify'];
        yield 'удаление' => ['POST', '/admin/leads/1/delete'];
    }

    #[DataProvider('protectedRoutes')]
    public function testGuestIsRedirectedToLogin(string $method, string $path): void
    {
        $this->client->request($method, $path);

        self::assertResponseRedirects('/admin/login');
    }

    public function testListFiltersAndSearchesByNormalizedContact(): void
    {
        $this->persist(
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000001')->withName('Иван')->withContact('8 (900) 111-22-33')->build(),
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->withName('Спамер')->withContact('bot@spam.io')->spam()->build(),
        );
        $this->logIn();

        $this->client->request('GET', '/admin/leads?q=%2B7%20900%20111');
        self::assertSelectorTextContains('tbody', 'Иван');
        self::assertSelectorTextNotContains('tbody', 'Спамер');

        // Часть номера в другом формате, в том числе через 8.
        $this->client->request('GET', '/admin/leads?q=111-22');
        self::assertSelectorTextContains('tbody', 'Иван');
        $this->client->request('GET', '/admin/leads?q=8%20900%20111');
        self::assertSelectorTextContains('tbody', 'Иван');

        $this->client->request('GET', '/admin/leads?q=BOT%40SPAM.IO');
        self::assertSelectorTextContains('tbody', 'Спамер');

        $this->client->request('GET', '/admin/leads?status=spam');
        self::assertSelectorTextContains('tbody', 'Спамер');
        self::assertSelectorTextNotContains('tbody', 'Иван');

        $this->client->request('GET', '/admin/leads?status=deleted');
        self::assertResponseStatusCodeSame(400);
    }

    public function testCardShowsLeadAndRelatedByContact(): void
    {
        [$first, $second] = $this->persist(
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000001')->build(),
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->build(),
        );
        $this->logIn();

        $this->client->request('GET', '/admin/leads/'.$second);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Обращение №'.$second);
        self::assertSelectorTextContains('main', '+7 900 123-45-67');
        self::assertSelectorTextContains('main', 'уведомления выключены');
        self::assertSelectorExists('a[href="/admin/leads/'.$first.'"]');
    }

    public function testStatusNextContactAndNoteAreSaved(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();

        $this->client->request('GET', '/admin/leads/'.$id);
        $this->client->submitForm('Сохранить', ['status' => 'qualified', 'next_contact_at' => '2026-10-01']);
        self::assertResponseRedirects('/admin/leads/'.$id);

        $this->client->request('GET', '/admin/leads/'.$id);
        $this->client->submitForm('Добавить заметку', ['text' => 'Созвонились, ждёт КП']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('main', 'Созвонились, ждёт КП');
        self::assertSelectorExists('input[name="next_contact_at"][value="2026-10-01"]');
        self::assertSame(LeadStatus::QUALIFIED, $this->find($id)?->status());
    }

    public function testStaleVersionShowsMessageInsteadOfOverwriting(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads/'.$id);
        $stale = $crawler->selectButton('Сохранить')->form();

        $this->client->submitForm('Сохранить', ['status' => 'in_progress']);
        $this->client->submit($stale, ['status' => 'closed']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert', 'изменили в другом окне');
        self::assertSame(LeadStatus::IN_PROGRESS, $this->find($id)?->status());
    }

    public function testActionsRequireCsrfToken(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();

        $this->client->request('POST', '/admin/leads/'.$id.'/delete', ['_token' => 'forged']);

        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($this->find($id));
    }

    public function testDeleteRemovesLeadWithNotes(): void
    {
        $lead = LeadBuilder::aLead()->build();
        $lead->addNote('Заметка', new \DateTimeImmutable('2026-09-02'));
        [$id] = $this->persist($lead);
        $this->logIn();

        $this->client->request('GET', '/admin/leads/'.$id);
        $this->client->submitForm('Удалить обращение');

        self::assertResponseRedirects('/admin/leads');
        self::assertNull($this->find($id));
        self::assertSame(0, (int) self::getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne('SELECT COUNT(*) FROM lead_note'));
    }

    public function testResendWithDisabledTelegramExplainsWhy(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads/'.$id);
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/leads/'.$id.'/notify', ['_token' => $token]);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert', 'Уведомление не отправлено');
    }

    public function testUnknownLeadIsNotFound(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/leads/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function logIn(): void
    {
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);
        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }

    /**
     * @return list<int>
     */
    private function persist(Lead ...$leads): array
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        foreach ($leads as $lead) {
            $entityManager->persist($lead);
        }
        $entityManager->flush();

        return array_values(array_map(static fn (Lead $lead): int => (int) $lead->id(), $leads));
    }

    private function find(int $id): ?Lead
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->find(Lead::class, $id);
    }
}
