<?php

declare(strict_types=1);

namespace App\Bitrix24\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Подключение к порталу Битрикс24 -- одно на сайт (id всегда 1).
 * Адрес вебхука хранится только зашифрованным; portal и mask -- для показа в админке.
 */
#[ORM\Entity]
#[ORM\Table(name: 'bitrix24_connection')]
class Bitrix24Connection
{
    public const int SINGLETON_ID = 1;

    #[ORM\Id]
    #[ORM\Column]
    private int $id = self::SINGLETON_ID;

    #[ORM\Column(type: 'text')]
    private string $encryptedWebhook;

    #[ORM\Column(length: 100)]
    private string $portal;

    #[ORM\Column(length: 200)]
    private string $mask;

    /** Отправлять ли новые обращения. Выключение не стирает подключение. */
    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $checkedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $encryptedWebhook, string $portal, string $mask, \DateTimeImmutable $now)
    {
        $this->encryptedWebhook = $encryptedWebhook;
        $this->portal = $portal;
        $this->mask = $mask;
        $this->checkedAt = $now;
        $this->updatedAt = $now;
    }

    /** Новый адрес прошёл проверку: заменяет прежний и включает отправку. */
    public function reconnect(string $encryptedWebhook, string $portal, string $mask, \DateTimeImmutable $now): void
    {
        $this->encryptedWebhook = $encryptedWebhook;
        $this->portal = $portal;
        $this->mask = $mask;
        $this->enabled = true;
        $this->checkedAt = $now;
        $this->lastError = null;
        $this->updatedAt = $now;
    }

    public function changeEnabled(bool $enabled, \DateTimeImmutable $now): void
    {
        $this->enabled = $enabled;
        $this->updatedAt = $now;
    }

    public function recordCheck(?string $error, \DateTimeImmutable $now): void
    {
        $this->checkedAt = $now;
        $this->lastError = null === $error ? null : mb_substr($error, 0, 100);
    }

    public function encryptedWebhook(): string
    {
        return $this->encryptedWebhook;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
