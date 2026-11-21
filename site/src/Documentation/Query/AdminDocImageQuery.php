<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use App\Documentation\DTO\DocImagePayload;
use App\Documentation\ValueObject\DocUrl;
use Doctrine\DBAL\Connection;
use Pagerfanta\PagerfantaInterface;

final class AdminDocImageQuery
{
    public function __construct(private readonly Connection $db, private readonly DocUrl $urls, private readonly DocPagination $pagination)
    {
    }

    public function get(string $id): ?DocImagePayload
    {
        $row = $this->db->fetchAssociative('SELECT contents, sha256 FROM documentation_image WHERE id = ?', [$id]);

        return false === $row ? null : DocImagePayload::fromRow($row);
    }

    /** @return array<string,array{url:string,width:int,height:int}> */
    public function referencedByPage(int $pageId): array
    {
        $result = [];
        foreach ($this->db->fetchAllAssociative('SELECT id, width, height FROM documentation_image WHERE page_id = ? AND referenced', [$pageId]) as $row) {
            $id = (string) $row['id'];
            $result[$id] = ['url' => $this->urls->adminImage($id), 'width' => (int) $row['width'], 'height' => (int) $row['height']];
        }

        return $result;
    }

    /** @return PagerfantaInterface<array<string,mixed>> */
    public function forPage(int $id, int $page): PagerfantaInterface
    {
        return $this->pagination->paginate('SELECT id, width, height, byte_size, referenced FROM documentation_image WHERE page_id = :id ORDER BY created_at DESC, id DESC', ['id' => $id], $page);
    }
}
