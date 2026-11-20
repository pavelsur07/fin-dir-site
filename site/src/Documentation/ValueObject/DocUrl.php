<?php

declare(strict_types=1);

namespace App\Documentation\ValueObject;

final readonly class DocUrl
{
    public function __construct(private string $base = 'https://docs.vashfindir.ru')
    {
    }

    public function route(string $name, string $slug = ''): string
    {
        return rtrim($this->base, '/').match ($name) {
            'docs_home' => '/', 'docs_page' => '/articles/'.$slug, 'docs_section' => '/sections/'.$slug,
            'docs_search' => '/search', 'docs_sitemap' => '/sitemap.xml',
            default => throw new \InvalidArgumentException('Unknown documentation route.'),
        };
    }
}
