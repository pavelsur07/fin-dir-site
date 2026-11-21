<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocDraftWriteResult
{
    public function __construct(public int $pageId, public string $requestKey, public bool $replayed)
    {
    }

    /** @return array{pageId:int,requestKey:string,replayed:bool,adminEditUrl:string} */
    public function toArray(): array
    {
        return ['pageId' => $this->pageId, 'requestKey' => $this->requestKey, 'replayed' => $this->replayed, 'adminEditUrl' => 'https://vashfindir.ru/admin/docs/pages/'.$this->pageId.'/edit'];
    }
}
