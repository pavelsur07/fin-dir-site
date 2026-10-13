<?php

declare(strict_types=1);

namespace App\Tests\Admin;

use App\Tests\Lead\Builder\LeadBuilder;
use App\Tests\Publication\Builder\PostBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AdminLayoutTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sections(): iterable
    {
        yield 'панель' => ['/admin', 'Панель'];
        yield 'публикации' => ['/admin/posts', 'Публикации'];
        yield 'кейсы' => ['/admin/cases', 'Кейсы'];
        yield 'обращения' => ['/admin/leads', 'Обращения'];
    }

    #[DataProvider('sections')]
    public function testSidebarMenuMarksCurrentSection(string $path, string $current): void
    {
        $this->logIn();
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(4, '[data-admin-sidebar] nav[data-admin-menu] li');
        self::assertSelectorCount(1, '[data-admin-sidebar] [aria-current="page"]');
        self::assertSelectorTextContains('[data-admin-sidebar] [aria-current="page"]', $current);
        self::assertSelectorExists('[data-admin-sidebar] form[data-admin-account] button[type="submit"]');
        // На узком экране то же меню раскрывается нативным details.
        self::assertSelectorCount(4, 'details[data-admin-mobile-menu] nav[data-admin-menu] li');
        self::assertSelectorTextContains('details[data-admin-mobile-menu] [aria-current="page"]', $current);
        self::assertSelectorExists('a[href="#main"]');
        self::assertSelectorExists('main#main');
    }

    public function testNestedPageKeepsSectionActive(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $post = PostBuilder::aPost()->withSlug('layout-test')->build();
        $entityManager->persist($post);
        $entityManager->flush();
        $this->logIn();

        $this->client->request('GET', '/admin/posts/'.$post->id().'/edit');

        self::assertSelectorTextContains('[data-admin-sidebar] [aria-current="page"]', 'Публикации');
    }

    public function testCounterShowsNewLeadsAndHidesAtZero(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin');
        self::assertSelectorNotExists('[data-admin-count]');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000001')->build());
        $entityManager->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->build());
        $entityManager->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000003')->spam()->build());
        $entityManager->flush();

        $this->client->request('GET', '/admin');
        self::assertSelectorTextSame('[data-admin-sidebar] a[href="/admin/leads"] [data-admin-count] [aria-hidden="true"]', '2');
        // Число -- для глаз, полный текст -- для экранных дикторов (aria-label на span не работает).
        self::assertSelectorTextSame('[data-admin-sidebar] [data-admin-count] .sr-only', 'новых обращений: 2');
        self::assertSelectorExists('details[data-admin-mobile-menu] summary [data-admin-count]');
    }

    public function testLoginPageHasNoSidebar(): void
    {
        $this->client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-admin-sidebar]');
        self::assertSelectorNotExists('[data-admin-mobile-menu]');
        self::assertSelectorExists('form[action="/admin/login"] input[name="_username"]');
    }

    /** @return iterable<string, array{string}> */
    public static function assetPages(): iterable
    {
        yield 'login' => ['/admin/login'];
        yield 'dashboard' => ['/admin'];
        yield 'post form' => ['/admin/posts/new'];
    }

    #[DataProvider('assetPages')]
    public function testAdminLoadsOnlyItsOwnAssets(string $path): void
    {
        if ('/admin/login' !== $path) {
            $this->logIn();
        }
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'link[rel="stylesheet"]');
        self::assertSelectorExists('link[rel="stylesheet"][href^="/assets/admin/app.css?v="]');
        self::assertSelectorExists('script[src^="/assets/admin/row-menus.js?v="]');
        self::assertSelectorNotExists('link[href^="/assets/website/"], script[src^="/assets/website/"]');
        if ('/admin/posts/new' === $path) {
            self::assertSelectorExists('script[src^="/assets/admin/tabs.js?v="]');
        }
    }

    public function testAdminAssetVersionMatchesItsOwnBundle(): void
    {
        $root = self::getContainer()->getParameter('kernel.project_dir');
        $contents = '';
        foreach (['app.css', 'row-menus.js', 'tabs.js'] as $asset) {
            $contents .= (string) file_get_contents($root.'/public/assets/admin/'.$asset);
        }
        $version = substr(hash('sha256', $contents), 0, 12);
        $this->client->request('GET', '/admin/login');
        self::assertSelectorExists('link[href="/assets/admin/app.css?v='.$version.'"]');
    }

    private function logIn(): void
    {
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);
        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }
}
