<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DocMcpSearchInput
{
    public function __construct(
        #[Assert\Length(max: 200)] public string $q = '',
        #[Assert\Positive] public int $page = 1,
        #[Assert\Positive] public ?int $sectionId = null,
        #[Assert\Choice(['draft', 'published'])] public ?string $status = null,
    ) {
    }
}
