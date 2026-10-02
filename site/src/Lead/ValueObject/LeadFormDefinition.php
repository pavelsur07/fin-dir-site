<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Описание формы: простая (без вопросов) или квалификационная (с вопросами).
 * Одно определение рендерит виджет и проверяет ответы на сервере.
 */
final readonly class LeadFormDefinition
{
    /**
     * @param list<LeadQuestion> $questions
     */
    public function __construct(
        public string $key,
        public string $title,
        public array $questions = [],
    ) {
    }

    public const int OTHER_MAX_LENGTH = 100;

    /**
     * Проверяет ответы и возвращает их снимок с подписями: обращение остаётся
     * читаемым, даже если вопрос потом переформулируют.
     *
     * @param array<mixed> $answers ключ вопроса => ключ ответа
     *
     * @return array{snapshot: list<array{question: string, questionLabel: string, answer: string, answerLabel: string}>, errors: array<string, string>}
     */
    public function snapshot(array $answers): array
    {
        $snapshot = [];
        $errors = [];

        foreach ($this->questions as $question) {
            $answer = $answers[$question->key] ?? null;

            if (!\is_string($answer) || '' === $answer) {
                $errors[$question->key] = 'Выберите вариант.';
                continue;
            }
            if (!isset($question->options[$answer])) {
                $errors[$question->key] = 'Недопустимый вариант.';
                continue;
            }

            $snapshot[] = [
                'question' => $question->key,
                'questionLabel' => $question->label,
                'answer' => $answer,
                'answerLabel' => $question->options[$answer],
            ];

            if (null === $question->otherOption || $answer !== $question->otherOption) {
                continue;
            }

            $otherKey = $question->key.'_other';
            $other = $answers[$otherKey] ?? null;
            $other = \is_string($other) ? trim($other) : '';

            if ('' === $other) {
                $errors[$otherKey] = 'Уточните, пожалуйста.';
            } elseif (mb_strlen($other) > self::OTHER_MAX_LENGTH) {
                $errors[$otherKey] = \sprintf('Не длиннее %d символов.', self::OTHER_MAX_LENGTH);
            } else {
                $snapshot[] = [
                    'question' => $otherKey,
                    'questionLabel' => $question->otherLabel ?? $question->label,
                    'answer' => $other,
                    'answerLabel' => $other,
                ];
            }
        }

        return ['snapshot' => $snapshot, 'errors' => $errors];
    }
}
