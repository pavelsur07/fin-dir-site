<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Entity;

use App\Documentation\Exception\DocConflict;
use App\Tests\Documentation\Builder\DocPageBuilder;
use App\Tests\Documentation\Builder\DocSectionBuilder;
use PHPUnit\Framework\TestCase;

final class DocPageTest extends TestCase
{
    public function testPublishedAddressStaysLockedAfterUnpublish(): void
    {
        $section = DocSectionBuilder::aSection();
        $page = DocPageBuilder::aPage($section, 'first', true);
        $now = new \DateTimeImmutable('2026-02-01');
        $page->unpublish($now);
        $this->expectException(DocConflict::class);
        $page->edit($section, 'Другое название', 'changed', 'Описание', 'Текст', 'Текст', 0, null, null, $now);
    }

    public function testCannotPublishEmptyText(): void
    {
        $section = DocSectionBuilder::aSection();
        $page = DocPageBuilder::aPage($section);
        $now = new \DateTimeImmutable('2026-01-01');
        $page->edit($section, 'Название', 'first-report', 'Описание', '', '', 0, null, null, $now);
        $this->expectException(DocConflict::class);
        $page->publish($now);
    }

    public function testPageCannotRecommendItself(): void
    {
        $page = DocPageBuilder::aPage(DocSectionBuilder::aSection());
        $this->expectException(DocConflict::class);
        $page->replaceLinks([['target' => $page, 'type' => \App\Documentation\ValueObject\DocLinkType::RELATED, 'fragment' => '', 'position' => 0, 'urls' => []]]);
    }
}
