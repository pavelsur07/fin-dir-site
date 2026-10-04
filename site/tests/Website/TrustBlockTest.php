<?php

declare(strict_types=1);

namespace App\Tests\Website;

use App\Website\Twig\CompanyAgeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TrustBlockTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function pages(): iterable
    {
        yield 'главная' => ['/'];
        yield 'о компании' => ['/about'];
        yield 'кейсы' => ['/cases'];
        yield 'услуга финдира' => ['/services/finansovyy-direktor-na-autsorsinge'];
    }

    #[DataProvider('pages')]
    public function testTrustBlockHasThreeCardsFromOneComponent(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '[data-vf-component="trust-cards"]', $path);
        self::assertSelectorCount(3, '[data-vf-component="trust-cards"] li', $path);

        $years = (int) date('Y') - 2015;
        $text = (string) $client->getCrawler()->filter('[data-vf-component="trust-cards"]')->text();
        self::assertStringContainsString($years.' '.CompanyAgeExtension::unit($years).' на рынке', $text, $path);
        self::assertStringContainsString('230+ компаний прошли через нас', $text, $path);
        self::assertStringContainsString('до 8 числа закрываем период каждый месяц, а не раз в квартал', $text, $path);
    }

    #[DataProvider('pages')]
    public function testAwardIsNotMentionedAnywhere(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        $html = (string) $client->getResponse()->getContent();
        foreach (['Премия 2015', '1С:БО', 'Лучший стартап'] as $needle) {
            self::assertStringNotContainsString($needle, $html, $path.': '.$needle);
        }
    }
}
