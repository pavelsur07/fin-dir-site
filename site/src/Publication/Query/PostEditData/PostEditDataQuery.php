<?php

declare(strict_types=1);

namespace App\Publication\Query\PostEditData;

use App\Publication\Entity\Post;
use App\Publication\Exception\PostNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class PostEditDataQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(int $id): PostEditData
    {
        $data = $this->entityManager->createQueryBuilder()
            ->select(\sprintf(
                'NEW %s(p.id, p.title, p.slug, p.excerpt, p.body, p.metaTitle, p.metaDescription, p.status, p.publishedAt, p.version)',
                PostEditData::class,
            ))
            ->from(Post::class, 'p')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $data instanceof PostEditData ? $data : throw new PostNotFound($id);
    }
}
