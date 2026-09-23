<?php

declare(strict_types=1);

namespace App\Publication\Service;

use App\Publication\Entity\Post;
use App\Publication\Exception\PostWasModified;
use App\Publication\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Clock\ClockInterface;

/**
 * Переходы статуса статьи. Правила переходов -- в Post, здесь только сценарий и flush.
 */
final class PostStatusChanger
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function publish(int $id): void
    {
        $this->apply($id, fn (Post $post) => $post->publish($this->clock->now()));
    }

    public function unpublish(int $id): void
    {
        $this->apply($id, fn (Post $post) => $post->unpublish($this->clock->now()));
    }

    public function archive(int $id): void
    {
        $this->apply($id, fn (Post $post) => $post->archive($this->clock->now()));
    }

    public function restore(int $id): void
    {
        $this->apply($id, fn (Post $post) => $post->restore($this->clock->now()));
    }

    /**
     * @param \Closure(Post): void $transition
     */
    private function apply(int $id, \Closure $transition): void
    {
        $transition($this->posts->get($id));

        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException $e) {
            // Статью сохранили между загрузкой и flush -- #[Version] не даёт перезаписать.
            throw new PostWasModified($id, $e);
        }
    }
}
