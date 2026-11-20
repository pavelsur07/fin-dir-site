<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use App\Documentation\Exception\DocNotFound;
use Doctrine\DBAL\Connection;

final class PublicDocPageQuery
{
    public function __construct(private readonly Connection $db, private readonly PublicDocHomeQuery $home)
    {
    }

    /** @return array<string,mixed> */
    public function getBySlug(string $slug): array
    {
        $p = $this->db->fetchAssociative("SELECT p.id,p.title,p.slug,p.description,p.body,p.meta_title,p.meta_description,p.position,p.section_id,p.updated_at,s.slug AS section_slug,s.title AS section_title FROM documentation_page p JOIN documentation_section s ON s.id=p.section_id WHERE p.slug=:slug AND p.status='published'", ['slug' => $slug]);
        if (false === $p) {
            throw new DocNotFound('Страница не найдена.');
        }
        $targets = $this->db->fetchAllAssociative("SELECT l.target_id,l.fragment,l.source_urls,l.type,l.position,p.title,p.slug FROM documentation_page_link l JOIN documentation_page p ON p.id=l.target_id WHERE l.source_id=:id AND p.status='published' ORDER BY l.position,l.id", ['id' => $p['id']]);
        $previous = $this->db->fetchAssociative("SELECT p.title,p.slug FROM documentation_page p WHERE p.section_id=:section AND p.status='published' AND (p.position<:position OR (p.position=:position AND p.id<:id)) ORDER BY p.position DESC,p.id DESC LIMIT 1", ['section' => $p['section_id'], 'position' => $p['position'], 'id' => $p['id']]);
        $next = $this->db->fetchAssociative("SELECT p.title,p.slug FROM documentation_page p WHERE p.section_id=:section AND p.status='published' AND (p.position>:position OR (p.position=:position AND p.id>:id)) ORDER BY p.position,p.id LIMIT 1", ['section' => $p['section_id'], 'position' => $p['position'], 'id' => $p['id']]);

        return ['page' => $p, 'targets' => $targets, 'related' => array_values(array_filter($targets, static fn (array $row): bool => 'related' === $row['type'])), 'previous' => $previous, 'next' => $next, 'navigation' => $this->home->navigation()];
    }
}
