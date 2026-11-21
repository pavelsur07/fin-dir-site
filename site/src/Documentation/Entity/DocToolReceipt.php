<?php

declare(strict_types=1);

namespace App\Documentation\Entity;

use App\Documentation\Exception\DocToolFailure;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documentation_tool_receipt')]
#[ORM\UniqueConstraint(name: 'doc_tool_receipt_key_uniq', columns: ['actor', 'request_key'])]
class DocToolReceipt
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    public function __construct(
        #[ORM\Column(length: 64)] private string $actor,
        #[ORM\Column(length: 36)] private string $requestKey,
        #[ORM\Column(length: 64)] private string $tool,
        #[ORM\Column(length: 64)] private string $payloadHash,
        #[ORM\ManyToOne(targetEntity: DocPage::class), ORM\JoinColumn(nullable: false)] private DocPage $page,
        #[ORM\Column] private \DateTimeImmutable $createdAt,
    ) {
    }

    public function replay(string $tool, string $hash): int
    {
        if ($this->tool !== $tool || !hash_equals($this->payloadHash, $hash)) {
            throw new DocToolFailure('IDEMPOTENCY_CONFLICT', 'Этот ключ уже использован для другого запроса.');
        }

        return $this->page->id();
    }
}
