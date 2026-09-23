<?php

declare(strict_types=1);

namespace App\Tests\Admin;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminAuthTest extends WebTestCase
{
    private const string PASSWORD = 'test-password'; // хеш в phpunit.dist.xml

    public function testGuestIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/admin/login');
    }

    public function testLoginPageIsNotIndexed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testWrongPasswordShowsError(): void
    {
        $client = static::createClient();
        $this->logIn($client, 'wrong-password');

        $this->assertLoginRejected($client, 'Неверный логин или пароль');
    }

    public function testForgedCsrfTokenIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');
        $client->submitForm('Войти', [
            '_username' => 'admin',
            '_password' => self::PASSWORD,
            '_csrf_token' => 'forged',
        ]);

        $this->assertLoginRejected($client, 'Страница входа устарела');
    }

    public function testMissingCsrfTokenIsRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/admin/login', [
            '_username' => 'admin',
            '_password' => self::PASSWORD,
        ]);

        $this->assertLoginRejected($client, 'Страница входа устарела');
    }

    public function testLoginOpensDashboardAndLogoutClosesIt(): void
    {
        $client = static::createClient();
        $this->logIn($client, self::PASSWORD);

        self::assertResponseRedirects('/admin');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Панель контент-менеджера');

        $client->request('GET', '/admin/login');
        self::assertResponseRedirects('/admin');

        $client->request('GET', '/admin');
        $client->submitForm('Выйти');
        self::assertResponseRedirects('/admin/login');

        $client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/login');
    }

    public function testLogoutRequiresCsrfToken(): void
    {
        $client = static::createClient();
        $this->logIn($client, self::PASSWORD);

        $client->request('POST', '/admin/logout', ['_csrf_token' => 'forged']);
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
    }

    private function assertLoginRejected(KernelBrowser $client, string $message): void
    {
        self::assertResponseRedirects('/admin/login');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert', $message);

        $client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/login');
    }

    private function logIn(KernelBrowser $client, string $password): void
    {
        $client->request('GET', '/admin/login');
        $client->submitForm('Войти', [
            '_username' => 'admin',
            '_password' => $password,
        ]);
    }
}
