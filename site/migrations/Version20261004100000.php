<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Рубрика статьи «Газеты». Только добавление nullable-колонки: существующие строки
 * не меняются (остаются без рубрики), откат удаляет колонку.
 */
final class Version20261004100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add publication_post.rubric (article rubric from a fixed list)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_post ADD rubric VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE INDEX publication_post_rubric_idx ON publication_post (rubric)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX publication_post_rubric_idx');
        $this->addSql('ALTER TABLE publication_post DROP rubric');
    }
}
