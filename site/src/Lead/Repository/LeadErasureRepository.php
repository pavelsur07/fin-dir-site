<?php

declare(strict_types=1);

namespace App\Lead\Repository;

use App\Lead\Entity\LeadErasure;
use Doctrine\ORM\EntityManagerInterface;

final class LeadErasureRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(LeadErasure $erasure): void
    {
        $this->entityManager->persist($erasure);
    }
}
