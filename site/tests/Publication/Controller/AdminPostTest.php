<?php

declare(strict_types=1);

namespace App\Tests\Publication\Controller;

use App\Publication\Entity\Post;
use App\Publication\Query\PostEditData\PostEditDataQuery;
use App\Publication\ValueObject\PostStatus;
use App\Tests\Publication\Builder\PostBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AdminPostTest extends WebTestCase
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
        yield 'список' => ['GET', '/admin/posts'];
        yield 'создание' => ['GET', '/admin/posts/new'];
        yield 'редактирование' => ['GET', '/admin/posts/1/edit'];
        yield 'предпросмотр' => ['GET', '/admin/posts/1/preview'];
        yield 'публикация' => ['POST', '/admin/posts/1/publish'];
    }

    #[DataProvider('protectedRoutes')]
    public function testGuestIsRedirectedToLogin(string $method, string $path): void
    {
        $this->client->request($method, $path);

        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateDraftAndSeeItInList(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/posts/new');
        $this->client->submitForm('Создать черновик', [
            'post[title]' => 'Как считать ДДС',
            'post[body]' => "## Зачем\n\nЧтобы видеть деньги.",
        ]);

        $post = $this->findPost('kak-schitat-dds');
        self::assertResponseRedirects('/admin/posts/'.$post->id().'/edit');
        self::assertSame(PostStatus::DRAFT, $post->status());

        $this->client->request('GET', '/admin/posts');
        self::assertSelectorTextContains('table', 'Как считать ДДС');
        self::assertSelectorTextContains('table', 'Черновик');
    }

    public function testInvalidFormIsRejectedWith422(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/posts/new');
        $this->client->submitForm('Создать черновик', [
            'post[title]' => '',
            'post[slug]' => 'Не латиница',
            'post[body]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form.panel', 'Только латиница в нижнем регистре');
    }

    public function testTakenSlugIsShownAsFieldError(): void
    {
        $this->persist(PostBuilder::aPost()->withSlug('taken')->build());
        $this->logIn();
        $this->client->request('GET', '/admin/posts/new');
        $this->client->submitForm('Создать черновик', [
            'post[title]' => 'Другая',
            'post[slug]' => 'taken',
            'post[body]' => 'Текст',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form.panel', 'Этот адрес уже занят');
    }

    public function testStatusLifecycleThroughButtons(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('lifecycle')->build());
        $this->logIn();

        foreach ([['Опубликовать', 'published'], ['Снять с публикации', 'draft'], ['В архив', 'archived'], ['Вернуть в черновики', 'draft']] as [$button, $status]) {
            $this->client->request('GET', '/admin/posts/'.$id.'/edit');
            $this->client->submitForm($button);

            self::assertResponseRedirects('/admin/posts/'.$id.'/edit');
            self::assertSame($status, $this->findPost('lifecycle')->status()->value, $button);
        }
    }

    public function testStatusChangeFromListReturnsToList(): void
    {
        $this->persist(PostBuilder::aPost()->withSlug('from-list')->build());
        $this->logIn();
        $this->client->request('GET', '/admin/posts');
        $this->client->submitForm('Опубликовать');

        self::assertResponseRedirects('/admin/posts');
        self::assertSame(PostStatus::PUBLISHED, $this->findPost('from-list')->status());
    }

    public function testStatusChangeOfUnknownPostIsNotFound(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('token-source')->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/posts/999999/publish', ['_token' => $token]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testDraftSlugCanBeChangedAndTakenSlugIsRejected(): void
    {
        $this->persist(PostBuilder::aPost()->withSlug('taken')->build());
        $id = $this->persist(PostBuilder::aPost()->withSlug('draft-slug')->build());
        $this->logIn();

        $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $this->client->submitForm('Сохранить', ['post[slug]' => 'taken']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form.panel', 'Этот адрес уже занят');

        $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $this->client->submitForm('Сохранить', ['post[slug]' => 'new-slug']);
        self::assertResponseRedirects('/admin/posts/'.$id.'/edit');
        self::assertSame($id, $this->findPost('new-slug')->id());
    }

    public function testEditWithoutVersionIsRejected(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('no-version')->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $values = $crawler->selectButton('Сохранить')->form()->getPhpValues();
        unset($values['post']['version']);

        $this->client->request('POST', '/admin/posts/'.$id.'/edit', $values);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-errors', 'Форма устарела');
    }

    public function testListFilterAndSortSurvivePagination(): void
    {
        for ($i = 1; $i <= 45; ++$i) {
            $this->persist(PostBuilder::aPost()->withSlug('p-'.$i)->published()->build());
        }
        $this->logIn();

        $crawler = $this->client->request('GET', '/admin/posts?status=published&sort=title&page=2');

        self::assertResponseIsSuccessful();
        self::assertCount(20, $crawler->filter('tbody tr'));
        $next = $crawler->filter('.pagination a[href*="page=3"]')->attr('href');
        self::assertStringContainsString('status=published', (string) $next);
        self::assertStringContainsString('sort=title', (string) $next);
    }

    public function testStatusChangeWithoutCsrfTokenIsForbidden(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('csrf')->build());
        $this->logIn();

        $this->client->request('POST', '/admin/posts/'.$id.'/publish', ['_token' => 'forged']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(PostStatus::DRAFT, $this->findPost('csrf')->status());
    }

    public function testInvalidTransitionIsConflict(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('archived')->archived()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/posts/'.$id.'/publish', ['_token' => $token]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testPublishedPostSlugCannotBeChanged(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('locked')->published()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');

        self::assertSelectorExists('input[name="post[slug]"][disabled]');

        // Даже подделанное поле не меняет адрес: disabled-поле форма игнорирует.
        $form = $crawler->selectButton('Сохранить')->form();
        $values = $form->getPhpValues();
        $values['post']['slug'] = 'hacked';
        $values['post']['title'] = 'Новый заголовок';
        $this->client->request('POST', '/admin/posts/'.$id.'/edit', $values);

        self::assertResponseRedirects('/admin/posts/'.$id.'/edit');
        $saved = self::getContainer()->get(PostEditDataQuery::class)->get($id);
        self::assertSame('Новый заголовок', $saved->title);
        self::assertSame('locked', $saved->slug);
    }

    public function testConcurrentEditShowsFormError(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('concurrent')->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $staleForm = $crawler->selectButton('Сохранить')->form();

        $this->client->submitForm('Сохранить', ['post[title]' => 'Первое сохранение']);
        $this->client->submit($staleForm, ['post[title]' => 'Второе окно']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-errors', 'Статью изменили в другом окне');
    }

    public function testUnknownPostIsNotFound(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/posts/999999/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testListRejectsBadQueryAndOutOfRangePage(): void
    {
        $this->logIn();

        $this->client->request('GET', '/admin/posts?status=deleted');
        self::assertResponseStatusCodeSame(400);

        $this->client->request('GET', '/admin/posts?sort=id');
        self::assertResponseStatusCodeSame(400);

        $this->client->request('GET', '/admin/posts?page=0');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/posts?page=5');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPreviewRendersMarkdownWithoutRawHtml(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('preview')->withBody("## Раздел\n\n<script>alert(1)</script>\n\n[ссылка](javascript:alert(1))")->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/preview');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSelectorTextContains('article h2', 'Раздел');
        self::assertCount(0, $crawler->filter('article script'));
        self::assertCount(0, $crawler->filter('article a[href^="javascript"]'));
    }

    private function logIn(): void
    {
        // Пользователь из того же провайдера: иначе ContextListener сравнит пароль
        // с хешем из env, сочтёт пользователя изменённым и разлогинит.
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);

        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }

    private function persist(Post $post): int
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($post);
        $entityManager->flush();

        return (int) $post->id();
    }

    private function findPost(string $slug): Post
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug])
            ?? self::fail('Post '.$slug.' not found');
    }
}
