<?php

declare(strict_types=1);

namespace App\Tests\Lead\Functional;

use App\Lead\Adapter\TelegramLeadNotifier;
use App\Lead\Entity\Lead;
use App\Lead\ValueObject\LeadStatus;
use App\Tests\Lead\Builder\LeadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    public function testCardShowsSourceOfLead(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->withAttribution([
            'first' => ['channel' => 'cpc', 'source' => 'yandex', 'medium' => 'cpc', 'campaign' => 'brand', 'landing' => '/services', 'click' => ['yclid' => '42']],
            'last' => ['channel' => 'organic', 'referrer' => 'https://ya.ru'],
            'visits' => 3,
        ], '1758600000123456789')->build());
        $this->logIn();

        $this->client->request('GET', '/admin/leads/'.$id);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Первый визит');
        self::assertSelectorTextContains('main', 'реклама, yandex / cpc, brand');
        self::assertSelectorTextContains('main', '/services');
        self::assertSelectorTextContains('main', 'yclid: 42');
        self::assertSelectorTextContains('main', 'Последний значимый визит');
        self::assertSelectorTextContains('main', 'поиск');
        self::assertSelectorTextContains('main', 'Визитов до заявки');
        self::assertSelectorTextContains('main', '1758600000123456789');
    }

    public function testCardOfLeadWithoutSourceStillRenders(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();

        $this->client->request('GET', '/admin/leads/'.$id);

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Первый визит', (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('ClientID', (string) $this->client->getResponse()->getContent());
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

        self::assertSelectorTextContains('[role="alert"]', 'изменили в другом окне');
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

        self::assertSelectorTextContains('[role="alert"]', 'Уведомление не отправлено');
    }

    public function testListRowActionsLiveInDropdownMenu(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads');

        $cell = $crawler->filter('tbody tr td')->last();
        $toggle = $cell->filter('details > summary[data-admin-menu-toggle]');
        self::assertSame('Действия: обращение №'.$id, $toggle->attr('aria-label'));
        self::assertSame('lead-actions-'.$id, $toggle->attr('aria-controls'));
        self::assertCount(1, $cell->filter('details'));
        self::assertCount(0, $cell->filter('button, a')->reduce(static fn ($node) => null === $node->closest('[data-admin-row-menu]')));

        $menu = $cell->filter('details > #lead-actions-'.$id);
        // Текущий статус «Новое» не предлагается; уведомление скрыто -- Telegram в тестах выключен.
        self::assertSame(
            ['Открыть', 'Статус: В работе', 'Статус: Квалифицировано', 'Статус: Спам', 'Статус: Закрыто', 'Удалить'],
            $menu->filter('a, button')->each(static fn ($node) => trim($node->text())),
        );
        self::assertSame('/admin/leads/'.$id, $menu->filter('a')->attr('href'));
        self::assertCount(5, $menu->filter('form[method="post"] input[name="_token"]'));
    }

    public function testResendItemFollowsNotificationRuleWhenTelegramEnabled(): void
    {
        $notified = LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000001')->build();
        $notified->markNotified(new \DateTimeImmutable('2026-09-01'));
        [$pending, $sent, $spam] = $this->persist(
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->build(),
            $notified,
            LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000003')->spam()->build(),
        );
        self::getContainer()->set(TelegramLeadNotifier::class, new TelegramLeadNotifier(
            new MockHttpClient(),
            self::getContainer()->get(UrlGeneratorInterface::class),
            'https://vashfindir.ru',
            'test-token',
            '-100123',
        ));
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads');

        $items = static fn (int $id): array => $crawler->filter('#lead-actions-'.$id.' button')->each(static fn ($node) => trim($node->text()));
        self::assertContains('Отправить уведомление', $items($pending));
        self::assertNotContains('Отправить уведомление', $items($sent));
        self::assertNotContains('Отправить уведомление', $items($spam));
    }

    public function testStatusChangeWithoutStatusFilterKeepsPage(): void
    {
        $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();

        $this->client->request('GET', '/admin/leads?form=consultation&page=1');
        $this->client->submitForm('Статус: В работе');

        self::assertResponseRedirects('/admin/leads?form=consultation&page=1');
    }

    public function testStatusChangeFromListKeepsNextContactAndReturnsToFilteredList(): void
    {
        $lead = LeadBuilder::aLead()->build();
        $lead->scheduleNextContact(new \DateTimeImmutable('2026-10-01'), new \DateTimeImmutable('2026-09-01'));
        [$id] = $this->persist($lead);
        $this->logIn();

        $this->client->request('GET', '/admin/leads?status=new&page=1');
        $this->client->submitForm('Статус: В работе');

        // Строка ушла из фильтра «Новое» -- возвращаемся на первую страницу того же фильтра.
        self::assertResponseRedirects('/admin/leads?status=new');
        self::assertSame(LeadStatus::IN_PROGRESS, $this->find($id)?->status());
        self::assertSame('2026-10-01', self::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->fetchOne('SELECT next_contact_at::date FROM lead_lead WHERE id = ?', [$id]));
    }

    public function testStaleStatusChangeFromListShowsMessageOnList(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads');
        $stale = $crawler->selectButton('Статус: Закрыто')->form();

        $this->client->submitForm('Статус: В работе');
        $this->client->submit($stale);
        self::assertResponseRedirects('/admin/leads');
        $this->client->followRedirect();

        self::assertSelectorTextContains('[role="alert"]', 'изменили в другом окне');
        self::assertSame(LeadStatus::IN_PROGRESS, $this->find($id)?->status());
    }

    public function testDeleteFromListReturnsToFilteredList(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();

        $this->client->request('GET', '/admin/leads?q=%D0%98%D0%B2%D0%B0%D0%BD');
        $this->client->submitForm('Удалить');

        self::assertResponseRedirects('/admin/leads?q=%D0%98%D0%B2%D0%B0%D0%BD');
        self::assertNull($this->find($id));
    }

    public function testResendFromListReturnsToSamePageWithReason(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads');
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/leads/'.$id.'/notify', ['_token' => $token, 'back' => '/admin/leads?form=diagnostics&page=1']);
        self::assertResponseRedirects('/admin/leads?form=diagnostics&page=1');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Уведомление не отправлено');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignBackUrls(): iterable
    {
        yield 'чужой сайт' => ['https://evil.example/admin/leads'];
        yield 'protocol-relative' => ['//evil.example/admin/leads'];
        yield 'другой раздел' => ['/admin/posts'];
        yield 'похожий путь' => ['/admin/leads/../posts'];
        yield 'перевод строки в конце' => ["/admin/leads\n"];
    }

    #[DataProvider('foreignBackUrls')]
    public function testForeignBackUrlIsIgnored(string $back): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads/'.$id);
        $form = $crawler->selectButton('Сохранить')->form();

        $this->client->request('POST', '/admin/leads/'.$id.'/update', ['back' => $back] + $form->getPhpValues());

        self::assertResponseRedirects('/admin/leads/'.$id);
    }

    public function testForeignBackUrlOnDeleteFallsBackToList(): void
    {
        [$id] = $this->persist(LeadBuilder::aLead()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/leads/'.$id);
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/leads/'.$id.'/delete', ['_token' => $token, 'back' => '//evil.example/admin/leads']);

        self::assertResponseRedirects('/admin/leads');
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
