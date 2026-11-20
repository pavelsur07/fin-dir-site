<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

use App\Documentation\ValueObject\DocFields;
use Symfony\Component\Validator\Constraints as Assert;

final class DocSectionInput
{
    #[Assert\NotBlank,Assert\Length(max: 120)] public string $title = '';
    #[Assert\NotBlank,Assert\Length(max: 120),Assert\Regex(pattern: DocFields::SLUG_PATTERN)] public string $slug = '';
    #[Assert\Length(max: 300)] public string $description = '';
    #[Assert\Range(min: 0, max: 100000)] public int $position = 0;
    #[Assert\Positive] public ?int $version = null;
    /** @param array<string,mixed> $data */
    public static function fromData(array $data): self
    {
        $input = new self();
        $input->title = (string) $data['title'];
        $input->slug = (string) $data['slug'];
        $input->description = (string) $data['description'];
        $input->position = (int) $data['position'];
        $input->version = (int) $data['version'];

        return $input;
    }
}
