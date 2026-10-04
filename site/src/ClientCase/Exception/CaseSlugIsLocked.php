<?php

declare(strict_types=1);

namespace App\ClientCase\Exception;

use App\Shared\Exception\Conflict;

final class CaseSlugIsLocked extends \DomainException implements Conflict
{
    public function __construct(public readonly ?int $caseId)
    {
        parent::__construct(\sprintf('Case %s was published: its slug cannot be changed.', $caseId ?? 'new'));
    }
}
