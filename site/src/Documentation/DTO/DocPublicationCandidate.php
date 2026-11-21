<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

final readonly class DocPublicationCandidate
{
    /**
     * @param list<array{id:int,status:string,body:string,fragment:string,slug?:string,urls?:list<string>}> $targets
     * @param list<array{sourceId:int,fragment:string}>                                                     $incomingAnchors
     * @param list<string>                                                                                  $imageIds
     */
    public function __construct(public int $id, public int $version, public string $description, public string $body, public array $targets, public array $incomingAnchors = [], public array $imageIds = [])
    {
    }
}
