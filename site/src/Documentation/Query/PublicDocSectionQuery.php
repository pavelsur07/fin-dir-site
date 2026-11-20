<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use App\Documentation\Exception\DocNotFound;
use Doctrine\DBAL\Connection;

final class PublicDocSectionQuery
{
    public function __construct(private readonly Connection $db, private readonly DocPagination $pagination, private readonly PublicDocHomeQuery $home)
    {
    }

    /** @return array<string,mixed> */
    public function get(string $slug, int $page): array
    {
        $section = $this->db->fetchAssociative("SELECT s.id,s.title,s.slug,s.description FROM documentation_section s WHERE s.slug=:slug AND EXISTS(SELECT 1 FROM documentation_page p WHERE p.section_id=s.id AND p.status='published')", ['slug' => $slug]);
        if (false === $section) {
            throw new DocNotFound('Раздел не найден.');
        }

        return ['section' => $section, 'navigation' => $this->home->navigation(), 'pager' => $this->pagination->paginate("SELECT p.id,p.title,p.slug,p.description FROM documentation_page p WHERE p.status='published' AND p.section_id=:id ORDER BY p.position,p.id", ['id' => (int) $section['id']], $page)];
    }
}
