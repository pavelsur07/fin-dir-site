<?php

declare(strict_types=1);

namespace App\ClientCase\Service;

use App\ClientCase\Entity\ClientCase;
use App\ClientCase\Repository\ClientCaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Переходы статуса кейса. Правила переходов -- в ClientCase, здесь только сценарий и flush.
 */
final class ClientCaseStatusChanger
{
    public function __construct(
        private readonly ClientCaseRepository $cases,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function publish(int $id): void
    {
        $this->apply($id, fn (ClientCase $case) => $case->publish($this->clock->now()));
    }

    public function unpublish(int $id): void
    {
        $this->apply($id, fn (ClientCase $case) => $case->unpublish($this->clock->now()));
    }

    public function archive(int $id): void
    {
        $this->apply($id, fn (ClientCase $case) => $case->archive($this->clock->now()));
    }

    public function restore(int $id): void
    {
        $this->apply($id, fn (ClientCase $case) => $case->restore($this->clock->now()));
    }

    /**
     * @param \Closure(ClientCase): void $transition
     */
    private function apply(int $id, \Closure $transition): void
    {
        $transition($this->cases->get($id));
        $this->entityManager->flush();
    }
}
