<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use Pagerfanta\PagerfantaInterface;

final class PublicDocSearchQuery
{
    public function __construct(private readonly DocPagination $pagination)
    {
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function paginate(string $q, int $page): PagerfantaInterface
    {
        $vector = "(setweight(to_tsvector('russian',p.title),'A') || setweight(to_tsvector('russian',p.search_text),'B'))";

        return $this->pagination->paginate('SELECT p.id,p.title,p.slug,p.description,ts_rank('.$vector.",plainto_tsquery('russian',:q)) AS rank FROM documentation_page p WHERE p.status='published' AND ".$vector." @@ plainto_tsquery('russian',:q) ORDER BY rank DESC,p.id ASC", ['q' => trim($q)], $page);
    }
}
