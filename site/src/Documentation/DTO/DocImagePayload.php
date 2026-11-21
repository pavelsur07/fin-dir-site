<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocImagePayload
{
    public function __construct(public string $contents, public string $sha256)
    {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        $contents = is_resource($row['contents']) ? stream_get_contents($row['contents']) : $row['contents'];
        if (!is_string($contents) || '' === $contents) {
            throw new \UnexpectedValueException('Stored screenshot is unreadable.');
        }

        return new self($contents, (string) $row['sha256']);
    }
}
