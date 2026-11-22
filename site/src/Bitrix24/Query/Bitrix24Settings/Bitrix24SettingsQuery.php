<?php

declare(strict_types=1);

namespace App\Bitrix24\Query\Bitrix24Settings;

use App\Bitrix24\Entity\Bitrix24Connection;
use App\Bitrix24\ValueObject\Bitrix24FailureReason;
use Doctrine\ORM\EntityManagerInterface;

final class Bitrix24SettingsQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(): Bitrix24SettingsView
    {
        /** @var ?array{portal: string, mask: string, enabled: bool, checkedAt: ?\DateTimeImmutable, lastError: ?string, updatedAt: \DateTimeImmutable} $row */
        $row = $this->entityManager->createQueryBuilder()
            ->select('c.portal, c.mask, c.enabled, c.checkedAt, c.lastError, c.updatedAt')
            ->from(Bitrix24Connection::class, 'c')
            ->where('c.id = :id')
            ->setParameter('id', Bitrix24Connection::SINGLETON_ID)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $row) {
            return new Bitrix24SettingsView(false);
        }

        return new Bitrix24SettingsView(true, $row['portal'], $row['mask'], $row['enabled'], $row['checkedAt'], $row['lastError'], $row['updatedAt'], null === $row['lastError'] ? null : Bitrix24FailureReason::label($row['lastError']));
    }
}
