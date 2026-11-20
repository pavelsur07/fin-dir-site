<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Builder;

use App\Documentation\Entity\DocSection;

final class DocSectionBuilder
{
    public static function aSection(string $slug = 'getting-started'): DocSection
    {
        return new DocSection('Начало работы', $slug, 'Первые шаги', 0, new \DateTimeImmutable('2026-01-01'));
    }
}
