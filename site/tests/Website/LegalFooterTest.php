<?php

declare(strict_types=1);

namespace App\Tests\Website;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * LEGAL_RULES.md, раздел 9: на сайте нет вымышленных контактов, пунктов «демо» и ссылок
 * на заглушки юридических документов; политика и согласие доступны с каждой страницы.
 */
final class LegalFooterTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function publicPages(): iterable
    {
        foreach (['/', '/about', '/services', '/cases', '/partners', '/privacy', '/consent', '/gazeta'] as $path) {
            yield $path => [$path];
        }
    }

    #[DataProvider('publicPages')]
    public function testFooterHasOnlyRealContactsAndLegalDocuments(string $path): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('(демо)', $html);
        self::assertStringNotContainsString('555-12-34', $html);
        self::assertSelectorNotExists('a[href="/offer"]');

        $footer = $crawler->filter('footer');
        self::assertCount(1, $footer->filter('a[href="/privacy"]'));
        self::assertCount(1, $footer->filter('a[href="/consent"]'));
        self::assertCount(1, $footer->filter('a[href="mailto:hello@vashfindir.ru"]'));
        // Пункты меню футера ведут на существующие страницы, а не на «страница пока не создана».
        foreach ($footer->filter('nav[aria-label="Основное меню футера"] a')->extract(['href']) as $href) {
            if (str_starts_with($href, '/')) {
                $client->request('GET', $href);
                self::assertResponseIsSuccessful($href);
            }
        }
    }
}
