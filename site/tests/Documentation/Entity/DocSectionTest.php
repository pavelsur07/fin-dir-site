<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Entity;

use App\Documentation\Entity\DocSection;
use PHPUnit\Framework\TestCase;

final class DocSectionTest extends TestCase
{
    public function testOrderOutsideRangeCannotBeStored(): void
    {
        $section = new DocSection('Раздел', 'start', 'Описание', 0, new \DateTimeImmutable('2026-01-01'));
        $this->expectException(\InvalidArgumentException::class);
        $section->edit('Раздел', 'Описание', 100001, new \DateTimeImmutable('2026-01-02'));
    }
}
