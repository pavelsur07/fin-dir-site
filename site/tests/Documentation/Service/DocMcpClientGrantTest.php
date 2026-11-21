<?php

declare(strict_types=1);

namespace App\Tests\Documentation\Service;

use App\Documentation\Query\DocMcpClientGrantQuery;
use App\Documentation\Service\DocMcpClientGrantEditor;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocMcpClientGrantTest extends KernelTestCase
{
    public function testGrantsAreIdempotentIssuerBoundAndRevocableWithoutCache(): void
    {
        $service = self::getContainer()->get(DocMcpClientGrantEditor::class);
        $query = self::getContainer()->get(DocMcpClientGrantQuery::class);
        $issuer = 'https://issuer.example';
        $service->allow($issuer, 'reader');
        $service->allow($issuer, 'reader');
        self::assertTrue($query->isEnabled(hash('sha256', $issuer), 'reader'));
        self::assertFalse($query->isEnabled(hash('sha256', 'https://other.example'), 'reader'));
        $service->disable($issuer, 'reader');
        $service->disable($issuer, 'unknown');
        self::assertFalse($query->isEnabled(hash('sha256', $issuer), 'reader'));
        self::assertSame(1, (int) self::getContainer()->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM documentation_mcp_client_grant'));
    }
}
