<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Вопрос квалификационной формы с фиксированным набором ответов.
 */
final readonly class LeadQuestion
{
    /**
     * @param array<string, string> $options ключ ответа => подпись
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $options,
    ) {
    }
}
