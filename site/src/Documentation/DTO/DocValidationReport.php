<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocValidationReport
{
    /** @param list<array{code:string,message:string,targetId?:int}> $issues */
    public function __construct(public int $pageId, public int $version, public array $issues, public \DateTimeImmutable $checkedAt)
    {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['pageId' => $this->pageId, 'version' => $this->version, 'readyToPublish' => [] === $this->issues, 'issues' => $this->issues, 'checkedAt' => $this->checkedAt->format(DATE_ATOM)];
    }
}
