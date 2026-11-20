<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Service;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocMigrationsTest extends KernelTestCase
{
    public function testMigrationRoundTripInIsolatedTestSchema(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $db = DriverManager::getConnection($em->getConnection()->getParams());
        self::assertStringEndsWith('_test', (string) $db->getDatabase());
        $schema = 'doc_roundtrip_'.bin2hex(random_bytes(6));
        $db->beginTransaction();
        try {
            $db->executeStatement('CREATE SCHEMA '.$schema);
            $db->executeStatement('SET LOCAL search_path TO '.$schema);
            $versions = ['Version20261120133344', 'Version20261120140000', 'Version20261120150000'];
            $factory = function (string $version) use ($db): AbstractMigration {
                require_once dirname(__DIR__, 3).'/migrations/'.$version.'.php';
                $class = 'DoctrineMigrations\\'.$version;
                self::assertTrue(is_a($class, AbstractMigration::class, true));

                return new $class($db, new NullLogger());
            };
            foreach ($versions as $version) {
                $migration = $factory($version);
                $migration->up(new Schema());
                foreach ($migration->getSql() as $sql) {
                    $db->executeStatement($sql->getStatement(), $sql->getParameters(), $sql->getTypes());
                }
            }
            self::assertSame(3, (int) $db->fetchOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:schema', ['schema' => $schema]));
            self::assertSame('json', $db->fetchOne("SELECT data_type FROM information_schema.columns WHERE table_schema=:schema AND table_name='documentation_page_link' AND column_name='source_urls'", ['schema' => $schema]));
            foreach (array_reverse($versions) as $version) {
                $migration = $factory($version);
                $migration->down(new Schema());
                foreach ($migration->getSql() as $sql) {
                    $db->executeStatement($sql->getStatement(), $sql->getParameters(), $sql->getTypes());
                }
            }
            self::assertSame(0, (int) $db->fetchOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:schema', ['schema' => $schema]));
        } finally {
            $db->rollBack();
            $db->close();
        }
    }
}
