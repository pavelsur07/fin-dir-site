<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use App\Documentation\Exception\DocNotFound;
use Doctrine\DBAL\Connection;
use Pagerfanta\PagerfantaInterface;

final class AdminDocQuery
{
    public function __construct(private readonly Connection $db, private readonly DocPagination $pagination)
    {
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function pages(int $page, string $q = '', string $status = '', int $section = 0): PagerfantaInterface
    {
        $sql = 'SELECT p.id,p.title,p.slug,p.status,p.position,p.version,s.title AS section_title FROM documentation_page p JOIN documentation_section s ON s.id=p.section_id WHERE 1=1';
        $params = [];
        if ('' !== trim($q)) {
            $sql .= ' AND p.title ILIKE :q';
            $params['q'] = '%'.$q.'%';
        }
        if ('' !== $status) {
            $sql .= ' AND p.status=:status';
            $params['status'] = $status;
        }
        if ($section > 0) {
            $sql .= ' AND p.section_id=:section';
            $params['section'] = $section;
        }

        return $this->pagination->paginate($sql.' ORDER BY s.position,s.id,p.position,p.id', $params, $page);
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function sections(int $page): PagerfantaInterface
    {
        return $this->pagination->paginate('SELECT s.id,s.title,s.slug,s.position,s.version FROM documentation_section s ORDER BY s.position,s.id', [], $page);
    }

    /** @return list<array<string,mixed>> */
    public function sectionOptions(): array
    {
        return $this->db->fetchAllAssociative("SELECT s.id,s.title || ' (' || s.slug || ')' AS title FROM documentation_section s ORDER BY s.position,s.id LIMIT 1000");
    }

    /** @return array<string,mixed> */
    public function section(int $id): array
    {
        return $this->db->fetchAssociative('SELECT id,title,slug,description,position,version FROM documentation_section WHERE id=:id', ['id' => $id]) ?: throw new DocNotFound('Раздел не найден.');
    }

    /** @return array<string,mixed> */
    public function page(int $id): array
    {
        $page = $this->db->fetchAssociative('SELECT id,section_id,title,slug,description,body,position,status,meta_title,meta_description,version,published_at FROM documentation_page WHERE id=:id', ['id' => $id]) ?: throw new DocNotFound('Страница не найдена.');
        $page['related_ids'] = array_map('intval', $this->db->fetchFirstColumn("SELECT target_id FROM documentation_page_link WHERE source_id=:id AND type='related' ORDER BY position,id", ['id' => $id]));
        $page['incoming'] = $this->db->fetchAllAssociative('SELECT DISTINCT p.id,p.title,p.status FROM documentation_page_link l JOIN documentation_page p ON p.id=l.source_id WHERE l.target_id=:id ORDER BY p.id', ['id' => $id]);

        return $page;
    }

    /** @return list<array<string,mixed>> */
    public function targets(int $id): array
    {
        return $this->db->fetchAllAssociative('SELECT l.target_id,l.fragment,l.source_urls,l.type,p.title,p.slug,p.body,p.status FROM documentation_page_link l JOIN documentation_page p ON p.id=l.target_id WHERE l.source_id=:id ORDER BY l.position,l.id', ['id' => $id]);
    }
}
