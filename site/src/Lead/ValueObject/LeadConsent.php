<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Версия текста согласия и политики конфиденциальности, на которую согласился
 * человек. Менять вместе с текстом /privacy и /consent.
 */
final class LeadConsent
{
    public const string VERSION = '2026-09-23';
}
