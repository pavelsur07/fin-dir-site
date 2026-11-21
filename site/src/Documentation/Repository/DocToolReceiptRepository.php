<?php

declare(strict_types=1);

namespace App\Documentation\Repository;

use App\Documentation\Entity\DocToolReceipt;
use Doctrine\ORM\EntityManagerInterface;

final class DocToolReceiptRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function find(string $actor, string $key): ?DocToolReceipt
    {
        return $this->em->getRepository(DocToolReceipt::class)->findOneBy(['actor' => $actor, 'requestKey' => $key]);
    }

    public function save(DocToolReceipt $receipt): void
    {
        $this->em->persist($receipt);
    }
}
