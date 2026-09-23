<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\DTO\LeadSubmission;
use App\Lead\Entity\Lead;
use App\Lead\Repository\LeadRepository;
use App\Lead\ValueObject\LeadAttribution;
use App\Lead\ValueObject\LeadConsent;
use App\Lead\ValueObject\LeadFormCatalog;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Регистрация обращения с сайта: сначала сохранить, потом уведомлять.
 */
final class LeadRegistrar
{
    /** Быстрее человек форму не заполнит -- это бот. */
    private const int MIN_FILL_MILLISECONDS = 3000;

    private const array UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function __construct(
        private readonly LeadRepository $leads,
        private readonly EntityManagerInterface $entityManager,
        private readonly LeadNotificationSender $notificationSender,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Вход уже прошёл Validator. Повтор submissionId возвращает существующее обращение.
     */
    public function register(LeadSubmission $submission): int
    {
        if ('' === $submission->submissionId) {
            $submission->submissionId = self::uuid4();
        }

        $existing = $this->leads->findBySubmissionId($submission->submissionId);
        if (null !== $existing) {
            return (int) $existing->id();
        }

        $now = $this->clock->now();
        $lead = new Lead(
            $submission->submissionId,
            $submission->form,
            $submission->name,
            $submission->contact,
            $submission->task,
            LeadFormCatalog::get($submission->form)->snapshot($submission->answers)['snapshot'],
            $submission->pageUrl,
            $submission->referrer,
            array_intersect_key(array_map(static fn ($value): string => mb_substr((string) $value, 0, 200), array_filter($submission->utm, 'is_scalar')), array_flip(self::UTM_KEYS)),
            LeadConsent::VERSION,
            $this->spamReason($submission),
            $now,
            LeadAttribution::fromJson($submission->attribution, $now),
            $submission->ymClientId,
        );
        $this->leads->save($lead);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Параллельный повтор той же отправки успел раньше: это не ошибка пользователя.
            $this->entityManager->clear();

            return (int) $this->leads->findBySubmissionId($submission->submissionId)?->id();
        }

        // После коммита: сбой Telegram не отменяет сохранённое обращение.
        $this->notificationSender->send($lead);

        return (int) $lead->id();
    }

    private static function uuid4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr(\ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = \chr(\ord($bytes[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function spamReason(LeadSubmission $submission): ?string
    {
        if (null !== $submission->website && '' !== trim($submission->website)) {
            return 'honeypot';
        }

        // Нет fill_ms -- браузер без JS: такого человека не наказываем. Бот тоже может
        // не прислать поле: от ботов защищают honeypot и лимит частоты, это лишь фильтр.
        if (null !== $submission->fillMs && $submission->fillMs < self::MIN_FILL_MILLISECONDS) {
            return 'too_fast';
        }

        return null;
    }
}
