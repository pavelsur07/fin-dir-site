<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Необязательный заголовок формы вопроса под статьёй. Только добавление nullable-колонки:
 * существующие строки не меняются, откат удаляет пустую колонку.
 */
final class Version20260930200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add publication_post.form_title (custom article question form title)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_post ADD form_title VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_post DROP form_title');
    }
}
