<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Полное удаление обращения с заметками -- по запросу субъекта ПД (152-ФЗ).
 */
final class LeadEraser
{
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function erase(int $id): void
    {
        $this->leads->remove($this->leads->get($id));
        $this->entityManager->flush();
    }
}
