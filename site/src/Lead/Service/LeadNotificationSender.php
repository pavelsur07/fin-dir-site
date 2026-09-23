<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Lead\Adapter\LeadNotification;
use App\Lead\Adapter\TelegramLeadNotifier;
use App\Lead\Entity\Lead;
use App\Lead\Repository\LeadRepository;
use App\Lead\ValueObject\LeadFormCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Отправляет уведомление по уже сохранённому обращению и записывает результат.
 * Вызывается после коммита регистрации, из команды повтора и из админки.
 */
final class LeadNotificationSender
{
    public function __construct(
        private readonly TelegramLeadNotifier $notifier,
        private readonly LeadRepository $leads,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->notifier->isEnabled();
    }

    /**
     * Повтор из админки по id обращения.
     */
    public function resend(int $id): bool
    {
        return $this->send($this->leads->get($id));
    }

    /**
     * Досылает уведомления, которые не ушли (например, Telegram был недоступен).
     *
     * @return array{sent: int, failed: int}
     */
    public function sendPending(\DateTimeImmutable $since, int $limit): array
    {
        $result = ['sent' => 0, 'failed' => 0];
        foreach ($this->leads->findPendingNotification($since, $limit) as $lead) {
            ++$result[$this->send($lead) ? 'sent' : 'failed'];
        }

        return $result;
    }

    /**
     * @return bool true -- уведомление отправлено
     */
    public function send(Lead $lead): bool
    {
        if (!$lead->needsNotification() || !$this->notifier->isEnabled()) {
            return false;
        }

        $formTitle = LeadFormCatalog::has($lead->formKey()) ? LeadFormCatalog::get($lead->formKey())->title : $lead->formKey();
        $error = $this->notifier->send(new LeadNotification(
            (int) $lead->id(),
            $formTitle,
            $lead->pageUrl(),
            array_map(static fn (array $answer): array => [
                'questionLabel' => $answer['questionLabel'],
                'answerLabel' => $answer['answerLabel'],
            ], $lead->answers()),
            $lead->utm(),
        ));

        if (null === $error) {
            $lead->markNotified($this->clock->now());
        } else {
            $lead->markNotificationFailed($error);
        }

        // Отдельный короткий flush: побочный эффект уже произошёл после коммита
        // регистрации, здесь сохраняется только его результат (PATTERNS §5.2).
        $this->entityManager->flush();

        return null === $error;
    }
}
