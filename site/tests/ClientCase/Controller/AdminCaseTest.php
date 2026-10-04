<?php

declare(strict_types=1);

namespace App\Tests\ClientCase\Controller;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\ValueObject\CaseStatus;
use App\Tests\ClientCase\Builder\ClientCaseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AdminCaseTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Миграция кладёт в site_test демо-кейсы; DELETE идёт внутри транзакции DAMA и откатывается.
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->executeStatement('DELETE FROM client_case');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function protectedRoutes(): iterable
    {
        yield 'список' => ['GET', '/admin/cases'];
        yield 'создание' => ['GET', '/admin/cases/new'];
        yield 'создание POST' => ['POST', '/admin/cases/new'];
        yield 'редактирование' => ['GET', '/admin/cases/1/edit'];
        yield 'публикация' => ['POST', '/admin/cases/1/publish'];
    }

    #[DataProvider('protectedRoutes')]
    public function testGuestIsRedirectedToLogin(string $method, string $path): void
    {
        $this->client->request($method, $path);

        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateDraftAndSeeItInListAndMenu(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/cases/new');
        $this->client->submitForm('Создать черновик', $this->validForm(['client_case[slug]' => 'novyj-kejs', 'client_case[title]' => 'Новый кейс']));

        $case = $this->findCase('novyj-kejs');
        self::assertResponseRedirects('/admin/cases/'.$case->id().'/edit');
        self::assertSame(CaseStatus::DRAFT, $case->status());

        $this->client->request('GET', '/admin/cases');
        self::assertSelectorTextContains('table', 'Новый кейс');
        self::assertSelectorTextContains('table', 'Черновик');
        self::assertSelectorExists('[data-admin-menu] a[href="/admin/cases"][aria-current="page"]');

        // Черновик не виден на сайте.
        $this->client->request('GET', '/cases');
        self::assertSelectorTextNotContains('main', 'Новый кейс');
        $this->client->request('GET', '/cases/novyj-kejs');
        self::assertResponseStatusCodeSame(404);
    }

    public function testInvalidFormIsRejectedWith422(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/cases/new');
        $this->client->submitForm('Создать черновик', ['client_case[title]' => '', 'client_case[slug]' => 'Bad Slug']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('form[data-admin-case-form] .form-error-message, form[data-admin-case-form] ul li');
        self::assertNull($this->findCaseOrNull('Bad Slug'));
    }

    public function testDuplicateSlugIsShownAsFieldError(): void
    {
        $this->persist(ClientCaseBuilder::aCase()->withSlug('zanyato')->build());
        $this->logIn();
        $this->client->request('GET', '/admin/cases/new');
        $this->client->submitForm('Создать черновик', $this->validForm(['client_case[slug]' => 'zanyato']));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[data-admin-case-form]', 'Этот адрес уже занят другим кейсом.');
    }

    public function testMalformedMetricIsRejected(): void
    {
        $this->logIn();
        $this->client->request('GET', '/admin/cases/new');
        $this->client->submitForm('Создать черновик', $this->validForm(['client_case[slug]' => 'metrika', 'client_case[metrics]' => 'без разделителя']));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[data-admin-case-form]', 'значение | подпись');
        self::assertNull($this->findCaseOrNull('metrika'));
    }

    public function testEditSavesContentAndParsesLists(): void
    {
        $id = $this->persist(ClientCaseBuilder::aCase()->withSlug('redaktiruem')->build());
        $this->logIn();
        $this->client->request('GET', '/admin/cases/'.$id.'/edit');
        $this->client->submitForm('Сохранить', [
            'client_case[title]' => 'Обновлённый заголовок',
            'client_case[tags]' => 'ДДС, ABC-анализ,  ',
            'client_case[steps]' => "Первый шаг\n\nВторой шаг",
            'client_case[metrics]' => '5 % | маржа',
        ]);

        self::assertResponseRedirects('/admin/cases/'.$id.'/edit');
        $this->client->request('GET', '/admin/cases/'.$id.'/edit');
        self::assertSelectorTextContains('h1', 'Обновлённый заголовок');
        self::assertSame('ДДС, ABC-анализ', $this->client->getCrawler()->filter('input[name="client_case[tags]"]')->attr('value'));
        self::assertSame("Первый шаг\nВторой шаг", trim((string) $this->client->getCrawler()->filter('textarea[name="client_case[steps]"]')->getNode(0)?->textContent));
        self::assertSame('5 % | маржа', trim((string) $this->client->getCrawler()->filter('textarea[name="client_case[metrics]"]')->getNode(0)?->textContent));
    }

    public function testPublishedCaseAppearsOnSiteAndCanBeUnpublished(): void
    {
        $id = $this->persist(ClientCaseBuilder::aCase()->withSlug('zhivoj')->withTitle('Живой кейс')->build());
        $this->logIn();

        $this->postStatus($id, 'publish');
        self::assertResponseRedirects('/admin/cases/'.$id.'/edit');
        $this->client->request('GET', '/cases/zhivoj');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/cases');
        self::assertSelectorTextContains('main', 'Живой кейс');

        $this->postStatus($id, 'unpublish', 'list');
        self::assertResponseRedirects('/admin/cases');
        $this->client->request('GET', '/cases/zhivoj');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPublishedCaseSlugCannotBeChanged(): void
    {
        $id = $this->persist(ClientCaseBuilder::aCase()->withSlug('opublikovan')->published()->build());
        $this->logIn();

        $crawler = $this->client->request('GET', '/admin/cases/'.$id.'/edit');
        self::assertSame('disabled', $crawler->filter('input[name="client_case[slug]"]')->attr('disabled'));

        // Подделанный POST с новым slug не меняет адрес: disabled-поле игнорируется формой.
        $this->client->submitForm('Сохранить', ['client_case[title]' => 'Другой заголовок']);
        self::assertSame('opublikovan', $this->findCase('opublikovan')->slug());
    }

    public function testOnlyOneCaseStaysFeatured(): void
    {
        $first = $this->persist(ClientCaseBuilder::aCase()->withSlug('pervyj')->featured()->published()->build());
        $second = $this->persist(ClientCaseBuilder::aCase()->withSlug('vtoroj')->published()->build());
        $this->logIn();

        $this->client->request('GET', '/admin/cases/'.$second.'/edit');
        $this->client->submitForm('Сохранить', [
            'client_case[task]' => 'Задача второго',
            'client_case[steps]' => 'Шаг',
            'client_case[metrics]' => '1 | метрика',
            'client_case[featured]' => true,
        ]);

        self::assertResponseRedirects('/admin/cases/'.$second.'/edit');
        self::assertTrue($this->findCase('vtoroj')->isFeatured());
        self::assertFalse($this->findCase('pervyj')->isFeatured());
        self::assertNotSame($first, $second);
    }

    public function testFeaturedWithoutDescriptionIsRejected(): void
    {
        $id = $this->persist(ClientCaseBuilder::aCase()->withSlug('bez-opisaniya')->build());
        $this->logIn();
        $this->client->request('GET', '/admin/cases/'.$id.'/edit');
        $this->client->submitForm('Сохранить', ['client_case[featured]' => true]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[data-admin-case-form]', 'Главному кейсу нужны задача, шаги и метрики.');
        self::assertFalse($this->findCase('bez-opisaniya')->isFeatured());
    }

    public function testStatusChangeWithoutCsrfTokenIsForbidden(): void
    {
        $id = $this->persist(ClientCaseBuilder::aCase()->withSlug('csrf')->build());
        $this->logIn();

        $this->client->request('POST', '/admin/cases/'.$id.'/publish', ['_token' => 'forged']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(CaseStatus::DRAFT, $this->findCase('csrf')->status());
    }

    public function testInvalidTransitionIsConflict(): void
    {
        $id = $this->persist(ClientCaseBuilder::aCase()->withSlug('v-arhive')->published()->archived()->build());
        $this->logIn();

        $this->postStatus($id, 'unpublish');

        self::assertResponseStatusCodeSame(409);
    }

    public function testUnknownCaseIsNotFound(): void
    {
        $this->logIn();

        $this->client->request('GET', '/admin/cases/999999/edit');
        self::assertResponseStatusCodeSame(404);

        $existing = $this->persist(ClientCaseBuilder::aCase()->withSlug('source')->build());
        $this->postStatus(999999, 'publish', null, $existing);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownStatusFilterIsBadRequest(): void
    {
        $this->logIn();

        $this->client->request('GET', '/admin/cases?status=deleted');

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validForm(array $overrides = []): array
    {
        return array_merge([
            'client_case[title]' => 'Тестовый кейс',
            'client_case[slug]' => 'testovyj-kejs',
            'client_case[industry]' => 'torgovlya',
            'client_case[problem]' => 'Была проблема.',
            'client_case[resultValue]' => '1 000 ₽',
            'client_case[resultLabel]' => 'результат',
        ], $overrides);
    }

    /**
     * @param int|null $tokenFrom кейс, со страницы которого берётся CSRF-токен; по умолчанию сам кейс
     */
    private function postStatus(int $id, string $action, ?string $back = null, ?int $tokenFrom = null): void
    {
        // Токен берём так же, как кнопка в админке: со страницы редактирования существующего кейса.
        $crawler = $this->client->request('GET', '/admin/cases/'.($tokenFrom ?? $id).'/edit');
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/cases/'.$id.'/'.$action, array_filter(['_token' => $token, 'back' => $back]));
    }

    private function logIn(): void
    {
        // Пользователь из того же провайдера: иначе ContextListener сравнит пароль
        // с хешем из env, сочтёт пользователя изменённым и разлогинит.
        $provider = self::getContainer()->get('security.user.provider.concrete.admin_user');
        self::assertInstanceOf(UserProviderInterface::class, $provider);

        $this->client->loginUser($provider->loadUserByIdentifier('admin'), 'admin');
    }

    private function persist(ClientCase $case): int
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($case);
        $entityManager->flush();

        return (int) $case->id();
    }

    private function findCase(string $slug): ClientCase
    {
        return $this->findCaseOrNull($slug) ?? self::fail(sprintf('Case "%s" not found.', $slug));
    }

    private function findCaseOrNull(string $slug): ?ClientCase
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->getRepository(ClientCase::class)->findOneBy(['slug' => $slug]);
    }
}
