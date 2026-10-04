<?php

declare(strict_types=1);

namespace App\ClientCase\Repository;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\Exception\CaseNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class ClientCaseRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(int $id): ClientCase
    {
        return $this->entityManager->find(ClientCase::class, $id) ?? throw new CaseNotFound($id);
    }

    public function slugExists(string $slug): bool
    {
        // count(), а не findOneBy(): чужой кейс не попадает в Unit of Work сценария.
        return $this->entityManager->getRepository(ClientCase::class)->count(['slug' => $slug]) > 0;
    }

    /**
     * Главные кейсы, кроме указанного: сценарий оставляет главным только один.
     *
     * @return list<ClientCase>
     */
    public function featuredExcept(ClientCase $case): array
    {
        /** @var list<ClientCase> $others */
        $others = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(ClientCase::class, 'c')
            ->where('c.featured = true')
            ->andWhere('c <> :case')
            ->setParameter('case', $case)
            ->getQuery()
            ->getResult();

        return $others;
    }

    /** persist без flush: транзакцией управляет Application Service. */
    public function save(ClientCase $case): void
    {
        $this->entityManager->persist($case);
    }
}
