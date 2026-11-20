<?php

declare(strict_types=1);

namespace App\Documentation\Repository;

use App\Documentation\Entity\DocPage;
use App\Documentation\Exception\DocNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class DocPageRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function get(int $id): DocPage
    {
        return $this->em->find(DocPage::class, $id) ?? throw new DocNotFound('Материал не найден.');
    }

    public function bySlug(string $slug): DocPage
    {
        return $this->em->getRepository(DocPage::class)->findOneBy(['slug' => $slug]) ?? throw new DocNotFound('Материал не найден.');
    }

    public function save(DocPage $item): void
    {
        $this->em->persist($item);
    }

    /** @return list<\App\Documentation\Entity\DocPageLink> */
    public function publishedIncoming(int $id): array
    {
        return $this->em->createQueryBuilder()->select('l')->from(\App\Documentation\Entity\DocPageLink::class, 'l')
            ->join('l.source', 'p')->where('l.target = :id')->andWhere('p.status = :published')->andWhere('p.id <> :id')
            ->setParameter('id', $id)->setParameter('published', 'published')->getQuery()->getResult();
    }
}
