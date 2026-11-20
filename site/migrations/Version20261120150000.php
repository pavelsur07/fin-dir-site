<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind original CommonMark URLs to Documentation page IDs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE documentation_page_link ADD source_urls JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE documentation_page_link ALTER source_urls DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE documentation_page_link DROP source_urls');
    }
}
