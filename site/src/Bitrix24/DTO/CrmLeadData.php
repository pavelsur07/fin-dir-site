<?php

declare(strict_types=1);

namespace App\Bitrix24\DTO;

/**
 * Данные лида для CRM, уже собранные модулем-владельцем обращения.
 * originId -- стабильный идентификатор источника: по нему повтор находит уже созданный лид.
 */
final readonly class CrmLeadData
{
    /**
     * @param array<string, string> $utm      ключи utm_source…utm_term
     * @param list<string>          $comments строки комментария, без разметки
     */
    public function __construct(
        public string $originId,
        public string $title,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $telegram,
        public array $comments,
        public array $utm,
    ) {
    }
}
