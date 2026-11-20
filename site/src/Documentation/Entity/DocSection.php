<?php

declare(strict_types=1);

namespace App\Documentation\Entity;

use App\Documentation\ValueObject\DocFields;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documentation_section')]
#[ORM\UniqueConstraint(name: 'doc_section_slug_uniq', columns: ['slug'])]
class DocSection
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 120)] private string $title;
    #[ORM\Column(length: 120)] private string $slug;
    #[ORM\Column(length: 300)] private string $description;
    #[ORM\Column] private int $position;
    #[ORM\Column] private \DateTimeImmutable $updatedAt;
    #[ORM\Column(type: 'integer'), ORM\Version] private int $version = 1;
    public function __construct(string $title, string $slug, string $description, int $position, \DateTimeImmutable $now)
    {
        $this->slug = DocFields::slug($slug, 120);
        $this->edit($title, $description, $position, $now);
    }

    public function edit(string $title, string $description, int $position, \DateTimeImmutable $now): void
    {
        $this->title = DocFields::text($title, 120);
        $this->description = DocFields::text($description, 300, false);
        $this->position = DocFields::position($position);
        $this->updatedAt = $now;
    }

    public function id(): int
    {
        return $this->id ?? throw new \LogicException('Section not persisted.');
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function version(): int
    {
        return $this->version;
    }
}
