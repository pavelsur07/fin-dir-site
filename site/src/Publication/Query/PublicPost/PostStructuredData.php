<?php

declare(strict_types=1);

namespace App\Publication\Query\PublicPost;

/**
 * JSON-LD статьи (SITE_RULES §15.2): BlogPosting + BreadcrumbList.
 * Чистая функция от уже прочитанных данных -- без запросов и времени системы.
 */
final class PostStructuredData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function build(PublicPostView $post, string $url, string $blogUrl, string $siteUrl): array
    {
        $organization = ['@id' => $siteUrl.'/#organization'];

        $posting = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'url' => $url,
            'mainEntityOfPage' => $url,
            'datePublished' => $post->publishedAt->format(\DATE_ATOM),
            'dateModified' => $post->updatedAt->format(\DATE_ATOM),
            'inLanguage' => 'ru-RU',
            'author' => $organization,
            'publisher' => $organization,
        ];

        $description = $post->seoDescription();
        if (null !== $description) {
            $posting['description'] = $description;
        }

        return [
            $posting,
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => $siteUrl.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Газета', 'item' => $blogUrl],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $url],
                ],
            ],
        ];
    }
}
