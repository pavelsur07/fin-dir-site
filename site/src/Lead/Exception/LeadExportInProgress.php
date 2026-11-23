<?php

declare(strict_types=1);

namespace App\Lead\Exception;

use App\Shared\Exception\Conflict;

/**
 * Обращение прямо сейчас передаётся в CRM: удалять его нельзя, иначе созданный в CRM
 * лид останется без записи о номере в журнале уничтожения. Повторить чуть позже.
 */
final class LeadExportInProgress extends \DomainException implements Conflict
{
    public function __construct(public readonly int $leadId)
    {
        parent::__construct(\sprintf('Lead %d is being exported to CRM.', $leadId));
    }
}
