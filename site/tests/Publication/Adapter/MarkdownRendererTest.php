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

        self::assertMatchesRegularExpression('/<div class="[^"]*overflow-x-auto[^"]*"[^>]*tabindex="0"[^>]*role="region"[^>]*><table /', $article->html);
        self::assertStringContainsString('scope="col"', $article->html);
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
        self::assertStringContainsString('class="text-accent underline', $article->html);
    }

    public function testArticleElementsCarryTailwindTypography(): void
    {
        $article = new MarkdownRenderer()->renderArticle("## Раздел\n\n- Пункт\n\n1. Первый\n\n> Цитата\n\n```php\necho 1;\n```\n");

        self::assertMatchesRegularExpression('/<h2 class="[^"]*type-t2-article[^"]*"/', $article->html);
        self::assertMatchesRegularExpression('/<ul class="[^"]*list-disc[^"]*">/', $article->html);
        self::assertMatchesRegularExpression('/<ol class="[^"]*list-decimal[^"]*">/', $article->html);
        self::assertMatchesRegularExpression('/<blockquote class="[^"]+"/', $article->html);
        self::assertMatchesRegularExpression('/<pre class="[^"]*\bdark\b[^"]*"/', $article->html);
    }

    public function testCalloutKindIsDetectedByLeadingLabel(): void
    {
        $markdown = "> **Короткий вывод:** а\n\n> **Важно:** б\n\n> **Best practice:** в\n\n> **Главный вопрос не про оборот.**\n\n> Без метки\n";
        $html = new MarkdownRenderer()->renderArticle($markdown)->html;

        self::assertSame(1, substr_count($html, 'data-vf-callout="summary"'));
        self::assertSame(1, substr_count($html, 'data-vf-callout="info"'));
        self::assertSame(3, substr_count($html, 'data-vf-callout="note"'));
        // Врезок ровно три вида: success-цвета в статьях не используются.
        self::assertStringNotContainsString('success', $html);
    }

    public function testCalloutLabelBecomesTitleAndIconMatchesKind(): void
    {
        $html = new MarkdownRenderer()->renderArticle("> **Важно:** Срок не продлевается.\n\n> **Совет:** Считайте на данных прошлого года.\n\n> **Коротко:** тезис\n")->html;

        // Метка без двоеточия -- отдельный заголовок, текст -- отдельный абзац.
        self::assertMatchesRegularExpression('/data-vf-callout-title="info"[^>]*><strong>Важно<\/strong><\/p>/', $html);
        self::assertMatchesRegularExpression('/data-vf-callout-part="info"[^>]*>Срок не продлевается\.<\/p>/', $html);
        // info -- иконка info, «Совет» -- лампочка (в ней есть path «M9 18h6»), «Коротко» -- без иконки.
        self::assertSame(2, substr_count($html, '<svg'));
        self::assertStringContainsString('M12 16v-4', $html);
        self::assertStringContainsString('M9 18h6', $html);
        // Каждый открытый внутренний контейнер закрыт.
        self::assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
    }

    public function testFaqQuestionsAreMarkedOnlyInsideFaqSection(): void
    {
        $markdown = "## Раздел\n\n### Не вопрос\n\nтекст\n\n## Частые вопросы\n\n### Первый?\n\nОтвет\n\n### Второй?\n\nОтвет\n";
        $html = new MarkdownRenderer()->renderArticle($markdown)->html;

        self::assertSame(2, substr_count($html, 'data-vf-faq="question"'));
        self::assertMatchesRegularExpression('/<h3 class="[^"]*"[^>]*id="section-[^"]+"[^>]*>Не вопрос<\/h3>/', $html);
        self::assertDoesNotMatchRegularExpression('/data-vf-faq="question"[^>]*>Не вопрос/', $html);
    }

    public function testRawHtmlAndUnsafeLinksAreDropped(): void
    {
        $article = new MarkdownRenderer()->renderArticle("<script>alert(1)</script>\n\n[x](javascript:alert(1)) <b onclick=\"x\">b</b>");

        self::assertStringNotContainsString('<script', $article->html);
        self::assertStringNotContainsString('javascript:', $article->html);
        self::assertStringNotContainsString('onclick', $article->html);
    }
}
