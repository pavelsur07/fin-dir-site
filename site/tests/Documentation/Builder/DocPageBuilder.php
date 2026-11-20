<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Builder;

use App\Documentation\Entity\DocPage;
use App\Documentation\Entity\DocSection;

final class DocPageBuilder
{
    public static function aPage(DocSection $section, string $slug = 'first-report', bool $published = false, int $position = 0): DocPage
    {
        $now = new \DateTimeImmutable('2026-01-01');
        $page = new DocPage($slug, $now);
        $page->edit($section, 'Первый отчёт', $slug, 'Как получить отчёт', "## Настройка\n\nЗагрузка операций и отчёты.", 'Загрузка операций и отчёты.', $position, null, null, $now);
        if ($published) {
            $page->publish($now);
        }

        return $page;
    }
}
