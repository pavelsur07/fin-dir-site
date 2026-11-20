<?php

declare(strict_types=1);

namespace App\Documentation\Entity;

use App\Documentation\ValueObject\DocLinkType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documentation_page_link')]
#[ORM\UniqueConstraint(name: 'doc_link_uniq', columns: ['source_id', 'target_id', 'type', 'fragment'])]
#[ORM\Index(name: 'doc_link_target_idx', columns: ['target_id', 'type'])]
class DocPageLink
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    /** @param list<string> $sourceUrls */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: DocPage::class, inversedBy: 'links'), ORM\JoinColumn(nullable: false)] private DocPage $source,
        #[ORM\ManyToOne(targetEntity: DocPage::class), ORM\JoinColumn(nullable: false)] private DocPage $target,
        #[ORM\Column(length: 16, enumType: DocLinkType::class)] private DocLinkType $type,
        #[ORM\Column(length: 512)] private string $fragment,
        #[ORM\Column] private int $position,
        #[ORM\Column(type: 'json')] private array $sourceUrls = [],
    ) {
    }

    public function key(): string
    {
        return $this->target->id().'|'.$this->type->value.'|'.$this->fragment;
    }

    public function target(): DocPage
    {
        return $this->target;
    }

    public function fragment(): string
    {
        return $this->fragment;
    }

    /** @return list<string> */
    public function sourceUrls(): array
    {
        return $this->sourceUrls;
    }

    /** @param list<string> $urls */
    public function refresh(int $position, array $urls): void
    {
        $this->position = $position;
        $this->sourceUrls = $urls;
    }
}
