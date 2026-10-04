<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Версия кейса для оптимистичной блокировки. Существующие строки получают 1;
 * DEFAULT 1 оставлен, потому что именно его Doctrine ожидает у колонки #[Version].
 */
final class Version20261004160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add client_case.version for optimistic locking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_case ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_case DROP version');
    }
}
