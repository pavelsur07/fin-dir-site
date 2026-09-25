<?php

declare(strict_types=1);

namespace App\Tests\Lead\Functional;

use App\Lead\Entity\Lead;
use App\Lead\ValueObject\LeadStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class LeadSubmitTest extends WebTestCase
{
    private const array JSON_SAME_ORIGIN = ['HTTP_ORIGIN' => 'http://localhost', 'HTTP_ACCEPT' => 'application/json'];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHomePageHasRealLeadFormWithoutDemoNote(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[data-vf-lead-form][method="post"][action="/lead"] button[type="submit"]');
        self::assertSelectorExists('form[data-vf-lead-form] input[name="form"][value="consultation"]');
        self::assertSelectorExists('form[data-vf-lead-form] input[name="website"][tabindex="-1"]');
        // Вебвизор Метрики не записывает ввод персональных данных.
        foreach (['input[name="name"]', 'input[name="contact"]', 'textarea[name="task"]'] as $field) {
            self::assertSelectorExists('form[data-vf-lead-form] '.$field.'.ym-disable-keys');
        }
        self::assertStringNotContainsString('демо-режиме', $crawler->filter('main')->text());
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
    }

    public function testValidSubmissionIsStored(): void
    {
        $this->post($this->fields());

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['ok' => true], json_decode((string) $this->client->getResponse()->getContent(), true));
        $lead = $this->onlyLead();
        self::assertSame(LeadStatus::NEW, $lead->status());
        self::assertSame(['utm_source' => 'telegram'], $lead->utm());
        // От чужого сайта хранится только origin: path и query могут содержать ПД.
        self::assertSame('https://yandex.ru', (new \ReflectionProperty($lead, 'referrer'))->getValue($lead));
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
    }

    public function testAttributionAndClientIdAreStored(): void
    {
        $this->post($this->fields([
            'attribution' => json_encode(['v' => 1, 'first' => ['channel' => 'cpc', 'source' => 'yandex', 'click' => ['yclid' => '42']], 'visits' => 2], \JSON_THROW_ON_ERROR),
            'ym_client_id' => '1758600000123456789',
        ]));

        self::assertResponseStatusCodeSame(201);
        $lead = $this->onlyLead();
        self::assertSame(['channel' => 'cpc', 'source' => 'yandex', 'click' => ['yclid' => '42']], $lead->attribution()?->first);
        self::assertSame('1758600000123456789', $lead->ymClientId());
    }

    public function testGarbageAttributionAndClientIdDoNotBlockLead(): void
    {
        $this->post($this->fields(['attribution' => '{"first":', 'ym_client_id' => 'abc<script>']));

        self::assertResponseStatusCodeSame(201);
        $lead = $this->onlyLead();
        self::assertNull($lead->attribution());
        self::assertNull($lead->ymClientId());
    }

    public function testNumericPostKeysDoNotBreakEndpoint(): void
    {
        $this->post($this->fields() + [0 => 'x', 1 => 'y']);

        self::assertResponseStatusCodeSame(201);
    }

    public function testAbsolutePageUrlIsRejected(): void
    {
        $this->post($this->fields(['page_url' => 'https://evil.example/phish']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->leadCount());
    }

    public function testQualificationFormStoresAnswers(): void
    {
        $this->post($this->fields([
            'form' => 'diagnostics',
            'answers' => ['channel' => 'wildberries', 'turnover' => 'over_20m', 'need' => 'outsourced_cfo'],
        ]));

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['Wildberries', 'больше 20 млн ₽', 'Финдиректор на аутсорсе'], array_column($this->onlyLead()->answers(), 'answerLabel'));
    }

    public function testInvalidSubmissionReturnsFieldErrors(): void
    {
        $this->post($this->fields([
            'form' => 'diagnostics',
            'name' => '',
            'agreement' => '',
            'answers' => ['channel' => 'aliexpress'],
        ]));

        self::assertResponseStatusCodeSame(422);
        $errors = json_decode((string) $this->client->getResponse()->getContent(), true)['errors'];
        self::assertSame('Укажите имя.', $errors['name']);
        self::assertArrayHasKey('agreement', $errors);
        self::assertSame('Недопустимый вариант.', $errors['answers[channel]']);
        self::assertArrayHasKey('answers[turnover]', $errors);
        self::assertSame(0, $this->leadCount());
    }

    public function testUnknownFormIsRejected(): void
    {
        $this->post($this->fields(['form' => 'unknown']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->leadCount());
    }

    public function testHoneypotIsAcceptedSilentlyAsSpam(): void
    {
        $this->post($this->fields(['website' => 'https://spam.example']));

        self::assertResponseStatusCodeSame(201);
        self::assertSame(LeadStatus::SPAM, $this->onlyLead()->status());
    }

    public function testForeignOriginIsForbidden(): void
    {
        $this->post($this->fields(), ['HTTP_ORIGIN' => 'https://evil.example', 'HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->leadCount());
    }

    public function testMissingOriginAndRefererIsForbidden(): void
    {
        $this->post($this->fields(), ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testRateLimitAfterFiveSubmissions(): void
    {
        // Счётчик исчерпывается до запроса: между запросами kernel сбрасывает
        // array-кеш лимитера (ResetInterface), поэтому пять POST подряд тут не помогут.
        $limiter = self::getContainer()->get('limiter.lead_submit');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $limiter);
        $limiter->create('127.0.0.1')->consume(5);

        $this->post($this->fields());

        self::assertResponseStatusCodeSame(429);
        self::assertSame(0, $this->leadCount());
    }

    public function testInvalidAttemptsDoNotSpendLimit(): void
    {
        $limiter = self::getContainer()->get('limiter.lead_submit');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $limiter);
        $limiter->create('127.0.0.1')->consume(5);

        // Ошибка ввода при исчерпанном лимите -- всё равно 422, а не 429:
        // исправляющего форму человека лимит не касается.
        $this->post($this->fields(['name' => '']));

        self::assertResponseStatusCodeSame(422);
    }

    public function testFormWithoutJavaScriptGetsResultPage(): void
    {
        $fields = $this->fields(['submission_id' => '', 'fill_ms' => '']);
        $this->post($fields, ['HTTP_REFERER' => 'http://localhost/']);

        self::assertResponseStatusCodeSame(201);
        self::assertSelectorTextContains('h1', 'Спасибо, заявка отправлена');
        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
        self::assertSame(1, $this->leadCount());
    }

    public function testResultPageLinksBackToLocalPage(): void
    {
        $this->post($this->fields(['page_url' => '/gazeta']), ['HTTP_REFERER' => 'http://localhost/']);

        self::assertSelectorExists('main a[href="/gazeta"]');
    }

    public function testBackslashPathCannotBecomeExternalBackLink(): void
    {
        // Браузер читает "/\evil.example" как "//evil.example" -- такой путь отклоняется.
        $this->post($this->fields(['page_url' => '/\\evil.example']), ['HTTP_REFERER' => 'http://localhost/']);

        self::assertResponseStatusCodeSame(422);
        self::assertStringNotContainsString('evil.example"', (string) $this->client->getResponse()->getContent());
        self::assertSelectorExists('main a[href="/"]');
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function fields(array $override = []): array
    {
        return array_replace([
            '_token' => 'csrf-token',
            'form' => 'consultation',
            'submission_id' => '11111111-1111-4111-8111-111111111111',
            'fill_ms' => '45000',
            'referrer' => 'https://yandex.ru/search/?text=финдир&uid=secret',
            'name' => 'Иван',
            'contact' => '+7 900 123-45-67',
            'task' => 'Нужен ДДС',
            'agreement' => 'on',
            'website' => '',
            'page_url' => '/',
            'utm_source' => 'telegram',
        ], $override);
    }

    /**
     * @param array<array-key, mixed> $fields
     * @param array<string, string>   $server
     */
    private function post(array $fields, array $server = self::JSON_SAME_ORIGIN): void
    {
        $this->client->request('POST', '/lead', $fields, server: $server);
    }

    private function leadCount(): int
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(Lead::class)->count([]);
    }

    private function onlyLead(): Lead
    {
        $leads = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Lead::class)->findAll();
        self::assertCount(1, $leads);

        return $leads[0];
    }
}
