<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocImageUpload
{
    public const int MAX_INPUT_BYTES = 8 * 1024 * 1024;

    public function __construct(public string $id, public int $width, public int $height)
    {
    }

    /** @return array{id:string,mimeType:string,width:int,height:int,markdownDestination:string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mimeType' => 'image/webp',
            'width' => $this->width,
            'height' => $this->height,
            'markdownDestination' => 'doc-image:'.$this->id,
        ];
    }
}
