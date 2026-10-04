<?php

declare(strict_types=1);

namespace App\ClientCase\Exception;

use App\Shared\Exception\Conflict;

/**
 * Кейс сохранили в другой вкладке/сессии после того, как его открыли на редактирование.
 */
final class CaseWasModified extends \DomainException implements Conflict
{
    public function __construct(public readonly int $caseId, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Case %d was modified concurrently.', $caseId), 0, $previous);
    }
}
