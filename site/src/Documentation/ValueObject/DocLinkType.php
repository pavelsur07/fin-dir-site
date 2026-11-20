<?php

declare(strict_types=1);

namespace App\Documentation\ValueObject;

enum DocLinkType: string
{
    case INLINE = 'inline';
    case RELATED = 'related';
}
