<?php

declare(strict_types=1);

namespace App\Documentation\Entity;

use App\Documentation\Exception\DocConflict;
use App\Documentation\ValueObject\DocFields;
use App\Documentation\ValueObject\DocLinkType;
use App\Documentation\ValueObject\DocPageStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documentation_page')]
#[ORM\UniqueConstraint(name: 'doc_page_slug_uniq', columns: ['slug'])]
#[ORM\Index(name: 'doc_page_order_idx', columns: ['section_id', 'status', 'position', 'id'])]
class DocPage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: DocSection::class), ORM\JoinColumn(nullable: false)] private DocSection $section;
    #[ORM\Column(length: 200)] private string $title;
    #[ORM\Column(length: 180)] private string $slug;
    #[ORM\Column(length: 300)] private string $description;
    #[ORM\Column(type: 'text')] private string $body;
    #[ORM\Column(type: 'text')] private string $searchText;
    #[ORM\Column] private int $position;
    #[ORM\Column(length: 70, nullable: true)] private ?string $metaTitle;
    #[ORM\Column(length: 170, nullable: true)] private ?string $metaDescription;
    #[ORM\Column(length: 16, enumType: DocPageStatus::class)] private DocPageStatus $status = DocPageStatus::DRAFT;
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column] private \DateTimeImmutable $updatedAt;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $publishedAt = null;
    #[ORM\Column(type: 'integer'), ORM\Version] private int $version = 1;
    /** @var Collection<int,DocPageLink> */
    #[ORM\OneToMany(targetEntity: DocPageLink::class, mappedBy: 'source', cascade: ['persist'], orphanRemoval: true)]
    private Collection $links;

    public function __construct(string $slug, \DateTimeImmutable $now)
    {
        $this->slug = DocFields::slug($slug, 180);
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->links = new ArrayCollection();
    }

    public function edit(DocSection $section, string $title, string $slug, string $description, string $body, string $searchText, int $position, ?string $metaTitle, ?string $metaDescription, \DateTimeImmutable $now): void
    {
        if (null !== $this->publishedAt && $slug !== $this->slug) {
            throw new DocConflict('Адрес после первой публикации менять нельзя.');
        }
        $this->slug = DocFields::slug($slug, 180);
        $this->title = DocFields::text($title, 200);
        $this->description = DocFields::text($description, 300, false);
        if (mb_strlen($body) > 100000) {
            throw new DocConflict('Текст длиннее 100000 символов.');
        }
        $this->body = $body;
        $this->searchText = $searchText;
        $this->position = DocFields::position($position);
        $this->metaTitle = null === $metaTitle ? null : DocFields::text($metaTitle, 70, false);
        $this->metaDescription = null === $metaDescription ? null : DocFields::text($metaDescription, 170, false);
        $this->section = $section;
        $this->updatedAt = $now;
        if ($this->isPublished()) {
            $this->requireContent();
        }
    }

    public function publish(\DateTimeImmutable $now): void
    {
        if ($this->isPublished()) {
            throw new DocConflict('Страница уже опубликована.');
        }
        $this->requireContent();
        $this->status = DocPageStatus::PUBLISHED;
        $this->publishedAt ??= $now;
        $this->updatedAt = $now;
    }

    public function unpublish(\DateTimeImmutable $now): void
    {
        if (!$this->isPublished()) {
            throw new DocConflict('Страница уже в черновиках.');
        }
        $this->status = DocPageStatus::DRAFT;
        $this->updatedAt = $now;
    }

    private function requireContent(): void
    {
        if ('' === trim($this->body) || '' === trim($this->description)) {
            throw new DocConflict('Для публикации заполните текст и описание.');
        }
    }

    /** @param list<array{target:DocPage,type:DocLinkType,fragment:string,position:int,urls:list<string>}> $desired */
    public function replaceLinks(array $desired): void
    {
        $keys = [];
        $related = [];
        foreach ($desired as $item) {
            $target = $item['target'];
            if (DocLinkType::RELATED === $item['type']) {
                if ($target === $this || isset($related[$target->id()])) {
                    throw new DocConflict('В «Читайте также» нужны разные страницы, кроме текущей.');
                }
                $related[$target->id()] = true;
            }
            $key = $target->id().'|'.$item['type']->value.'|'.$item['fragment'];
            $previousUrls = $keys[$key]['urls'] ?? [];
            $keys[$key] = $item;
            $keys[$key]['urls'] = array_values(array_unique(array_merge($previousUrls, $item['urls'])));
        }
        if (count($related) > 5) {
            throw new DocConflict('В «Читайте также» можно выбрать до пяти страниц.');
        }
        foreach ($this->links->toArray() as $link) {
            if (!isset($keys[$link->key()])) {
                $this->links->removeElement($link);
            } else {
                $link->refresh($keys[$link->key()]['position'], $keys[$link->key()]['urls']);
                unset($keys[$link->key()]);
            }
        }
        foreach ($keys as $item) {
            $this->links->add(new DocPageLink($this, $item['target'], $item['type'], $item['fragment'], $item['position'], $item['urls']));
        }
    }

    /** @return Collection<int,DocPageLink> */
    public function links(): Collection
    {
        return $this->links;
    }

    public function id(): int
    {
        return $this->id ?? throw new \LogicException('Page not persisted.');
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function isPublished(): bool
    {
        return DocPageStatus::PUBLISHED === $this->status;
    }

    public function version(): int
    {
        return $this->version;
    }
}
