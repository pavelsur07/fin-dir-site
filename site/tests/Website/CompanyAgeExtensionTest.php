<?php

declare(strict_types=1);

namespace App\Tests\Website;

use App\Website\Twig\CompanyAgeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompanyAgeExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function units(): iterable
    {
        foreach ([0, 5, 6, 9, 10, 11, 12, 13, 14, 15, 19, 20, 25, 100, 111, 112, 113, 114] as $count) {
            yield $count.' лет' => [$count, 'лет'];
        }
        foreach ([1, 21, 31, 101, 121] as $count) {
            yield $count.' год' => [$count, 'год'];
        }
        foreach ([2, 3, 4, 22, 23, 24, 102, 104] as $count) {
            yield $count.' года' => [$count, 'года'];
        }
    }

    #[DataProvider('units')]
    public function testUnitFollowsRussianDeclension(int $count, string $unit): void
    {
        self::assertSame($unit, CompanyAgeExtension::unit($count));
    }

    public function testYearsAreCountedFromFoundingYearByCurrentYear(): void
    {
        $extension = new CompanyAgeExtension();

        self::assertSame(['count' => 11, 'unit' => 'лет'], $extension->yearsSince(2015, 2026));
        self::assertSame(['count' => 21, 'unit' => 'год'], $extension->yearsSince(2015, 2036));
        self::assertSame(['count' => 22, 'unit' => 'года'], $extension->yearsSince(2015, 2037));
    }

    public function testDefaultsToTheSystemYear(): void
    {
        self::assertSame((int) date('Y') - 2015, (new CompanyAgeExtension())->yearsSince(2015)['count']);
    }

    public function testFutureFoundingYearNeverGivesNegativeAge(): void
    {
        self::assertSame(['count' => 0, 'unit' => 'лет'], (new CompanyAgeExtension())->yearsSince(2030, 2026));
    }
}
