<?php

declare(strict_types=1);

namespace App\Documentation\Twig;

use App\Documentation\ValueObject\DocUrl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DocUrlExtension extends AbstractExtension
{
    public function __construct(private readonly DocUrl $urls)
    {
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [new TwigFunction('doc_url', $this->urls->route(...))];
    }
}
