<?php

declare(strict_types=1);

namespace App\Bitrix24\Service;

use App\Bitrix24\Adapter\Bitrix24Client;
use App\Bitrix24\Adapter\WebhookCipher;
use App\Bitrix24\DTO\CrmLeadData;
use App\Bitrix24\Exception\Bitrix24NotConnected;
use App\Bitrix24\Exception\Bitrix24RequestFailed;
use App\Bitrix24\Exception\InvalidWebhookUrl;
use App\Bitrix24\Repository\Bitrix24ConnectionRepository;
use App\Bitrix24\ValueObject\WebhookUrl;

/**
 * Публичный вход модуля для других модулей: создать лид в CRM.
 * Состояние не меняет -- результат хранит модуль-владелец обращения.
 */
final class Bitrix24LeadSender
{
    public function __construct(
        private readonly Bitrix24ConnectionRepository $connections,
        private readonly Bitrix24Client $client,
        private readonly WebhookCipher $cipher,
    ) {
    }

    public function isEnabled(): bool
    {
        return true === $this->connections->find()?->isEnabled();
    }

    /**
     * @param bool $mayExist прошлая попытка могла дойти: сначала искать лид по originId
     *
     * @return int ID лида в Битрикс24
     *
     * @throws Bitrix24NotConnected
     * @throws Bitrix24RequestFailed
     */
    public function send(CrmLeadData $lead, bool $mayExist): int
    {
        $connection = $this->connections->find();
        if (null === $connection || !$connection->isEnabled()) {
            throw new Bitrix24NotConnected();
        }
        try {
            $webhook = WebhookUrl::fromString($this->cipher->decrypt($connection->encryptedWebhook()));
        } catch (InvalidWebhookUrl) {
            // Сохраняется только проверенный адрес; сюда попадём лишь при порче данных.
            throw new Bitrix24NotConnected('undecryptable');
        }

        if ($mayExist) {
            $existing = $this->client->findLead($webhook, $lead->originId);
            if (null !== $existing) {
                return $existing;
            }
        }

        return $this->client->addLead($webhook, $lead);
    }
}
