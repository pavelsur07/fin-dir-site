<?php

declare(strict_types=1);

namespace App\Publication\Repository;

use App\Publication\Entity\Post;
use App\Publication\Exception\PostNotFound;
use Doctrine\ORM\EntityManagerInterface;

final class PostRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(int $id): Post
    {
        return $this->entityManager->find(Post::class, $id) ?? throw new PostNotFound($id);
    }

    public function slugExists(string $slug): bool
    {
        // count(), а не findOneBy(): чужая статья не попадает в Unit of Work сценария.
        return $this->entityManager->getRepository(Post::class)->count(['slug' => $slug]) > 0;
    }

    /** persist без flush: транзакцией управляет Application Service. */
    public function save(Post $post): void
    {
        $this->entityManager->persist($post);
    }
}
