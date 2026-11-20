<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Adapter;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Shared\Adapter\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class DocMarkdownProcessorTest extends TestCase
{
    public function testAnalysisUsesLinksInAstAndIgnoresExamples(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        $a = $processor->analyze("## Настройка\n\n## Настройка\n\n[Отчёт](doc:123)\n\n`[Пример](doc:999)`\n\n```\n[Код](doc:456)\n```\n");
        self::assertCount(1, $a->links);
        self::assertSame(123, $a->links[0]['id']);
        self::assertCount(2, $a->headings);
        self::assertNotSame($a->headings[0]['id'], $a->headings[1]['id']);
    }

    public function testSafeHtmlAndResolvedUrl(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        $result = $processor->render('[Отчёт](doc:123) <script>alert(1)</script> [x](javascript:alert(2))', ['doc:123' => 'https://docs.vashfindir.ru/articles/report']);
        self::assertStringContainsString('href="https://docs.vashfindir.ru/articles/report"', $result->html);
        self::assertStringNotContainsString('<script>', $result->html);
        self::assertStringNotContainsString('href="javascript:', $result->html);
    }

    public function testHeadingIdsMatchRenderAcrossConversions(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        $body = "## Настройка\n\n## Настройка";
        $ids = array_column($processor->analyze($body)->headings, 'id');
        $rendered = $processor->render($body, []);
        self::assertSame($ids, array_column($rendered->toc, 'id'));
        foreach ($ids as $id) {
            self::assertStringContainsString('id="'.$id.'"', $rendered->html);
        }
    }

    public function testMissingTargetNeverEmitsTechnicalScheme(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        self::assertStringNotContainsString('href="doc:', $processor->render('[Скрыто](doc:999)', [])->html);
    }

    public function testMissingSlugTargetsAreSuppressedEvenForAutomaticLinks(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        $html = $processor->renderTargets('[Draft](/articles/draft) <https://docs.vashfindir.ru/articles/draft>', [])->html;
        self::assertStringNotContainsString('href="/articles/draft"', $html);
        self::assertStringNotContainsString('href="https://docs.vashfindir.ru/articles/draft"', $html);
    }

    public function testRussianAnchorUsesExactAstUrlBinding(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        $body = $processor->link('Настройка', 12, 'section-настройка');
        $url = $processor->analyze($body)->links[0]['url'];
        $html = $processor->renderTargets($body, [['target_id' => 12, 'slug' => 'report', 'fragment' => 'section-настройка', 'source_urls' => json_encode([$url])]])->html;
        self::assertStringContainsString('href="https://docs.vashfindir.ru/articles/report#section-настройка"',$html);
    }
}
