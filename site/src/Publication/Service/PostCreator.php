<?php

declare(strict_types=1);

namespace App\Publication\Service;

use App\Publication\Adapter\PostCoverStorage;
use App\Publication\DTO\PostInput;
use App\Publication\Entity\Post;
use App\Publication\Exception\PostSlugAlreadyTaken;
use App\Publication\Repository\PostRepository;
use App\Publication\ValueObject\PostCover;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class PostCreator
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly PostSlugger $slugger,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        private readonly PostCoverStorage $covers,
    ) {
    }

    /**
     * Создаёт черновик.
     *
     * @return int id новой статьи
     */
    public function create(PostInput $input): int
    {
        $slug = $this->resolveSlug($input);
        $now = $this->clock->now();

        // Как в PostEditor: файл до flush, при неудаче -- удалить.
        $uploaded = null === $input->coverUpload ? null : $this->covers->store($input->coverUpload);

        try {
            $post = new Post(
                $input->title,
                $slug,
                $input->excerpt,
                $input->body,
                $input->metaTitle,
                $input->metaDescription,
                $now,
                $input->formTitle,
                $input->rubric,
            );
            if (null !== $uploaded) {
                $post->changeCover(new PostCover($uploaded, (string) $input->coverAlt), $now);
            }
            $this->posts->save($post);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            if (null !== $uploaded) {
                $this->covers->delete($uploaded);
            }

            // Гонка между проверкой slugExists() и вставкой -- ловит уникальный индекс.
            throw $e instanceof UniqueConstraintViolationException ? new PostSlugAlreadyTaken($slug, $e) : $e;
        }

        return (int) $post->id();
    }

    /**
     * Явно заданный slug обязан быть свободен. Сгенерированный из заголовка
     * получает суффикс -2, -3... -- контент-менеджеру не нужно думать об URL.
     */
    private function resolveSlug(PostInput $input): string
    {
        $explicit = trim((string) $input->slug);

        if ('' !== $explicit) {
            if ($this->posts->slugExists($explicit)) {
                throw new PostSlugAlreadyTaken($explicit);
            }

            return $explicit;
        }

        $base = $this->slugger->slugify($input->title);
        if ('' === $base) {
            $base = 'post';
        }

        $slug = $base;
        for ($suffix = 2; $this->posts->slugExists($slug); ++$suffix) {
            $slug = $this->slugger->withSuffix($base, $suffix);
        }

        return $slug;
    }
}
