<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add page-owned screenshots to Documentation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE documentation_image (id UUID NOT NULL, page_id INT NOT NULL, sha256 VARCHAR(64) NOT NULL, width INT NOT NULL, height INT NOT NULL, contents BYTEA NOT NULL, byte_size INT NOT NULL, referenced BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX doc_image_page_hash_uniq ON documentation_image (page_id, sha256)');
        $this->addSql('CREATE INDEX doc_image_page_order_idx ON documentation_image (page_id, created_at, id)');
        $this->addSql('ALTER TABLE documentation_image ADD CONSTRAINT FK_E7A4C408C4663E4 FOREIGN KEY (page_id) REFERENCES documentation_page (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('A rollback of application code must preserve stored screenshots.');
    }
}
