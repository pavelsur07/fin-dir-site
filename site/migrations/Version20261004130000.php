<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Рубрики для двух статей, импортированных со старого сайта. Трогает только строки
 * без рубрики: рубрику, выставленную в админке, миграция не перезаписывает.
 */
final class Version20261004130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill publication_post.rubric for imported legacy articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE publication_post SET rubric = 'marketplejsy' WHERE slug = 'marketpleys-ili-internet-magazin' AND rubric IS NULL");
        $this->addSql("UPDATE publication_post SET rubric = 'upravlencheskij-uchet' WHERE slug = 'kak-chitat-opiu-selleru' AND rubric IS NULL");
    }

    public function down(Schema $schema): void
    {
        // Данные не откатываем: после выставления рубрики нельзя отличить её от выбранной в админке.
        $this->write('Rubric backfill is not reverted.');
    }
}
