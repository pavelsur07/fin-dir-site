<?php

declare(strict_types=1);

namespace App\Documentation\Query;

use Doctrine\DBAL\Connection;
use Pagerfanta\Adapter\CallbackAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;

final class DocPagination
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param array<string,int|string> $params
     *
     * @return PagerfantaInterface<array<string,mixed>>
     */
    public function paginate(string $sql, array $params, int $page): PagerfantaInterface
    {
        return Pagerfanta::createForCurrentPageWithMaxPerPage(new CallbackAdapter(
            fn (): int => max(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM ('.$sql.') doc_count', $params)),
            fn (int $offset, int $length): array => $this->db->fetchAllAssociative($sql.' LIMIT '.$length.' OFFSET '.$offset, $params),
        ), $page, 20);
    }
}
