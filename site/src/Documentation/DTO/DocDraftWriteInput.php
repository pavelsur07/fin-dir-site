<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DocDraftWriteInput
{
    public function __construct(
        #[Assert\Uuid(versions: [4])] public string $requestKey,
        #[Assert\Valid] public DocPageInput $page,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(string $requestKey, array $data): self
    {
        $page = new DocPageInput();
        foreach ($data as $key => $value) {
            $page->$key = $value;
        }

        return new self(strtolower($requestKey), $page);
    }

    public function hash(string $tool, ?int $id = null, ?int $expectedVersion = null): string
    {
        $p = $this->page;

        return hash('sha256', json_encode([$tool, $id, $expectedVersion, $p->title, $p->slug, $p->description, $p->body, $p->sectionId, $p->position, $p->metaTitle, $p->metaDescription, $p->relatedIds], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
