<?php

declare(strict_types=1);

namespace App\Documentation\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documentation_image')]
#[ORM\UniqueConstraint(name: 'doc_image_page_hash_uniq', columns: ['page_id', 'sha256'])]
#[ORM\Index(name: 'doc_image_page_order_idx', columns: ['page_id', 'created_at', 'id'])]
final class DocImage
{
    #[ORM\Column]
    private bool $referenced = false;

    #[ORM\Column]
    private int $byteSize;

    public function __construct(
        #[ORM\Id, ORM\Column(type: Types::GUID)] private string $id,
        #[ORM\ManyToOne(targetEntity: DocPage::class), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private DocPage $page,
        #[ORM\Column(length: 64)] private string $sha256,
        #[ORM\Column] private int $width,
        #[ORM\Column] private int $height,
        #[ORM\Column(type: Types::BINARY)] private string $contents,
        #[ORM\Column] private \DateTimeImmutable $createdAt,
    ) {
        $this->byteSize = strlen($contents);
    }
}
