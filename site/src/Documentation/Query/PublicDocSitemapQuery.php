<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use Doctrine\DBAL\Connection;

final class PublicDocSitemapQuery
{
    public function __construct(private readonly Connection $db)
    {
    }

    /** @return array{pages:list<array<string,mixed>>,sections:list<array<string,mixed>>} */
    public function get(): array
    {
        return ['pages' => $this->db->fetchAllAssociative("SELECT p.slug,p.updated_at FROM documentation_page p WHERE p.status='published' ORDER BY p.id LIMIT 49000"), 'sections' => $this->db->fetchAllAssociative("SELECT s.slug,GREATEST(s.updated_at,MAX(p.updated_at)) AS updated_at FROM documentation_section s JOIN documentation_page p ON p.section_id=s.id AND p.status='published' GROUP BY s.id ORDER BY s.id LIMIT 999")];
    }
}
