<?php

declare(strict_types=1);

namespace App\Publication\Service;

use App\Publication\DTO\PostInput;
use App\Publication\Exception\PostSlugAlreadyTaken;
use App\Publication\Exception\PostWasModified;
use App\Publication\Repository\PostRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Clock\ClockInterface;

final class PostEditor
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function edit(int $id, PostInput $input): void
    {
        $post = $this->posts->get($id);
        $now = $this->clock->now();
        $slug = trim((string) $input->slug);

        try {
            // Версия, с которой открыли форму, против текущей в базе. Без неё
            // правка молча перезаписала бы чужую -- поэтому она обязательна.
            $this->entityManager->lock(
                $post,
                LockMode::OPTIMISTIC,
                $input->version ?? throw new \LogicException('Post version is required for editing.'),
            );

            $post->edit(
                $input->title,
                $input->excerpt,
                $input->body,
                $input->metaTitle,
                $input->metaDescription,
                $now,
            );

            if ('' !== $slug && $slug !== $post->slug()) {
                // Сначала правило Entity (адрес заблокирован), потом занятость.
                $post->changeSlug($slug, $now);
                if ($this->posts->slugExists($slug)) {
                    throw new PostSlugAlreadyTaken($slug);
                }
            }

            $this->entityManager->flush();
        } catch (OptimisticLockException $e) {
            throw new PostWasModified($id, $e);
        } catch (UniqueConstraintViolationException $e) {
            throw new PostSlugAlreadyTaken($slug, $e);
        }
    }
}
