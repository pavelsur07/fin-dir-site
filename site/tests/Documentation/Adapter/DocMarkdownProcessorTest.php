<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Adapter;

use App\Documentation\Adapter\DocMarkdownProcessor;
use App\Shared\Adapter\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class DocMarkdownProcessorTest extends TestCase
{
    public function testOnlyResolvedDocumentationImagesAreEmbedded(): void
    {
        $renderer = new MarkdownRenderer();
        $processor = new DocMarkdownProcessor($renderer);
        $id = '9bcb5e71-70aa-4ce5-8c88-1bf827128b4a';
        $url = 'doc-image:'.$id;
        $body = "![Форма]($url)\n\n![Внешний](https://example.test/image.png)\n\n`![Код]($url)`";
        self::assertSame([['id' => $id, 'alt' => 'Форма']], $processor->analyze($body)->images);

        $html = $processor->render($body, [], [$id => [
            'url' => '/media/documentation/'.$id, 'width' => 1280, 'height' => 960,
        ]])->html;
        self::assertSame(1, substr_count($html, '<img '));
        self::assertStringContainsString('src="/media/documentation/'.$id.'"', $html);
        self::assertStringContainsString('alt="Форма"', $html);
        self::assertStringContainsString('width="1280"', $html);
        self::assertStringContainsString('height="960"', $html);
        self::assertStringContainsString('href="/media/documentation/'.$id.'"', $html);
        self::assertStringNotContainsString('example.test', $html);
        self::assertStringNotContainsString('<img ', $renderer->renderArticle($body)->html);
        self::assertStringNotContainsString('<img ', $processor->render($body, [])->html);
    }

    public function testImageAlternativesDoNotPublishNestedImagesOrCreateNestedLinks(): void
    {
        $processor = new DocMarkdownProcessor(new MarkdownRenderer());
        $outer = '9bcb5e71-70aa-4ce5-8c88-1bf827128b4a';
        $inner = 'a27ee528-820a-4868-b354-88ee4d398a40';
        $body = "[**![A ![B](doc-image:$inner)](doc-image:$outer)**](https://example.test/article)";
        self::assertSame([$outer], array_column($processor->analyze($body)->images, 'id'));
        $html = $processor->render($body, [], [
            $outer => ['url' => '/media/documentation/'.$outer, 'width' => 4, 'height' => 3],
            $inner => ['url' => '/media/documentation/'.$inner, 'width' => 4, 'height' => 3],
        ])->html;
        self::assertSame(2, substr_count($html, '<a '));
        self::assertStringContainsString('</a> <a ', $html);
        self::assertStringContainsString('href="/media/documentation/'.$outer.'"', $html);
        self::assertSame(1, substr_count($html, '<img '));
        self::assertStringNotContainsString($inner, $html);
        self::assertSame('', $processor->analyze("![&nbsp;](doc-image:$outer)")->images[0]['alt']);
        self::assertSame([], $processor->analyze("![[A](doc:123)](doc-image:$outer)")->links);
        $legacy = '![[A](doc:123)](https://example.test/image.png)';
        self::assertSame([], $processor->analyze($legacy)->links);
        self::assertStringContainsString('>A</p>', $processor->render($legacy, [])->html);
    }

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
        self::assertStringContainsString('href="https://docs.vashfindir.ru/articles/report#section-настройка"', $html);
    }
}
