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
        yield 'обращения' => ['/admin/leads', 'Обращения'];
    }

    #[DataProvider('sections')]
    public function testSidebarMenuMarksCurrentSection(string $path, string $current): void
    {
        $this->logIn();
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(3, '.sidebar nav.menu li');
        self::assertSelectorCount(1, '.sidebar [aria-current="page"]');
        self::assertSelectorTextContains('.sidebar [aria-current="page"]', $current);
        self::assertSelectorExists('.sidebar form.account button[type="submit"]');
        // На узком экране то же меню раскрывается нативным details.
        self::assertSelectorCount(3, 'details.mobile-bar nav.menu li');
        self::assertSelectorTextContains('details.mobile-bar [aria-current="page"]', $current);
        self::assertSelectorExists('a.skip-link[href="#main"]');
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

        self::assertSelectorTextContains('.sidebar [aria-current="page"]', 'Публикации');
    }

    public function testCounterShowsNewLeadsAndHidesAtZero(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin');
        self::assertSelectorNotExists('.count');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000001')->build());
        $entityManager->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000002')->build());
        $entityManager->persist(LeadBuilder::aLead()->withSubmissionId('00000000-0000-4000-8000-000000000003')->spam()->build());
        $entityManager->flush();

        $this->client->request('GET', '/admin');
        self::assertSelectorTextSame('.sidebar a[href="/admin/leads"] .count [aria-hidden="true"]', '2');
        // Число -- для глаз, полный текст -- для экранных дикторов (aria-label на span не работает).
        self::assertSelectorTextSame('.sidebar .count .visually-hidden', 'новых обращений: 2');
        self::assertSelectorExists('details.mobile-bar summary .count');
    }

    public function testLoginPageHasNoSidebar(): void
    {
        $this->client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.sidebar');
        self::assertSelectorNotExists('.mobile-bar');
        self::assertSelectorExists('form.card input[name="_username"]');
    }

    public function testStylesComeFromSeparateAdminFileWithCurrentVersion(): void
    {
        $this->client->request('GET', '/admin/login');

        self::assertSelectorNotExists('style');
        self::assertSelectorCount(1, 'link[rel="stylesheet"]');
        // nginx отдаёт CSS как immutable: версия обязана меняться вместе с файлом.
        $css = file_get_contents(self::getContainer()->getParameter('kernel.project_dir').'/public/assets/admin/admin.css');
        self::assertIsString($css);
        self::assertSelectorExists(sprintf(
            'link[rel="stylesheet"][href="/assets/admin/admin.css?v=%s"]',
            substr(hash('sha256', $css), 0, 12),
        ));
    }

    private function logIn(): void
    {
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);
        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }
}
