<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DocsStubTest extends WebTestCase
{
    public function testDocsHostRootShowsStubWithoutSession(): void
    {
        $client = self::createClient([], ['HTTP_HOST' => 'docs.vashfindir.ru']);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'База знаний в разработке');
        self::assertSelectorExists('meta[name="robots"][content="noindex, follow"]');
        self::assertSelectorExists('link[rel="canonical"][href="https://docs.vashfindir.ru/"]');
        self::assertSame([], $client->getResponse()->headers->getCookies());
    }

    public function testDocsHostOtherPathsRedirectToMainSite(): void
    {
        $client = self::createClient([], ['HTTP_HOST' => 'docs.vashfindir.ru']);

        $client->request('GET', '/share/hn9f97nx4m/p/vaIvbFr1b5?x=1');
        self::assertResponseRedirects('https://vashfindir.ru/share/hn9f97nx4m/p/vaIvbFr1b5?x=1', 301);

        $client->request('GET', '/a%3Fb/%D0%B1%0D%0A');
        self::assertResponseRedirects('https://vashfindir.ru/a%3Fb/%D0%B1%0D%0A', 301);

        // Админка на этом хосте не открывается и вход не принимается: firewall для хоста выключен.
        $client->request('GET', '/admin');
        self::assertResponseRedirects('https://vashfindir.ru/admin', 301);
        $client->request('POST', '/admin/login', ['_username' => 'admin', '_password' => 'x']);
        self::assertResponseRedirects('https://vashfindir.ru/admin/login', 301);
        self::assertSame([], $client->getResponse()->headers->getCookies());
    }

    public function testMainHostKeepsHomePage(): void
    {
        $client = self::createClient([], ['HTTP_HOST' => 'vashfindir.ru']);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('h1', 'База знаний');
    }
}
