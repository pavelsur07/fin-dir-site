<?php

declare(strict_types=1);

namespace App\Lead\Twig;

use App\Lead\ValueObject\LeadFormCatalog;
use App\Lead\ValueObject\LeadFormDefinition;
use Twig\Attribute\AsTwigFunction;

/**
 * Виджет формы берёт вопросы из того же каталога, по которому сервер проверяет ответы.
 */
final class LeadFormExtension
{
    #[AsTwigFunction('lead_form')]
    public function leadForm(string $key): LeadFormDefinition
    {
        return LeadFormCatalog::get($key);
    }
}
