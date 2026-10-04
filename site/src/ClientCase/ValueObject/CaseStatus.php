<?php

declare(strict_types=1);

namespace App\ClientCase\ValueObject;

enum CaseStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
