<?php

declare(strict_types=1);

namespace App\Lead\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Заметка менеджера к обращению. Создаётся только через Lead::addNote().
 */
#[ORM\Entity]
#[ORM\Table(name: 'lead_note')]
class LeadNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lead::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Lead $lead;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Lead $lead, string $text, \DateTimeImmutable $now)
    {
        $text = trim($text);
        if ('' === $text) {
            throw new \InvalidArgumentException('Note text must not be empty.');
        }

        $this->lead = $lead;
        $this->text = $text;
        $this->createdAt = $now;
    }
}
