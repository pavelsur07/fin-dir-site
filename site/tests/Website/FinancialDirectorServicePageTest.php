<?php

declare(strict_types=1);

namespace App\Tests\Website;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FinancialDirectorServicePageTest extends WebTestCase
{
    private const string PATH = '/services/finansovyy-direktor-na-autsorsinge';

    public function testPageHasSeoBasicsAndSectionsInOrder(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertFalse($client->getResponse()->headers->has('Set-Cookie'));
        self::assertSelectorCount(1, 'h1');
        self::assertSelectorTextContains('h1', 'Видите реальную прибыль и знаете, как расти к дивидендам');
        self::assertPageTitleSame('Финансовый директор на аутсорсинге — Ваш Финдир');
        self::assertSelectorExists('meta[name="description"]');
        self::assertSelectorExists('link[rel="canonical"][href="https://vashfindir.ru'.self::PATH.'"]');
        self::assertSelectorExists('meta[name="robots"][content="index, follow"]');
        self::assertSelectorExists('[data-vf-desktop-navigation] a[href="/services"][aria-current="page"]');

        $ids = $crawler->filter('main > section, main > div > section')->each(static fn ($node): ?string => $node->attr('id') ?? $node->attr('aria-labelledby'));
        self::assertSame(
            ['service-hero-title', 'service-problems', 'service-about', 'service-compare', 'service-duties', 'service-process', 'service-reports', 'service-saas', 'service-price', 'service-faq-title', 'lead-form'],
            $ids,
        );
    }

    public function testPlaceholderSectionsAreHiddenAndNoBracketedStubsAreVisible(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        self::assertSelectorNotExists('#service-cases');
        self::assertSelectorNotExists('#service-founder');
        $text = (string) $client->getCrawler()->filter('main')->text();
        // Заглушки вида «[Название кейса]»; квадратные скобки JSON-LD сюда не относятся.
        self::assertDoesNotMatchRegularExpression('/\[[А-Яа-я]/u', $text);
        self::assertStringNotContainsString('28 лет', $text);
    }

    public function testContentFollowsTheLayoutSpecification(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        self::assertSelectorExists('meta[name="description"][content="Финдир на аутсорсе для бизнеса с оборотом от 2 млн ₽ в месяц: управленческий учёт, платёжный календарь, ОПиУ и план роста"]');
        self::assertSelectorTextContains('#service-reports-title', 'Три отчёта, которые показывают реальное состояние бизнеса');
        self::assertSelectorTextContains('#service-reports', 'Управленческий баланс');
        self::assertSelectorTextContains('#service-reports', 'Показывает устойчивость бизнеса');
        self::assertSelectorCount(7, '#service-about ul li');
        self::assertSelectorCount(4, '#service-saas tbody tr');
        self::assertSelectorTextContains('#service-saas', 'Маржа по направлениям, условные данные');
        self::assertSelectorTextContains('#service-duties-title', 'Что делает финансовый директор Ваш Финдир');
        self::assertSelectorCount(7, '#service-duties li');
        self::assertSelectorTextContains('#service-duties', 'Выстраивает правила дивидендов');
        self::assertSelectorTextContains('#service-compare-title', 'Штатный финансист или финдиректор на аутсорсе');
        self::assertSelectorTextContains('#service-process', 'обычно 5–7 числа следующего месяца');
        self::assertSelectorTextContains('section[aria-labelledby="service-hero-title"]', 'Пример интерфейса, цифры условные.');
    }

    public function testBreadcrumbsAndStructuredData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::PATH);

        self::assertSelectorExists('nav[aria-label="Хлебные крошки"] a[href="/services"]');

        $types = [];
        foreach ($crawler->filter('script[type="application/ld+json"]') as $node) {
            $documents = json_decode((string) $node->textContent, true);
            self::assertIsArray($documents);
            // BreadcrumbList -- один документ, остальные блоки -- массивы документов.
            foreach (array_is_list($documents) ? $documents : [$documents] as $document) {
                $types[] = $document['@type'] ?? null;
            }
        }
        foreach (['Service', 'FAQPage', 'BreadcrumbList'] as $expected) {
            self::assertContains($expected, $types);
        }
    }

    public function testFaqHasEightClosedQuestionsWithoutPrices(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        self::assertSelectorCount(8, 'section[aria-labelledby="service-faq-title"] details');
        self::assertSelectorNotExists('section[aria-labelledby="service-faq-title"] details[open]');
        // Цен на странице нет: знак рубля встречается только во фразе про порог оборота «2 млн ₽»
        // и в вариантах ответа формы («оборот в месяц»).
        $content = (string) $client->getResponse()->getContent();
        $content = (string) preg_replace('/<form\b.*?<\/form>/su', '', $content);
        $withoutThreshold = (string) preg_replace('/2\x{00A0}млн\x{00A0}₽/u', '', $content);
        self::assertStringNotContainsString('₽', $withoutThreshold);
    }

    public function testLeadFormAndFounderArePresent(): void
    {
        $client = static::createClient();
        $client->request('GET', self::PATH);

        self::assertSelectorCount(1, 'main form[data-vf-lead-form][action="/lead"][data-vf-form-key="excursion"]');
        self::assertSelectorExists('main form input[name="_token"]');
        self::assertSelectorExists('main form input[name="agreement"][required]');
        self::assertSelectorTextContains('#lead-form', 'Разберём вашу финансовую ситуацию бесплатно');
        self::assertSelectorExists('#lead-form input[name="contact_type"][value="telegram"]');
        self::assertSelectorExists('#lead-form input[data-vf-contact-input][required]');
        self::assertSelectorTextContains('#lead-form button[type="submit"]', 'Бесплатная консультация');
        self::assertSelectorTextContains('#service-process', 'обычно 5–7 числа');
    }

    public function testPageIsInSitemapAndTrailingSlashRedirects(): void
    {
        $client = static::createClient();

        $client->request('GET', '/sitemap.xml');
        self::assertStringContainsString('https://vashfindir.ru'.self::PATH.'</loc>', (string) $client->getResponse()->getContent());

        $client->request('GET', self::PATH.'/');
        self::assertResponseRedirects(self::PATH, 301);
    }
}
