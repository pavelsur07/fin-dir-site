<?php

declare(strict_types=1);

namespace App\ClientCase\Query\CaseEditData;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\Exception\CaseNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class CaseEditDataQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(int $id): CaseEditData
    {
        /** @var CaseEditData|null $data */
        $data = $this->entityManager->createQueryBuilder()
            ->select(\sprintf(
                'NEW %s(c.id, c.slug, c.industry, c.title, c.problem, c.resultValue, c.resultLabel, c.tags, c.task, c.steps, c.metrics, c.source, c.featured, c.status, c.publishedAt)',
                CaseEditData::class,
            ))
            ->from(ClientCase::class, 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $data ?? throw new CaseNotFound($id);
    }
}
