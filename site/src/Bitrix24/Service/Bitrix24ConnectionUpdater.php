<?php

declare(strict_types=1);

namespace App\Bitrix24\Service;

use App\Bitrix24\Adapter\Bitrix24Client;
use App\Bitrix24\Adapter\WebhookCipher;
use App\Bitrix24\Entity\Bitrix24Connection;
use App\Bitrix24\Exception\Bitrix24NotConnected;
use App\Bitrix24\Exception\Bitrix24RequestFailed;
use App\Bitrix24\Exception\InvalidWebhookUrl;
use App\Bitrix24\Repository\Bitrix24ConnectionRepository;
use App\Bitrix24\ValueObject\WebhookUrl;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Сценарии экрана «Настройки → Битрикс24»: подключить, проверить, включить/выключить, отключить.
 */
final class Bitrix24ConnectionUpdater
{
    public function __construct(
        private readonly Bitrix24ConnectionRepository $connections,
        private readonly Bitrix24Client $client,
        private readonly WebhookCipher $cipher,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Сохраняет адрес, только если портал ответил: нерабочий вебхук не подменяет рабочий.
     *
     * @throws InvalidWebhookUrl
     * @throws Bitrix24RequestFailed
     */
    public function connect(string $rawUrl): void
    {
        $webhook = WebhookUrl::fromString($rawUrl);
        $this->client->ping($webhook);

        $now = $this->clock->now();
        $encrypted = $this->cipher->encrypt($webhook->reveal());
        $connection = $this->connections->find();
        if (null === $connection) {
            $this->connections->save(new Bitrix24Connection($encrypted, $webhook->portal, $webhook->mask(), $now));
        } else {
            $connection->reconnect($encrypted, $webhook->portal, $webhook->mask(), $now);
        }
        $this->entityManager->flush();
    }

    /**
     * @return ?string null -- портал отвечает, иначе категория сбоя
     */
    public function check(): ?string
    {
        $connection = $this->connections->find() ?? throw new Bitrix24NotConnected();

        try {
            $this->client->ping(WebhookUrl::fromString($this->cipher->decrypt($connection->encryptedWebhook())));
            $error = null;
        } catch (Bitrix24RequestFailed|Bitrix24NotConnected $e) {
            $error = $e->reason;
        } catch (InvalidWebhookUrl) {
            $error = 'undecryptable';
        }

        $connection->recordCheck($error, $this->clock->now());
        $this->entityManager->flush();

        return $error;
    }

    public function changeEnabled(bool $enabled): void
    {
        $connection = $this->connections->find() ?? throw new Bitrix24NotConnected();
        $connection->changeEnabled($enabled, $this->clock->now());
        $this->entityManager->flush();
    }

    /** Стирает сохранённый адрес. Сам вебхук в Битрикс24 удаляется там же, где создан. */
    public function disconnect(): void
    {
        $connection = $this->connections->find();
        if (null !== $connection) {
            $this->connections->remove($connection);
            $this->entityManager->flush();
        }
    }
}
