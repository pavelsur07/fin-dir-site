<?php

declare(strict_types=1);

namespace App\Publication\Entity;

use App\Publication\Exception\PostCannotBeTransitioned;
use App\Publication\Exception\PostSlugIsLocked;
use App\Publication\ValueObject\PostSlug;
use App\Publication\ValueObject\PostStatus;
use App\Publication\ValueObject\PostTitle;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'publication_post')]
#[ORM\UniqueConstraint(name: 'publication_post_slug_uniq', columns: ['slug'])]
#[ORM\Index(name: 'publication_post_status_idx', columns: ['status'])]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: PostTitle::MAX_LENGTH)]
    private string $title;

    #[ORM\Column(length: PostSlug::MAX_LENGTH)]
    private string $slug;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $excerpt;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(length: 16, enumType: PostStatus::class)]
    private PostStatus $status = PostStatus::DRAFT;

    #[ORM\Column(length: 70, nullable: true)]
    private ?string $metaTitle;

    #[ORM\Column(length: 170, nullable: true)]
    private ?string $metaDescription;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** Дата первой публикации. При снятии с публикации сохраняется. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private int $version = 1;

    public function __construct(
        string $title,
        string $slug,
        ?string $excerpt,
        string $body,
        ?string $metaTitle,
        ?string $metaDescription,
        \DateTimeImmutable $now,
    ) {
        $this->title = self::requireTitle($title);
        $this->slug = self::requireSlug($slug);
        $this->excerpt = $excerpt;
        $this->body = $body;
        $this->metaTitle = $metaTitle;
        $this->metaDescription = $metaDescription;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function edit(
        string $title,
        ?string $excerpt,
        string $body,
        ?string $metaTitle,
        ?string $metaDescription,
        \DateTimeImmutable $now,
    ): void {
        if (PostStatus::PUBLISHED === $this->status && '' === trim($body)) {
            throw new \InvalidArgumentException('Published post body must not be empty.');
        }

        $this->title = self::requireTitle($title);
        $this->excerpt = $excerpt;
        $this->body = $body;
        $this->metaTitle = $metaTitle;
        $this->metaDescription = $metaDescription;
        $this->updatedAt = $now;
    }

    public function changeSlug(string $slug, \DateTimeImmutable $now): void
    {
        if ($slug === $this->slug) {
            return;
        }

        if (null !== $this->publishedAt) {
            throw new PostSlugIsLocked($this->id);
        }

        $this->slug = self::requireSlug($slug);
        $this->updatedAt = $now;
    }

    public function publish(\DateTimeImmutable $now): void
    {
        if (!$this->beginTransition(PostStatus::PUBLISHED)) {
            return;
        }

        if ('' === trim($this->body)) {
            throw new PostCannotBeTransitioned($this->id, $this->status, PostStatus::PUBLISHED, 'body is empty');
        }

        $this->status = PostStatus::PUBLISHED;
        $this->publishedAt ??= $now;
        $this->updatedAt = $now;
    }

    public function unpublish(\DateTimeImmutable $now): void
    {
        // ARCHIVED → DRAFT разрешён, но это restore(), а не снятие с публикации.
        if (PostStatus::ARCHIVED === $this->status) {
            throw new PostCannotBeTransitioned($this->id, $this->status, PostStatus::DRAFT, 'use restore');
        }

        $this->transitionTo(PostStatus::DRAFT, $now);
    }

    public function archive(\DateTimeImmutable $now): void
    {
        $this->transitionTo(PostStatus::ARCHIVED, $now);
    }

    /** Из архива статья возвращается черновиком: публиковать её снова -- отдельное решение. */
    public function restore(\DateTimeImmutable $now): void
    {
        if (PostStatus::PUBLISHED === $this->status) {
            throw new PostCannotBeTransitioned($this->id, $this->status, PostStatus::DRAFT, 'use unpublish');
        }

        $this->transitionTo(PostStatus::DRAFT, $now);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function status(): PostStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }

    private function transitionTo(PostStatus $target, \DateTimeImmutable $now): void
    {
        if (!$this->beginTransition($target)) {
            return;
        }

        $this->status = $target;
        $this->updatedAt = $now;
    }

    /**
     * Повтор перехода в текущий статус -- no-op: повторный POST ничего не ломает.
     *
     * @return bool false, если статья уже в целевом статусе
     */
    private function beginTransition(PostStatus $target): bool
    {
        if ($target === $this->status) {
            return false;
        }

        if (!$this->status->canTransitionTo($target)) {
            throw new PostCannotBeTransitioned($this->id, $this->status, $target);
        }

        return true;
    }

    private static function requireTitle(string $title): string
    {
        $title = trim($title);

        if ('' === $title || mb_strlen($title) > PostTitle::MAX_LENGTH) {
            throw new \InvalidArgumentException('Post title must be 1..200 characters.');
        }

        return $title;
    }

    private static function requireSlug(string $slug): string
    {
        if (!PostSlug::isValid($slug)) {
            throw new \InvalidArgumentException(\sprintf('Invalid post slug "%s".', $slug));
        }

        return $slug;
    }
}
