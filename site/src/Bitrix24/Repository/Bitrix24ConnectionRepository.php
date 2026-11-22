<?php

declare(strict_types=1);

namespace App\Bitrix24\Repository;

use App\Bitrix24\Entity\Bitrix24Connection;
use Doctrine\ORM\EntityManagerInterface;

final class Bitrix24ConnectionRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function find(): ?Bitrix24Connection
    {
        return $this->entityManager->find(Bitrix24Connection::class, Bitrix24Connection::SINGLETON_ID);
    }

    public function save(Bitrix24Connection $connection): void
    {
        $this->entityManager->persist($connection);
    }

    public function remove(Bitrix24Connection $connection): void
    {
        $this->entityManager->remove($connection);
    }
}
