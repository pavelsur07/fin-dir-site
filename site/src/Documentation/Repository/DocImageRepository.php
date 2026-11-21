<?php

declare(strict_types=1);

namespace App\Documentation\Repository;

use App\Documentation\Entity\DocImage;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;

final class DocImageRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(DocImage $image): void
    {
        $this->em->persist($image);
    }

    public function duplicateId(int $pageId, string $hash): ?string
    {
        $id = $this->em->getConnection()->fetchOne('SELECT id FROM documentation_image WHERE page_id = ? AND sha256 = ?', [$pageId, $hash]);

        return false === $id ? null : (string) $id;
    }

    /** @return list<string> */
    public function idsForPage(int $pageId): array
    {
        return $this->em->getConnection()->fetchFirstColumn('SELECT id FROM documentation_image WHERE page_id = ?', [$pageId]);
    }

    /** @param list<string> $ids */
    public function replaceReferences(int $pageId, array $ids): void
    {
        $db = $this->em->getConnection();
        if ([] === $ids) {
            $db->executeStatement('UPDATE documentation_image SET referenced = false WHERE page_id = ? AND referenced', [$pageId]);

            return;
        }

        $db->executeStatement(
            'UPDATE documentation_image SET referenced = (id IN (?)) WHERE page_id = ? AND referenced IS DISTINCT FROM (id IN (?))',
            [$ids, $pageId, $ids],
            [ArrayParameterType::STRING, \Doctrine\DBAL\ParameterType::INTEGER, ArrayParameterType::STRING],
        );
    }

    public function removeUnused(int $pageId, string $id): bool
    {
        return 1 === $this->em->getConnection()->executeStatement(
            'DELETE FROM documentation_image WHERE id = ? AND page_id = ? AND NOT referenced',
            [$id, $pageId],
        );
    }
}
