<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use App\Documentation\DTO\DocImagePayload;
use App\Documentation\ValueObject\DocUrl;
use Doctrine\DBAL\Connection;

final class PublicDocImageQuery
{
    private const string VISIBLE = " FROM documentation_image i INNER JOIN documentation_page p ON p.id = i.page_id AND p.status = 'published' WHERE i.referenced";

    public function __construct(private readonly Connection $db, private readonly DocUrl $urls)
    {
    }

    public function get(string $id): ?DocImagePayload
    {
        $row = $this->db->fetchAssociative('SELECT i.contents, i.sha256'.self::VISIBLE.' AND i.id = ?', [$id]);

        return false === $row ? null : DocImagePayload::fromRow($row);
    }

    /** @return array<string,array{url:string,width:int,height:int}> */
    public function referencedByPage(int $pageId): array
    {
        $result = [];
        foreach ($this->db->fetchAllAssociative('SELECT i.id, i.width, i.height'.self::VISIBLE.' AND i.page_id = ?', [$pageId]) as $row) {
            $id = (string) $row['id'];
            $result[$id] = ['url' => $this->urls->route('docs_image', $id), 'width' => (int) $row['width'], 'height' => (int) $row['height']];
        }

        return $result;
    }
}
