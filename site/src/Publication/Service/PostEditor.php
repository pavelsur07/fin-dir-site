<?php

declare(strict_types=1);

namespace App\Publication\Service;

use App\Publication\Adapter\PostCoverStorage;
use App\Publication\DTO\PostInput;
use App\Publication\Exception\PostSlugAlreadyTaken;
use App\Publication\Exception\PostWasModified;
use App\Publication\Repository\PostRepository;
use App\Publication\ValueObject\PostCover;
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
        private readonly PostCoverStorage $covers,
    ) {
    }

    public function edit(int $id, PostInput $input): void
    {
        $post = $this->posts->get($id);
        $now = $this->clock->now();
        $slug = trim((string) $input->slug);
        $previousCover = $post->cover();

        // Файл пишется до flush: иначе в базе на мгновение окажется ссылка на несуществующий файл.
        // Если сохранение не удалось, новый файл убираем -- ссылки на него нет.
        $uploaded = null === $input->coverUpload ? null : $this->covers->store($input->coverUpload);

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
                $input->formTitle,
                $input->rubric,
            );

            $post->changeCover(match (true) {
                null !== $uploaded => new PostCover($uploaded, (string) $input->coverAlt),
                $input->removeCover, null === $previousCover => null,
                default => new PostCover($previousCover->file, (string) $input->coverAlt),
            }, $now);

            if ('' !== $slug && $slug !== $post->slug()) {
                // Сначала правило Entity (адрес заблокирован), потом занятость.
                $post->changeSlug($slug, $now);
                if ($this->posts->slugExists($slug)) {
                    throw new PostSlugAlreadyTaken($slug);
                }
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            if (null !== $uploaded) {
                $this->covers->delete($uploaded);
            }

            throw match (true) {
                $e instanceof OptimisticLockException => new PostWasModified($id, $e),
                $e instanceof UniqueConstraintViolationException => new PostSlugAlreadyTaken($slug, $e),
                default => $e,
            };
        }

        // Прежний файл больше никто не показывает -- убираем после коммита.
        if (null !== $previousCover && $previousCover->file !== $post->cover()?->file) {
            $this->covers->delete($previousCover->file);
        }
    }
}
