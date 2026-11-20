<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Exception\DocConflict;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

final class DocWriteTransaction
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @template T
     *
     * @param callable():T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed
    {
        try {
            return $this->em->wrapInTransaction(function () use ($operation) {
                // All Documentation writes use the same transaction lock. No public reader waits.
                $this->em->getConnection()->executeQuery('SELECT pg_advisory_xact_lock(1146045267, 1)');

                return $operation(); // wrapInTransaction flushes exactly once, then commits.
            });
        } catch (OptimisticLockException $e) {
            throw new DocConflict('Форма устарела. Обновите страницу.', 0, $e);
        } catch (UniqueConstraintViolationException $e) {
            throw new DocConflict('Этот адрес уже занят.', 0, $e);
        }
    }
}
