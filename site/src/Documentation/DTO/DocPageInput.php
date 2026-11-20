<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

use App\Documentation\ValueObject\DocFields;
use Symfony\Component\Validator\Constraints as Assert;

final class DocPageInput
{
    #[Assert\NotBlank,Assert\Length(max: 200)] public string $title = '';
    #[Assert\NotBlank,Assert\Length(max: 180),Assert\Regex(pattern: DocFields::SLUG_PATTERN)] public string $slug = '';
    #[Assert\Length(max: 300)] public string $description = '';
    #[Assert\Length(max: 100000)] public string $body = '';
    #[Assert\Positive] public int $sectionId = 0;
    #[Assert\Range(min: 0, max: 100000)] public int $position = 0;
    #[Assert\Length(max: 70)] public ?string $metaTitle = null;
    #[Assert\Length(max: 170)] public ?string $metaDescription = null;
    /** @var list<int> */ #[Assert\Count(max: 5),Assert\All([new Assert\Positive()])] public array $relatedIds = [];
    #[Assert\Length(max: 5000)] public string $relatedLinks = '';
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
        $input->sectionId = (int) $data['section_id'];
        $input->body = (string) $data['body'];
        $input->metaTitle = null === $data['meta_title'] ? null : (string) $data['meta_title'];
        $input->metaDescription = null === $data['meta_description'] ? null : (string) $data['meta_description'];

        return $input;
    }
}
