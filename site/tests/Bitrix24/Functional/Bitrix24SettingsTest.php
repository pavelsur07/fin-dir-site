<?php

declare(strict_types=1);

namespace App\Tests\Bitrix24\Functional;

use App\Bitrix24\Adapter\Bitrix24Client;
use App\Bitrix24\Adapter\WebhookCipher;
use App\Bitrix24\Entity\Bitrix24Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class Bitrix24SettingsTest extends WebTestCase
{
    private const string WEBHOOK = 'https://vf.bitrix24.ru/rest/7/secrettoken123abc/';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Подменённый HTTP-клиент Битрикс24 должен пережить несколько запросов теста.
        $this->client->disableReboot();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedRoutes(): iterable
    {
        yield 'раздел' => ['GET', '/admin/settings'];
        yield 'экран' => ['GET', '/admin/settings/bitrix24'];
        yield 'подключить' => ['POST', '/admin/settings/bitrix24/connect'];
        yield 'проверить' => ['POST', '/admin/settings/bitrix24/check'];
        yield 'вкл/выкл' => ['POST', '/admin/settings/bitrix24/enabled'];
        yield 'отключить' => ['POST', '/admin/settings/bitrix24/disconnect'];
    }

    #[DataProvider('protectedRoutes')]
    public function testGuestIsRedirectedToLogin(string $method, string $path): void
    {
        $this->client->request($method, $path);

        self::assertResponseRedirects('/admin/login');
    }

    public function testMenuLeadsToBitrix24Settings(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/settings');
        self::assertResponseRedirects('/admin/settings/bitrix24');

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-admin-sidebar] a[aria-current="page"]', 'Настройки');
        self::assertSelectorTextContains('h2', 'Подключение к Битрикс24');
        self::assertSelectorTextContains('section', 'Не подключено');
    }

    public function testPostWithoutCsrfIsRejected(): void
    {
        $this->logIn();
        $this->client->request('POST', '/admin/settings/bitrix24/connect', ['webhook' => self::WEBHOOK, '_token' => 'bad']);

        self::assertResponseStatusCodeSame(403);
        self::assertNull($this->connection());
    }

    public function testConnectChecksPortalStoresEncryptedAndShowsOnlyMask(): void
    {
        $requests = $this->mockBitrix([new MockResponse('{"result":{"ID":{}}}')]);
        $this->logIn();

        $this->submit('connect', ['webhook' => self::WEBHOOK]);
        $crawler = $this->client->followRedirect();

        self::assertSame([self::WEBHOOK.'crm.lead.fields.json'], $requests->urls);
        self::assertSelectorTextContains('[role="status"]', 'Битрикс24 подключён');
        self::assertSelectorTextContains('dl', 'vf.bitrix24.ru/rest/7/…3abc/');
        self::assertStringNotContainsString('secrettoken123', (string) $this->client->getResponse()->getContent());
        self::assertSame('', $crawler->filter('#bitrix24-webhook')->attr('value') ?? '');

        $stored = $this->connection();
        self::assertNotNull($stored);
        self::assertStringNotContainsString('secrettoken123', $stored->encryptedWebhook());
        self::assertSame(self::WEBHOOK, self::getContainer()->get(WebhookCipher::class)->decrypt($stored->encryptedWebhook()));
    }

    public function testInvalidOrRejectedWebhookIsNotSaved(): void
    {
        $this->mockBitrix([new MockResponse('{"error":"NO_AUTH_FOUND"}', ['http_code' => 401])]);
        $this->logIn();

        $this->submit('connect', ['webhook' => 'https://evil.example/rest/1/abcdefgh12/']);
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Это не адрес входящего вебхука');

        $this->submit('connect', ['webhook' => self::WEBHOOK]);
        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'вебхук не принят');
        self::assertNull($this->connection());
    }

    public function testToggleAndDisconnect(): void
    {
        $this->mockBitrix([new MockResponse('{"result":{}}')]);
        $this->logIn();
        $this->submit('connect', ['webhook' => self::WEBHOOK]);

        $this->submit('enabled', ['enabled' => '0']);
        self::assertFalse($this->connection()?->isEnabled());

        $this->submit('disconnect');
        self::assertNull($this->connection());
    }

    /**
     * @param array<string, string> $data
     */
    private function submit(string $action, array $data = []): void
    {
        $crawler = $this->client->request('GET', '/admin/settings/bitrix24');
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/settings/bitrix24/'.$action, $data + ['_token' => $token]);
        self::assertResponseRedirects('/admin/settings/bitrix24');
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function mockBitrix(array $responses): \stdClass
    {
        $log = new \stdClass();
        $log->urls = [];
        $http = new MockHttpClient(static function (string $method, string $url) use (&$responses, $log): MockResponse {
            $log->urls[] = $url;

            return array_shift($responses) ?? new MockResponse('{"result":{}}');
        });
        self::getContainer()->set(Bitrix24Client::class, new Bitrix24Client($http));

        return $log;
    }

    private function connection(): ?Bitrix24Connection
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->find(Bitrix24Connection::class, Bitrix24Connection::SINGLETON_ID);
    }

    private function logIn(): void
    {
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);
        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }
}
