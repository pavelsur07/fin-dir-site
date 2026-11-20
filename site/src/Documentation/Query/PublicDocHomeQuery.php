<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use Doctrine\DBAL\Connection;
use Pagerfanta\PagerfantaInterface;

final class PublicDocHomeQuery
{
    private const string SECTIONS = "SELECT s.id,s.slug,s.title,s.description,s.position FROM documentation_section s WHERE EXISTS (SELECT 1 FROM documentation_page p WHERE p.section_id=s.id AND p.status='published') ORDER BY s.position,s.id";

    public function __construct(private readonly Connection $db, private readonly DocPagination $pagination)
    {
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function paginate(int $page): PagerfantaInterface
    {
        return $this->pagination->paginate(self::SECTIONS, [], $page);
    }

    /** @return list<array<string,mixed>> */
    public function navigation(): array
    {
        return $this->db->fetchAllAssociative(self::SECTIONS.' LIMIT 100');
    }
}
