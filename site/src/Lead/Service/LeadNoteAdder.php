<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class LeadNoteAdder
{
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(int $id, string $text): void
    {
        $this->leads->get($id)->addNote($text, $this->clock->now());
        $this->entityManager->flush();
    }
}
