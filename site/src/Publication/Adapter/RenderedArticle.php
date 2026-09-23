<?php

declare(strict_types=1);

namespace App\Publication\Adapter;

/**
 * Результат рендера текста статьи: безопасный HTML и оглавление по h2.
 */
final readonly class RenderedArticle
{
    /**
     * @param list<array{id: string, title: string}> $toc
     */
    public function __construct(
        public string $html,
        public array $toc,
    ) {
    }
}
