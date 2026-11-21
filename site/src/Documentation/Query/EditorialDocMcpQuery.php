<?php

declare(strict_types=1);

namespace App\Documentation\Query;

final class EditorialDocMcpQuery extends DocMcpQuery
{
    protected function pageScope(string $alias): string
    {
        return '1=1';
    }

    protected function sectionScope(): string
    {
        return '1=1';
    }
}
