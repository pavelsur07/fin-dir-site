<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Обложка статьи: имя файла в public/uploads/posts и alt. Обе колонки nullable --
 * существующие статьи остаются без обложки и показывают заглушку.
 */
final class Version20261012165405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add publication_post.cover_file and cover_alt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_post ADD cover_file VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE publication_post ADD cover_alt VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_post DROP cover_file');
        $this->addSql('ALTER TABLE publication_post DROP cover_alt');
    }
}
