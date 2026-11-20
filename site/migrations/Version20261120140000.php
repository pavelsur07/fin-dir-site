<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Constrain Documentation status, link type and positions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE documentation_page ADD CONSTRAINT doc_page_status_check CHECK (status IN ('draft','published'))");
        $this->addSql('ALTER TABLE documentation_page ADD CONSTRAINT doc_page_position_check CHECK (position BETWEEN 0 AND 100000)');
        $this->addSql('ALTER TABLE documentation_section ADD CONSTRAINT doc_section_position_check CHECK (position BETWEEN 0 AND 100000)');
        $this->addSql("ALTER TABLE documentation_page_link ADD CONSTRAINT doc_link_type_check CHECK (type IN ('inline','related'))");
        $this->addSql('ALTER TABLE documentation_page_link ADD CONSTRAINT doc_link_position_check CHECK (position >= 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE documentation_page DROP CONSTRAINT doc_page_status_check, DROP CONSTRAINT doc_page_position_check');
        $this->addSql('ALTER TABLE documentation_section DROP CONSTRAINT doc_section_position_check');
        $this->addSql('ALTER TABLE documentation_page_link DROP CONSTRAINT doc_link_type_check, DROP CONSTRAINT doc_link_position_check');
    }
}
