<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class DocMcpPersistenceResetter
{
    /** @var array<string,string> */
    private array $settings = [];

    public function __construct(private readonly ManagerRegistry $registry, private readonly Connection $db)
    {
    }

    public function reset(): void
    {
        $em = $this->registry->getManager();
        if ($em instanceof EntityManagerInterface && !$em->isOpen()) {
            $em = $this->registry->resetManager();
        }
        $em->clear();
        $db = $this->db;
        foreach ($this->settings as $name => $value) {
            $db->executeQuery('SELECT set_config(:name,:value,false)', ['name' => $name, 'value' => $value]);
        }
        $this->settings = [];
    }

    public function prepare(bool $write): void
    {
        $this->reset();
        $db = $this->db;
        foreach (['statement_timeout' => $write ? '15s' : '10s', 'lock_timeout' => '5s'] as $name => $value) {
            $this->settings[$name] = (string) $db->fetchOne('SELECT current_setting(:name)', ['name' => $name]);
            $db->executeQuery('SELECT set_config(:name,:value,false)', ['name' => $name, 'value' => $value]);
        }
    }
}
