<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Вопрос квалификационной формы с фиксированным набором ответов.
 */
final readonly class LeadQuestion
{
    /**
     * @param array<string, string> $options     ключ ответа => подпись
     * @param string|null           $otherOption ключ ответа «Другое»: при нём обязателен текст своими словами
     *                                           в поле "<ключ вопроса>_other" (подпись -- $otherLabel)
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $options,
        public ?string $otherOption = null,
        public ?string $otherLabel = null,
    ) {
    }
}
