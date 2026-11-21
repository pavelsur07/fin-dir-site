<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use App\Documentation\DTO\DocMcpPageDetails;
use App\Documentation\DTO\DocMcpSearchInput;
use App\Documentation\Exception\DocNotFound;
use App\Documentation\Exception\DocToolFailure;
use Doctrine\DBAL\Connection;
use Pagerfanta\PagerfantaInterface;

abstract class DocMcpQuery
{
    public function __construct(protected readonly Connection $db, private readonly DocPagination $pagination)
    {
    }

    abstract protected function pageScope(string $alias): string;

    abstract protected function sectionScope(): string;

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function sections(int $page): PagerfantaInterface
    {
        return $this->pagination->paginate('SELECT s.id,s.title,s.slug,s.description,s.position FROM documentation_section s WHERE '.$this->sectionScope().' ORDER BY s.position,s.id', [], $page);
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function search(DocMcpSearchInput $input): PagerfantaInterface
    {
        $sql = 'SELECT p.id,p.title,p.slug,p.description,p.status,p.section_id FROM documentation_page p JOIN documentation_section s ON s.id=p.section_id WHERE '.$this->pageScope('p');
        $params = [];
        $order = 's.position,s.id,p.position,p.id';
        if ('' !== trim($input->q)) {
            $vector = "(setweight(to_tsvector('russian',p.title),'A') || setweight(to_tsvector('russian',p.search_text),'B'))";
            $sql .= ' AND '.$vector." @@ plainto_tsquery('russian',:q)";
            $params['q'] = trim($input->q);
            $order = 'ts_rank('.$vector.",plainto_tsquery('russian',:q)) DESC,p.id ASC";
        }
        if (null !== $input->sectionId) {
            $sql .= ' AND p.section_id=:section';
            $params['section'] = $input->sectionId;
        }
        if (null !== $input->status) {
            $sql .= ' AND p.status=:status';
            $params['status'] = $input->status;
        }

        return $this->pagination->paginate($sql.' ORDER BY '.$order, $params, $input->page);
    }

    public function page(int $id): DocMcpPageDetails
    {
        $row = $this->db->fetchAssociative('SELECT p.id,p.section_id,p.title,p.slug,p.description,p.body,p.position,p.status,p.meta_title,p.meta_description,p.version,p.published_at FROM documentation_page p WHERE p.id=:id AND '.$this->pageScope('p'), ['id' => $id]);
        if (false === $row) {
            throw new DocNotFound('Страница не найдена.');
        }
        $related = $this->db->fetchFirstColumn("SELECT l.target_id FROM documentation_page_link l JOIN documentation_page source ON source.id=l.source_id JOIN documentation_page p ON p.id=l.target_id WHERE l.source_id=:id AND l.type='related' AND ".$this->pageScope('p').' AND '.$this->pageScope('source').' ORDER BY l.position,l.id', ['id' => $id]);

        return new DocMcpPageDetails(['id' => (int) $row['id'], 'sectionId' => (int) $row['section_id'], 'title' => $row['title'], 'slug' => $row['slug'], 'description' => $row['description'], 'body' => $row['body'], 'position' => (int) $row['position'], 'status' => $row['status'], 'metaTitle' => $row['meta_title'], 'metaDescription' => $row['meta_description'], 'version' => (int) $row['version'], 'slugLocked' => null !== $row['published_at'], 'relatedIds' => array_map('intval', $related)]);
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function links(int $id, string $direction, int $page): PagerfantaInterface
    {
        $this->page($id);
        $column = 'incoming' === $direction ? 'target_id' : 'source_id';

        return $this->pagination->paginate('SELECT l.source_id,l.target_id,l.type,l.fragment,l.position,source.title AS source_title,target.title AS target_title FROM documentation_page_link l JOIN documentation_page source ON source.id=l.source_id JOIN documentation_page target ON target.id=l.target_id WHERE l.'.$column.'=:id AND '.$this->pageScope('source').' AND '.$this->pageScope('target').' ORDER BY l.position,l.id', ['id' => $id], $page);
    }

    /** @return list<array<string,mixed>> */
    public function targets(int $id): array
    {
        $this->page($id);
        $result = $this->db->executeQuery('SELECT l.target_id,l.fragment,l.source_urls,l.type,p.title,p.slug,p.body,p.status FROM documentation_page_link l JOIN documentation_page source ON source.id=l.source_id JOIN documentation_page p ON p.id=l.target_id WHERE l.source_id=:id AND '.$this->pageScope('p').' AND '.$this->pageScope('source').' ORDER BY l.position,l.id', ['id' => $id]);
        $rows = [];
        $bytes = 0;
        foreach ($result->iterateAssociative() as $row) {
            $bytes += strlen((string) $row['body']);
            if ($bytes > 10 * 1024 * 1024) {
                throw new DocToolFailure('LIMIT_EXCEEDED', 'Слишком большой граф ссылок для одной проверки.');
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return list<array{sourceId:int,fragment:string}> */
    public function incomingAnchors(int $id): array
    {
        $rows = $this->db->fetchAllAssociative("SELECT l.source_id,l.fragment FROM documentation_page_link l JOIN documentation_page p ON p.id=l.source_id JOIN documentation_page target ON target.id=l.target_id WHERE l.target_id=:id AND p.status='published' AND p.id<>:id AND l.fragment<>'' AND ".$this->pageScope('target').' ORDER BY l.id', ['id' => $id]);

        return array_map(static fn (array $row): array => ['sourceId' => (int) $row['source_id'], 'fragment' => (string) $row['fragment']], $rows);
    }
}
