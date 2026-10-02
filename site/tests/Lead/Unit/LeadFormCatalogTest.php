<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\ValueObject\LeadFormCatalog;
use PHPUnit\Framework\TestCase;

final class LeadFormCatalogTest extends TestCase
{
    public function testSimpleFormHasNoQuestionsAndEmptySnapshot(): void
    {
        $result = LeadFormCatalog::get('consultation')->snapshot(['anything' => 'x']);

        self::assertSame(['snapshot' => [], 'errors' => []], $result);
    }

    public function testQualificationAnswersAreSnapshottedWithLabels(): void
    {
        $result = LeadFormCatalog::get('diagnostics')->snapshot([
            'channel' => 'ozon',
            'turnover' => '1m_5m',
            'need' => 'outsourced_cfo',
        ]);

        self::assertSame([], $result['errors']);
        self::assertSame([
            'question' => 'channel',
            'questionLabel' => 'Где продаёте?',
            'answer' => 'ozon',
            'answerLabel' => 'Ozon',
        ], $result['snapshot'][0]);
        self::assertCount(3, $result['snapshot']);
    }

    public function testMissingAndForeignAnswersAreErrors(): void
    {
        $result = LeadFormCatalog::get('diagnostics')->snapshot([
            'channel' => 'aliexpress',
            'turnover' => ['array'],
        ]);

        self::assertSame(['channel', 'turnover', 'need'], array_keys($result['errors']));
    }

    public function testExcursionOtherRoleRequiresFreeText(): void
    {
        $form = LeadFormCatalog::get('excursion');

        $missing = $form->snapshot(['role' => 'other', 'turnover' => 'under_2m']);
        self::assertSame(['role_other'], array_keys($missing['errors']));

        $tooLong = $form->snapshot(['role' => 'other', 'role_other' => str_repeat('я', 101), 'turnover' => 'under_2m']);
        self::assertSame(['role_other'], array_keys($tooLong['errors']));

        $ok = $form->snapshot(['role' => 'other', 'role_other' => '  Операционный директор ', 'turnover' => '2m_10m']);
        self::assertSame([], $ok['errors']);
        self::assertSame(['role', 'role_other', 'turnover'], array_column($ok['snapshot'], 'question'));
        self::assertSame('Операционный директор', $ok['snapshot'][1]['answer']);
    }

    public function testExcursionIgnoresFreeTextWhenRoleIsNotOther(): void
    {
        $result = LeadFormCatalog::get('excursion')->snapshot(['role' => 'cfo', 'role_other' => 'мусор', 'turnover' => 'over_10m']);

        self::assertSame([], $result['errors']);
        self::assertSame(['role', 'turnover'], array_column($result['snapshot'], 'question'));
    }

    public function testUnknownFormIsRejected(): void
    {
        self::assertFalse(LeadFormCatalog::has('unknown'));

        $this->expectException(\InvalidArgumentException::class);
        LeadFormCatalog::get('unknown');
    }
}
