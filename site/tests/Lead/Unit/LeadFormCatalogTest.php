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

    public function testUnknownFormIsRejected(): void
    {
        self::assertFalse(LeadFormCatalog::has('unknown'));

        $this->expectException(\InvalidArgumentException::class);
        LeadFormCatalog::get('unknown');
    }
}
