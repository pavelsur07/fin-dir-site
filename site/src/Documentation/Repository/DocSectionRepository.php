<?php

declare(strict_types=1);

namespace App\Documentation\Repository;

use App\Documentation\Entity\DocSection;
use App\Documentation\Exception\DocNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class DocSectionRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function get(int $id): DocSection
    {
        return $this->em->find(DocSection::class, $id) ?? throw new DocNotFound('Материал не найден.');
    }

    public function bySlug(string $slug): DocSection
    {
        return $this->em->getRepository(DocSection::class)->findOneBy(['slug' => $slug]) ?? throw new DocNotFound('Материал не найден.');
    }

    public function save(DocSection $item): void
    {
        $this->em->persist($item);
    }
}
