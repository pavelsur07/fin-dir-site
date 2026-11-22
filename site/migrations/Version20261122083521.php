<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122083521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Bitrix24 connection settings and CRM export state of leads.';
    }

    public function up(Schema $schema): void
    {
        // Одно подключение на сайт: строка с id = 1. Адрес вебхука -- только шифротекст.
        $this->addSql('CREATE TABLE bitrix24_connection (id INT NOT NULL, encrypted_webhook TEXT NOT NULL, portal VARCHAR(100) NOT NULL, mask VARCHAR(200) NOT NULL, enabled BOOLEAN NOT NULL, checked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_error VARCHAR(100) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id), CONSTRAINT bitrix24_connection_singleton CHECK (id = 1))');
        $this->addSql('ALTER TABLE lead_lead ADD crm_lead_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lead_lead ADD crm_exported_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE lead_lead ADD crm_export_error VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bitrix24_connection');
        $this->addSql('ALTER TABLE lead_lead DROP crm_lead_id');
        $this->addSql('ALTER TABLE lead_lead DROP crm_exported_at');
        $this->addSql('ALTER TABLE lead_lead DROP crm_export_error');
    }
}
