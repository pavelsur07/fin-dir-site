<?php

declare(strict_types=1);

namespace App\Documentation\Query;

final class PublishedDocMcpQuery extends DocMcpQuery
{
    protected function pageScope(string $alias): string
    {
        return $alias.".status='published'";
    }

    protected function sectionScope(): string
    {
        return "EXISTS (SELECT 1 FROM documentation_page p WHERE p.section_id=s.id AND p.status='published')";
    }
}
