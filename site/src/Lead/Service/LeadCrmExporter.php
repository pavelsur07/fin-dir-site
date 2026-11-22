<?php

declare(strict_types=1);

namespace App\Lead\Service;

use App\Bitrix24\DTO\CrmLeadData;
use App\Bitrix24\Exception\Bitrix24NotConnected;
use App\Bitrix24\Exception\Bitrix24RequestFailed;
use App\Bitrix24\Service\Bitrix24LeadSender;
use App\Lead\Adapter\LeadAdminLink;
use App\Lead\Entity\Lead;
use App\Lead\Repository\LeadRepository;
use App\Lead\ValueObject\ContactType;
use App\Lead\ValueObject\LeadConsent;
use App\Lead\ValueObject\LeadFormCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Передаёт сохранённое обращение лидом в CRM и записывает результат.
 * Вызывается после коммита регистрации, из команды досылки и из карточки в админке.
 * Сбой CRM не трогает само обращение: оно уже сохранено.
 */
final class LeadCrmExporter
{
    private const string FRESH_LEAD_GRACE = '-2 minutes';

    /** @var list<string> */
    private readonly array $consentVersions;

    /**
     * @param ?list<string> $consentVersions только для тестов; по умолчанию -- LeadConsent::crmTransferVersions()
     */
    public function __construct(
        private readonly Bitrix24LeadSender $sender,
        private readonly LeadRepository $leads,
        private readonly LeadAdminLink $adminLink,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        ?array $consentVersions = null,
    ) {
        $this->consentVersions = $consentVersions ?? LeadConsent::crmTransferVersions();
    }

    public function isEnabled(): bool
    {
        return $this->sender->isEnabled();
    }

    public function resend(int $id): bool
    {
        return $this->export($this->leads->get($id));
    }

    /**
     * @return array{sent: int, failed: int}
     */
    public function exportPending(\DateTimeImmutable $since, int $limit): array
    {
        $result = ['sent' => 0, 'failed' => 0];
        // Свежие обращения ещё может передавать сама регистрация.
        $createdBefore = $this->clock->now()->modify(self::FRESH_LEAD_GRACE);
        foreach ($this->leads->findPendingCrmExport($this->consentVersions, $since, $createdBefore, $limit) as $lead) {
            ++$result[$this->export($lead) ? 'sent' : 'failed'];
        }

        return $result;
    }

    /**
     * @param bool $mayExist лид мог быть создан раньше (повтор): сначала искать его в CRM.
     *                       false -- только для первой попытки сразу после регистрации.
     *
     * @return bool true -- лид создан (или найден созданным прошлой попыткой)
     */
    public function export(Lead $lead, bool $mayExist = true): bool
    {
        if (!$lead->needsCrmExport($this->consentVersions) || !$this->sender->isEnabled()) {
            return false;
        }

        $id = (int) $lead->id();
        if (!$this->leads->tryLockCrmExport($id)) {
            return false; // это обращение прямо сейчас передаёт другой процесс
        }

        try {
            // Пока ждали блокировку, другой процесс мог успеть передать обращение.
            $this->entityManager->refresh($lead);
            if (!$lead->needsCrmExport($this->consentVersions)) {
                return null !== $lead->crmLeadId();
            }

            try {
                $crmLeadId = $this->sender->send($this->data($lead), $mayExist || $lead->hadCrmExportAttempt());
                $lead->markCrmExported($crmLeadId, $this->clock->now());
            } catch (Bitrix24RequestFailed|Bitrix24NotConnected $e) {
                $lead->markCrmExportFailed($e->reason);
            }

            // Отдельный короткий flush: внешний вызов уже произошёл после коммита
            // регистрации, здесь сохраняется только его результат (PATTERNS §5.2).
            $this->entityManager->flush();
        } finally {
            $this->leads->unlockCrmExport($id);
        }

        return null !== $lead->crmLeadId();
    }

    private function data(Lead $lead): CrmLeadData
    {
        $id = (int) $lead->id();
        $forms = LeadFormCatalog::all();
        $formTitle = isset($forms[$lead->formKey()]) ? $forms[$lead->formKey()]->title : $lead->formKey();
        $type = $lead->contactType();

        $comments = [\sprintf('Обращение №%d с сайта, форма «%s».', $id, $formTitle)];
        if (ContactType::OTHER === $type) {
            $comments[] = 'Контакт: '.$lead->contact();
        }
        if (null !== $lead->task()) {
            $comments[] = 'Задача: '.$lead->task();
        }
        foreach ($lead->answers() as $answer) {
            $comments[] = $answer['questionLabel'].': '.$answer['answerLabel'];
        }
        if (null !== $lead->pageUrl()) {
            $comments[] = 'Страница: '.$lead->pageUrl();
        }
        $comments[] = 'Карточка на сайте: '.$this->adminLink->forLead($id);

        return new CrmLeadData(
            (string) $id,
            \sprintf('Заявка с сайта №%d', $id),
            $lead->name(),
            ContactType::PHONE === $type ? $lead->contactNormalized() : null,
            ContactType::EMAIL === $type ? $lead->contactNormalized() : null,
            ContactType::TELEGRAM === $type ? $lead->contactNormalized() : null,
            $comments,
            $lead->utm(),
        );
    }
}
