<?php

declare(strict_types=1);

namespace App\Tests\Publication\Controller;

use App\Publication\Entity\Post;
use App\Publication\Query\PostEditData\PostEditDataQuery;
use App\Publication\ValueObject\PostStatus;
use App\Tests\Publication\Builder\PostBuilder;
use App\Tests\Publication\PostTableCleaner;
use App\Tests\Publication\TestPng;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AdminPostTest extends WebTestCase
{
    use PostTableCleaner;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::clearPosts(self::getContainer()->get(EntityManagerInterface::class));
        foreach ($this->storedCovers() as $file) {
            unlink($this->coverDir().'/'.$file);
        }
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
        self::assertSelectorTextContains('form[data-admin-post-form]', 'Только латиница в нижнем регистре');
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
        self::assertSelectorTextContains('form[data-admin-post-form]', 'Этот адрес уже занят');
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

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function rowMenus(): iterable
    {
        yield 'черновик' => ['draft', ['Редактировать', 'Опубликовать', 'В архив']];
        yield 'опубликована' => ['published', ['Редактировать', 'Снять с публикации', 'В архив']];
        yield 'в архиве' => ['archived', ['Редактировать', 'Вернуть в черновики']];
    }

    /**
     * @param list<string> $items
     */
    #[DataProvider('rowMenus')]
    public function testListRowActionsLiveInDropdownMenu(string $status, array $items): void
    {
        $builder = PostBuilder::aPost()->withTitle('Меню строки')->withSlug('row-menu');
        $builder = match ($status) {
            'published' => $builder->published(),
            'archived' => $builder->archived(),
            default => $builder,
        };
        $id = $this->persist($builder->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts');

        $cell = $crawler->filter('tbody tr td')->last();
        $toggle = $cell->filter('details > summary[data-admin-menu-toggle]');
        self::assertCount(1, $toggle);
        self::assertSame('Действия: Меню строки', $toggle->attr('aria-label'));
        self::assertSame('post-actions-'.$id, $toggle->attr('aria-controls'));

        // Все действия находятся в раскрывающемся меню рядом с заголовком.
        self::assertCount(1, $cell->filter('details'));
        self::assertCount(0, $cell->filter('button, a')->reduce(static fn ($node) => null === $node->closest('[data-admin-row-menu]')));
        $menu = $cell->filter('details > #post-actions-'.$id);
        self::assertSame($items, $menu->filter('a, button')->each(static fn ($node) => trim($node->text())));
        self::assertSame('/admin/posts/'.$id.'/edit', $menu->filter('a')->attr('href'));
        self::assertCount(\count($items) - 1, $menu->filter('form[method="post"] input[name="_token"]'));
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
        self::assertSelectorTextContains('form[data-admin-post-form]', 'Этот адрес уже занят');

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
        self::assertSelectorTextContains('[data-admin-form-errors]', 'Форма устарела');
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
        self::assertSelectorTextContains('[data-admin-form-errors]', 'Статью изменили в другом окне');
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

    public function testFormIsSplitIntoTabs(): void
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/new');

        self::assertSame(
            ['Основное', 'Текст', 'Обложка', 'SEO'],
            $crawler->filter('[role="tablist"][data-admin-tabs] [role="tab"]')->each(static fn ($tab) => trim($tab->text())),
        );
        self::assertCount(4, $crawler->filter('form[data-admin-post-form] [role="tabpanel"]'));
        self::assertSelectorExists('#post-panel-cover input[type="file"][name="post[coverUpload]"][accept="image/png"]');
        self::assertSelectorExists('#post-panel-seo input[name="post[metaTitle]"]');
        self::assertSelectorExists('form[data-admin-post-form][enctype="multipart/form-data"]');
        self::assertSelectorExists('script[src^="/assets/website/admin-tabs.js?v="]');
    }

    public function testCreateDraftWithCover(): void
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/new');
        $form = $crawler->selectButton('Создать черновик')->form([
            'post[title]' => 'С обложкой',
            'post[body]' => 'Текст',
            'post[coverAlt]' => 'График выручки',
        ]);
        $this->fileField($form)->upload(TestPng::create(1200, 675));
        $this->client->submit($form);

        $cover = $this->findPost('s-oblozhkoy')->cover();
        self::assertNotNull($cover);
        self::assertSame('График выручки', $cover->alt);
        self::assertSame([$cover->file], $this->storedCovers());
    }

    public function testCoverCanBeReplacedAndRemoved(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('cover')->build());
        $this->logIn();

        $this->uploadCover($id, TestPng::create(1200, 675), 'Первая');
        self::assertResponseRedirects('/admin/posts/'.$id.'/edit');
        $first = $this->findPost('cover')->cover()?->file;
        self::assertSame([$first], $this->storedCovers());

        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        self::assertSelectorExists('#post-panel-cover img[data-admin-post-cover][src="/uploads/posts/'.$first.'"][alt="Первая"]');

        // Замена: новый файл в базе, прежний удалён с диска.
        $this->uploadCover($id, TestPng::create(1200, 675), 'Вторая');
        $second = $this->findPost('cover')->cover();
        self::assertNotNull($second);
        self::assertNotSame($first, $second->file);
        self::assertSame('Вторая', $second->alt);
        self::assertSame([$second->file], $this->storedCovers());

        // Alt меняется без нового файла.
        $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $this->client->submitForm('Сохранить', ['post[coverAlt]' => 'Новое описание']);
        self::assertSame('Новое описание', $this->findPost('cover')->cover()?->alt);

        $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $this->client->submitForm('Сохранить', ['post[removeCover]' => '1']);
        self::assertResponseRedirects('/admin/posts/'.$id.'/edit');
        self::assertNull($this->findPost('cover')->cover());
        self::assertSame([], $this->storedCovers());
    }

    /**
     * @return iterable<string, array{\Closure(): string, string}>
     */
    public static function badCovers(): iterable
    {
        yield 'пропорция OG 1200×630' => [static fn (): string => TestPng::create(1200, 630), 'высота файла 630 px'];
        yield 'вдвое больше' => [static fn (): string => TestPng::create(2400, 1350), 'ширина файла 2400 px'];
        yield 'не картинка' => [static fn (): string => TestPng::notAnImage(), 'Нужен файл PNG'];
    }

    /**
     * @param \Closure(): string $file
     */
    #[DataProvider('badCovers')]
    public function testBadCoverIsRejectedOnCoverTab(\Closure $file, string $error): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('bad-cover')->build());
        $this->logIn();

        $this->uploadCover($id, $file(), 'Описание');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#post-panel-cover', $error);
        self::assertSelectorExists('#post-tab-cover[data-admin-tab-invalid]');
        self::assertSelectorNotExists('#post-tab-main[data-admin-tab-invalid]');
        self::assertNull($this->findPost('bad-cover')->cover());
        self::assertSame([], $this->storedCovers());
    }

    public function testCoverWithoutAltIsRejected(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('no-alt')->build());
        $this->logIn();

        $this->uploadCover($id, TestPng::create(1200, 675), '');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#post-panel-cover', 'Опишите, что на обложке');
        self::assertNull($this->findPost('no-alt')->cover());
        self::assertSame([], $this->storedCovers());
    }

    public function testFailedSaveRemovesUploadedFileAndKeepsOldCover(): void
    {
        $this->persist(PostBuilder::aPost()->withSlug('taken')->build());
        $id = $this->persist(PostBuilder::aPost()->withSlug('keeps-cover')->withCover()->build());
        $this->logIn();

        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $form = $crawler->selectButton('Сохранить')->form(['post[slug]' => 'taken']);
        $this->fileField($form)->upload(TestPng::create(1200, 675));
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(str_repeat('a', 32).'.png', $this->findPost('keeps-cover')->cover()?->file);
        self::assertSame([], $this->storedCovers());
    }

    public function testConcurrentEditWithUploadKeepsOldCoverAndRemovesNewFile(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('concurrent-cover')->withCover()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $staleForm = $crawler->selectButton('Сохранить')->form();

        $this->client->submitForm('Сохранить', ['post[title]' => 'Первое сохранение']);
        $this->fileField($staleForm)->upload(TestPng::create(1200, 675));
        $this->client->submit($staleForm);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(str_repeat('a', 32).'.png', $this->findPost('concurrent-cover')->cover()?->file);
        self::assertSame([], $this->storedCovers());
    }

    public function testNewFileWinsOverRemoveFlag(): void
    {
        $id = $this->persist(PostBuilder::aPost()->withSlug('upload-wins')->withCover()->build());
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $form = $crawler->selectButton('Сохранить')->form(['post[removeCover]' => '1', 'post[coverAlt]' => 'Новая']);
        $this->fileField($form)->upload(TestPng::create(1200, 675));
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/posts/'.$id.'/edit');
        $cover = $this->findPost('upload-wins')->cover();
        self::assertNotNull($cover);
        self::assertSame([$cover->file], $this->storedCovers());
    }

    private function uploadCover(int $id, string $path, string $alt): void
    {
        $crawler = $this->client->request('GET', '/admin/posts/'.$id.'/edit');
        $form = $crawler->selectButton('Сохранить')->form(['post[coverAlt]' => $alt]);
        $this->fileField($form)->upload($path);
        $this->client->submit($form);
    }

    private function coverDir(): string
    {
        return (string) self::getContainer()->getParameter('vf.post_cover.dir');
    }

    private function fileField(Form $form): FileFormField
    {
        $field = $form['post[coverUpload]'];
        self::assertInstanceOf(FileFormField::class, $field);

        return $field;
    }

    /**
     * @return list<string>
     */
    private function storedCovers(): array
    {
        return is_dir($this->coverDir()) ? array_values(array_diff(scandir($this->coverDir()) ?: [], ['.', '..'])) : [];
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
