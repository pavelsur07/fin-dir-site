<?php

declare(strict_types=1);

namespace App\Tests\Publication\Adapter;

use App\Publication\Adapter\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    public function testHeadingsGetIdsAndH2BuildTableOfContents(): void
    {
        $article = new MarkdownRenderer()->renderArticle("## Сравнение затрат\n\nТекст.\n\n### Деталь\n\n## Unit-экономика\n");

        self::assertCount(2, $article->toc);
        self::assertSame('Сравнение затрат', $article->toc[0]['title']);
        self::assertSame('Unit-экономика', $article->toc[1]['title']);
        self::assertStringContainsString('id="'.$article->toc[0]['id'].'">Сравнение затрат</h2>', $article->html);
        self::assertMatchesRegularExpression('/<h3 class="[^"]+" id="[^"]+">Деталь<\/h3>/', $article->html);
    }

    public function testDuplicateHeadingsGetUniqueIds(): void
    {
        $article = new MarkdownRenderer()->renderArticle("## Итог\n\n## Итог\n");

        self::assertNotSame($article->toc[0]['id'], $article->toc[1]['id']);
    }

    public function testLevelOneHeadingIsDemotedToKeepSingleH1(): void
    {
        $article = new MarkdownRenderer()->renderArticle("# Заголовок в тексте\n");

        self::assertStringNotContainsString('<h1', $article->html);
        self::assertStringContainsString('>Заголовок в тексте</h2>', $article->html);
        self::assertCount(1, $article->toc);
    }

    public function testTableIsWrappedForHorizontalScroll(): void
    {
        $article = new MarkdownRenderer()->renderArticle("| A | B |\n|---|---|\n| 1 | 2 |\n");

        self::assertStringContainsString('<div class="my-6 overflow-x-auto rounded-lg border border-slate-200"><table class="w-full border-collapse text-left">', $article->html);
    }

    public function testImagesAreReplacedByAltText(): void
    {
        $article = new MarkdownRenderer()->renderArticle('Схема: ![отчёт ДДС](https://cdn.example.com/dds.png) ниже.');

        self::assertStringNotContainsString('<img', $article->html);
        self::assertStringContainsString('Схема: отчёт ДДС ниже.', $article->html);
    }

    public function testExternalLinksGetSafeRel(): void
    {
        $article = new MarkdownRenderer()->renderArticle('[внешняя](https://example.com) и [своя](https://vashfindir.ru/gazeta)');

        self::assertStringContainsString('rel="noopener noreferrer" href="https://example.com">', $article->html);
        self::assertStringContainsString('href="https://vashfindir.ru/gazeta">', $article->html);
        self::assertStringContainsString('class="text-red-700 underline', $article->html);
    }

    public function testArticleElementsCarryTailwindTypography(): void
    {
        $article = new MarkdownRenderer()->renderArticle("## Раздел\n\n- Пункт\n\n1. Первый\n\n> Цитата\n\n```php\necho 1;\n```\n");

        self::assertStringContainsString('<h2 class="mb-4 mt-10 text-3xl font-bold', $article->html);
        self::assertStringContainsString('<ul class="mb-4 list-disc pl-6">', $article->html);
        self::assertStringContainsString('<ol class="mb-4 list-decimal pl-6">', $article->html);
        self::assertStringContainsString('<blockquote class="my-6 border-l-4', $article->html);
        self::assertStringContainsString('<pre class="my-6 overflow-x-auto', $article->html);
    }

    public function testRawHtmlAndUnsafeLinksAreDropped(): void
    {
        $article = new MarkdownRenderer()->renderArticle("<script>alert(1)</script>\n\n[x](javascript:alert(1)) <b onclick=\"x\">b</b>");

        self::assertStringNotContainsString('<script', $article->html);
        self::assertStringNotContainsString('javascript:', $article->html);
        self::assertStringNotContainsString('onclick', $article->html);
    }
}
