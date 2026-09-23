<?php

declare(strict_types=1);

namespace App\Lead\DTO;

use App\Lead\ValueObject\LeadFormCatalog;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Вход публичной формы. Поля названы как в HTML-форме, ошибки возвращаются
 * по этим же ключам, чтобы JS подсветил нужное поле.
 */
final class LeadSubmission
{
    /** Локальный путь: не "//host" и не "/\\host" (браузер читает его как "//host"), без пробелов. */
    public const string LOCAL_PATH = '#^/(?![/\\\\])[^\s\x00-\x1f]*$#';

    #[Assert\NotBlank(message: 'Форма не найдена.')]
    public string $form = '';

    /**
     * Ставит JS при открытии формы. Пусто -- браузер без JS, id выдаст сервер
     * (в шаблон его класть нельзя: страница кешируется, id стал бы общим).
     */
    #[Assert\Uuid(message: 'Обновите страницу и отправьте форму ещё раз.')]
    public string $submissionId = '';

    #[Assert\NotBlank(message: 'Укажите имя.')]
    #[Assert\Length(max: 100, maxMessage: 'Не длиннее 100 символов.')]
    public string $name = '';

    #[Assert\NotBlank(message: 'Укажите телефон, email или Telegram.')]
    #[Assert\Length(min: 5, max: 200, minMessage: 'Слишком короткий контакт.', maxMessage: 'Не длиннее 200 символов.')]
    public string $contact = '';

    #[Assert\Length(max: 2000, maxMessage: 'Не длиннее 2000 символов.')]
    public ?string $task = null;

    #[Assert\IsTrue(message: 'Нужно согласие на обработку персональных данных.')]
    public bool $agreement = false;

    /** @var array<mixed> ключ вопроса => ключ ответа */
    public array $answers = [];

    /** Honeypot: человек его не видит и не заполняет. */
    public ?string $website = null;

    /**
     * Сколько миллисекунд форма была открыта до отправки (считает JS по своим же
     * часам -- расхождение часов клиента и сервера не влияет).
     */
    public ?int $fillMs = null;

    /** Путь страницы на этом сайте, не произвольный URL. */
    #[Assert\Length(max: 500, maxMessage: 'Слишком длинный адрес страницы.')]
    #[Assert\Regex(pattern: self::LOCAL_PATH, message: 'Некорректный адрес страницы.')]
    public ?string $pageUrl = null;

    /** Только origin сайта-источника: путь и query чужого сайта могут содержать ПД. */
    #[Assert\Length(max: 500, maxMessage: 'Слишком длинный адрес источника.')]
    public ?string $referrer = null;

    /** @var array<string, string> */
    public array $utm = [];

    #[Assert\Callback]
    public function validateAnswers(ExecutionContextInterface $context): void
    {
        if ('' === $this->form) {
            return;
        }
        if (!LeadFormCatalog::has($this->form)) {
            $context->buildViolation('Форма не найдена.')->atPath('form')->addViolation();

            return;
        }

        foreach (LeadFormCatalog::get($this->form)->snapshot($this->answers)['errors'] as $question => $message) {
            $context->buildViolation($message)->atPath('answers['.$question.']')->addViolation();
        }
    }

    /**
     * Поля HTML-формы (snake_case) → DTO. Только перенос значений, без правил.
     *
     * @param array<array-key, mixed> $data ключи из POST -- бывают и числовыми
     */
    public static function fromFormData(array $data): self
    {
        $string = static fn (string $key): ?string => isset($data[$key]) && \is_scalar($data[$key]) ? trim((string) $data[$key]) : null;

        $submission = new self();
        $submission->form = (string) $string('form');
        $submission->submissionId = (string) $string('submission_id');
        $submission->name = (string) $string('name');
        $submission->contact = (string) $string('contact');
        $submission->task = $string('task');
        $submission->agreement = \in_array($string('agreement'), ['1', 'on', 'true'], true);
        $submission->answers = \is_array($data['answers'] ?? null) ? $data['answers'] : [];
        $submission->website = $string('website');
        $fillMs = $string('fill_ms');
        $submission->fillMs = null !== $fillMs && ctype_digit($fillMs) ? (int) $fillMs : null;
        $submission->pageUrl = $string('page_url') ?: null;
        $submission->referrer = self::origin($string('referrer'));
        foreach ($data as $key => $value) {
            if (\is_string($key) && str_starts_with($key, 'utm_') && \is_scalar($value) && '' !== (string) $value) {
                $submission->utm[$key] = (string) $value;
            }
        }

        return $submission;
    }

    private static function origin(?string $url): ?string
    {
        $parts = null === $url || '' === $url ? false : parse_url($url);
        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
