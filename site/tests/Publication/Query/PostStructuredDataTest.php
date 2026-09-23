<?php

declare(strict_types=1);

namespace App\Tests\Publication\Query;

use App\Publication\Query\PublicPost\PostStructuredData;
use App\Publication\Query\PublicPost\PublicPostView;
use PHPUnit\Framework\TestCase;

final class PostStructuredDataTest extends TestCase
{
    private const string SITE = 'https://vashfindir.ru';
    private const string URL = 'https://vashfindir.ru/gazeta/test';

    public function testBuildsBlogPostingAndBreadcrumbs(): void
    {
        [$posting, $breadcrumbs] = PostStructuredData::build($this->view(metaDescription: 'SEO-описание'), self::URL, self::SITE.'/gazeta', self::SITE);

        self::assertSame('BlogPosting', $posting['@type']);
        self::assertSame('Заголовок', $posting['headline']);
        self::assertSame(self::URL, $posting['url']);
        self::assertSame('2026-05-27T09:00:00+00:00', $posting['datePublished']);
        self::assertSame('2026-06-01T10:30:00+00:00', $posting['dateModified']);
        self::assertSame('SEO-описание', $posting['description']);
        self::assertSame(['@id' => self::SITE.'/#organization'], $posting['author']);

        self::assertSame('BreadcrumbList', $breadcrumbs['@type']);
        self::assertIsArray($breadcrumbs['itemListElement']);
        self::assertCount(3, $breadcrumbs['itemListElement']);
        self::assertSame(self::URL, $breadcrumbs['itemListElement'][2]['item']);
    }

    public function testDescriptionFallsBackToExcerptAndIsOmittedWithoutIt(): void
    {
        [$withExcerpt] = PostStructuredData::build($this->view(excerpt: 'Анонс'), self::URL, self::SITE.'/gazeta', self::SITE);
        [$withoutAny] = PostStructuredData::build($this->view(), self::URL, self::SITE.'/gazeta', self::SITE);

        self::assertSame('Анонс', $withExcerpt['description']);
        self::assertArrayNotHasKey('description', $withoutAny);
    }

    private function view(?string $excerpt = null, ?string $metaDescription = null): PublicPostView
    {
        return new PublicPostView(
            1,
            'test',
            'Заголовок',
            $excerpt,
            'Текст',
            null,
            $metaDescription,
            new \DateTimeImmutable('2026-05-27 09:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-06-01 10:30:00', new \DateTimeZone('UTC')),
        );
    }
}
