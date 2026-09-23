<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

enum ContactType: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case TELEGRAM = 'telegram';
    case OTHER = 'other';
}
