<?php

declare(strict_types=1);

namespace App\Lead\Adapter;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Абсолютная ссылка на карточку обращения в админке -- для внешних систем. */
final readonly class LeadAdminLink
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%vf.site_url%')] private string $siteUrl,
    ) {
    }

    public function forLead(int $id): string
    {
        return $this->siteUrl.$this->urlGenerator->generate('admin_lead_show', ['id' => $id]);
    }
}
