<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Источник заявки (Stage 9): две nullable-колонки и индекс, существующие строки
 * не затрагиваются.
 */
final class Version20260923132723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attribution and ym_client_id to lead_lead';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lead_lead ADD attribution JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE lead_lead ADD ym_client_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE INDEX lead_lead_ym_client_idx ON lead_lead (ym_client_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX lead_lead_ym_client_idx');
        $this->addSql('ALTER TABLE lead_lead DROP attribution');
        $this->addSql('ALTER TABLE lead_lead DROP ym_client_id');
    }
}
