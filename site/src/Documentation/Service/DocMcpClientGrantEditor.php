<?php

declare(strict_types=1);

namespace App\Documentation\Service;

use App\Documentation\Entity\DocMcpClientGrant;
use App\Documentation\Repository\DocMcpClientGrantRepository;
use Psr\Clock\ClockInterface;

final class DocMcpClientGrantEditor
{
    public function __construct(private readonly DocMcpClientGrantRepository $grants, private readonly DocWriteTransaction $transaction, private readonly ClockInterface $clock)
    {
    }

    public function allow(string $issuer, string $clientId): void
    {
        $this->transaction->run(function () use ($issuer, $clientId): void {
            $grant = $this->grants->find(hash('sha256', $issuer), $clientId) ?? new DocMcpClientGrant($issuer, $clientId, $this->clock->now());
            $grant->allow($this->clock->now());
            $this->grants->save($grant);
        });
    }

    public function disable(string $issuer, string $clientId): void
    {
        $this->transaction->run(function () use ($issuer, $clientId): void {
            $grant = $this->grants->find(hash('sha256', $issuer), $clientId);
            $grant?->disable($this->clock->now());
        });
    }
}
