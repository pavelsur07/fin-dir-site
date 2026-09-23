<?php

declare(strict_types=1);

namespace App\Tests\Publication;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Data-миграция кладёт в site_test перенесённую статью post-1. Тестам с точными
 * счётчиками нужна пустая таблица: DELETE идёт внутри транзакции DAMA и откатывается.
 */
trait PostTableCleaner
{
    private static function clearPosts(EntityManagerInterface $entityManager): void
    {
        $entityManager->getConnection()->executeStatement('DELETE FROM publication_post');
    }
}
