<?php

declare(strict_types=1);

namespace App\Lead\Adapter;

/**
 * Данные уведомления о новом обращении. Персональных данных нет: имя, контакт
 * и задача остаются в админке (Telegram -- трансграничная передача, 152-ФЗ).
 */
final readonly class LeadNotification
{
    /**
     * @param list<array{questionLabel: string, answerLabel: string}> $answers
     * @param array<string, string>                                   $utm
     */
    public function __construct(
        public int $leadId,
        public string $formTitle,
        public ?string $pageUrl,
        public array $answers,
        public array $utm,
    ) {
    }

    public function text(string $adminUrl): string
    {
        // HTML401: апостроф -> &#039;. Именованный &apos; (HTML5) Telegram не понимает
        // и отвечает 400 "can't parse entities" -- уведомление не ушло бы никогда.
        // Переводы строк схлопываются: значение из формы не подделает строку уведомления.
        $escape = static fn (string $value): string => htmlspecialchars((string) preg_replace('/\s+/u', ' ', $value), \ENT_QUOTES | \ENT_HTML401);

        $lines = [
            \sprintf('<b>Новое обращение №%d</b>', $this->leadId),
            'Форма: '.$escape($this->formTitle),
        ];
        if (null !== $this->pageUrl) {
            $lines[] = 'Страница: '.$escape($this->pageUrl);
        }
        foreach ($this->answers as $answer) {
            $lines[] = $escape($answer['questionLabel']).' '.$escape($answer['answerLabel']);
        }
        foreach ($this->utm as $key => $value) {
            $lines[] = $escape($key).': '.$escape($value);
        }
        $lines[] = '';
        $lines[] = '<a href="'.$escape($adminUrl).'">Открыть в админке</a>';

        return implode("\n", $lines);
    }
}
