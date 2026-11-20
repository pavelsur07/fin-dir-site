<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Controller;

use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AdminDocumentationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient([], ['HTTP_HOST' => 'vashfindir.ru']);
    }

    private function login(): void
    {
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);
        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }

    public function testGuestCannotReadOrChangeDocumentation(): void
    {
        foreach (['/admin/docs/pages', '/admin/docs/sections', '/admin/docs/pages/1/preview', '/admin/docs/link-picker'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseRedirects('/admin/login');
        }
        $this->client->request('POST', '/admin/docs/pages/1/publish');
        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateSectionDraftPreviewPublishSearchAndUnpublish(): void
    {
        $this->login();
        $this->client->request('GET', '/admin/docs/sections/new');
        $this->client->submitForm('Сохранить раздел', ['doc_section[title]' => 'Отчёты', 'doc_section[slug]' => 'reports']);
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $id = (int) $em->getConnection()->fetchOne("SELECT id FROM documentation_section WHERE slug='reports'");
        $this->client->request('GET', '/admin/docs/pages/new');
        $this->client->submitForm('Создать черновик', ['doc_page[title]' => 'Настройка отчёта', 'doc_page[slug]' => 'setup', 'doc_page[sectionId]' => (string) $id, 'doc_page[description]' => 'Инструкция', 'doc_page[body]' => "## Загрузка\n\nНастройка операций."]);
        self::assertResponseRedirects();
        $edit = (string) $this->client->getResponse()->headers->get('Location');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        $crawler = $this->client->getCrawler();
        $preview = $crawler->selectLink('Предпросмотр сохранённого текста')->link()->getUri();
        $form = $crawler->selectButton('Опубликовать')->form();
        $this->client->request('GET', $preview);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
        $this->client->submit($form);
        self::assertResponseRedirects($edit);
        $this->client->request('GET', 'https://docs.vashfindir.ru/articles/setup');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', 'https://vashfindir.ru'.$edit);
        $this->client->submitForm('Снять с публикации');
        self::assertResponseRedirects();
        $this->client->request('GET', 'https://docs.vashfindir.ru/articles/setup');
        self::assertResponseStatusCodeSame(404);
    }

    public function testCsrfAndReadOnlyStatusRoute(): void
    {
        $this->login();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $page = DocPageBuilder::aPage($section);
        $em->persist($section);
        $em->persist($page);
        $em->flush();
        $id = $page->id();
        $this->client->request('POST', '/admin/docs/pages/'.$id.'/publish', ['version' => 1, '_token' => 'wrong']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/admin/docs/pages/'.$id.'/publish');
        self::assertResponseStatusCodeSame(405);
        $this->client->request('GET', '/admin/docs/link-picker?target='.$id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('textarea', 'doc:'.$id);
    }

    public function testInvalidPageFormReturns422(): void
    {
        $this->login();
        $this->client->request('GET', '/admin/docs/pages/new');
        $this->client->submitForm('Создать черновик', ['doc_page[title]' => '', 'doc_page[slug]' => 'Неверно']);
        self::assertResponseStatusCodeSame(422);
    }

    public function testAuthenticatedUserWithoutAdminRoleGets403(): void
    {
        $this->client->disableReboot();
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(\Symfony\Component\Security\Core\User\InMemoryUserProvider::class, $provider);
        $viewer = new \Symfony\Component\Security\Core\User\InMemoryUser('viewer', 'hash', ['ROLE_USER']);
        $provider->createUser($viewer);
        $this->client->loginUser($viewer, 'admin');
        $this->client->request('GET', '/admin/docs/pages');
        self::assertResponseStatusCodeSame(403);
    }

    public function testSubmittedIndentedCodeIsNotTrimmed(): void
    {
        $this->login();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $em->flush();
        $body = '    [Пример](doc:999999)';
        $this->client->request('GET', '/admin/docs/pages/new');
        $this->client->submitForm('Создать черновик', ['doc_page[title]' => 'Пример', 'doc_page[slug]' => 'code-example', 'doc_page[sectionId]' => (string) $section->id(), 'doc_page[body]' => $body]);
        self::assertResponseRedirects();
        self::assertSame($body, self::getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne("SELECT body FROM documentation_page WHERE slug='code-example'"));
    }

    public function testExpectedConflictShowsReasonAndPreservesInput(): void
    {
        $this->login();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $em->persist(DocPageBuilder::aPage($section, 'taken'));
        $em->flush();
        $body = 'Новый текст, который нельзя потерять';
        $this->client->request('GET', '/admin/docs/pages/new');
        $this->client->submitForm('Создать черновик', ['doc_page[title]' => 'Новое', 'doc_page[slug]' => 'taken', 'doc_page[sectionId]' => (string) $section->id(), 'doc_page[body]' => $body]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main form', 'Этот адрес уже занят');
        self::assertSame($body, $this->client->getCrawler()->filter('textarea[name="doc_page[body]"]')->text());
    }

    public function testUnknownRelatedPageShowsErrorWithoutLosingText(): void
    {
        $this->login();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $section = DocSectionBuilder::aSection();
        $em->persist($section);
        $page = DocPageBuilder::aPage($section);
        $em->persist($page);
        $em->flush();
        $this->client->request('GET', '/admin/docs/pages/'.$page->id().'/edit');
        $this->client->submitForm('Сохранить страницу', ['doc_page[body]' => 'Новый сохранённый ввод', 'doc_page[relatedLinks]' => '[Missing](doc:2147483647)']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main', 'Страница в «Читайте также» не найдена');
        self::assertSame('Новый сохранённый ввод', $this->client->getCrawler()->filter('textarea[name="doc_page[body]"]')->text());
    }
}
