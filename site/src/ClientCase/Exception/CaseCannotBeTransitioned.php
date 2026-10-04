<?php

declare(strict_types=1);

namespace App\ClientCase\Exception;

use App\ClientCase\ValueObject\CaseStatus;
use App\Shared\Exception\Conflict;

final class CaseCannotBeTransitioned extends \DomainException implements Conflict
{
    public function __construct(
        public readonly ?int $caseId,
        public readonly CaseStatus $from,
        public readonly CaseStatus $to,
        string $reason = 'transition is not allowed',
    ) {
        parent::__construct(\sprintf('Case %s cannot go from %s to %s: %s.', $caseId ?? 'new', $from->value, $to->value, $reason));
    }
}
