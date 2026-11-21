<?php

declare(strict_types=1);

namespace App\Documentation\Repository;

use App\Documentation\Entity\DocMcpClientGrant;
use Doctrine\ORM\EntityManagerInterface;

final class DocMcpClientGrantRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function find(string $issuerKey, string $clientId): ?DocMcpClientGrant
    {
        return $this->em->getRepository(DocMcpClientGrant::class)->findOneBy(['issuerKey' => $issuerKey, 'clientId' => $clientId]);
    }

    public function save(DocMcpClientGrant $grant): void
    {
        $this->em->persist($grant);
    }
}
