<?php

declare(strict_types=1);

namespace App\Tests\Publication\Builder;

use App\Publication\Entity\Post;
use App\Publication\ValueObject\PostStatus;

/**
 * Валидная статья с безопасными значениями по умолчанию. Время задаётся явно --
 * build() не читает системные часы.
 */
final class PostBuilder
{
    private string $title = 'Как считать unit-экономику';
    private string $slug = 'kak-schitat-unit-ekonomiku';
    private string $excerpt = 'Анонс статьи.';
    private string $body = "## Введение\n\nТекст статьи.";
    private \DateTimeImmutable $createdAt;
    private PostStatus $status = PostStatus::DRAFT;
    private ?\DateTimeImmutable $publishedAt = null;

    private function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('2026-01-10 10:00:00');
    }

    public static function aPost(): self
    {
        return new self();
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function withSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function withBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function createdAt(string $at): self
    {
        $this->createdAt = new \DateTimeImmutable($at);

        return $this;
    }

    public function published(string $at = '2026-01-15 12:00:00'): self
    {
        $this->status = PostStatus::PUBLISHED;
        $this->publishedAt = new \DateTimeImmutable($at);

        return $this;
    }

    public function archived(): self
    {
        $this->status = PostStatus::ARCHIVED;

        return $this;
    }

    public function build(): Post
    {
        $post = new Post($this->title, $this->slug, $this->excerpt, $this->body, null, null, $this->createdAt);

        // Состояние собирается штатными переходами Entity, а не reflection:
        // builder не обходит бизнес-правила.
        if (null !== $this->publishedAt) {
            $post->publish($this->publishedAt);
        }
        if (PostStatus::ARCHIVED === $this->status) {
            $post->archive($this->publishedAt ?? $this->createdAt);
        }

        return $post;
    }
}
