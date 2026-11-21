<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

final readonly class DocMarkdownAnalysis
{
    /** @param list<array{url:string,id:?int,slug:?string,fragment:string}> $links
     * @param list<array{id:string,title:string}> $headings
     * @param list<array{id:string,alt:string}> $images */
    public function __construct(public array $links, public array $headings, public string $plainText, public array $images)
    {
    }
}
