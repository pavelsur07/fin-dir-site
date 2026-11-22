<?php

declare(strict_types=1);

namespace App\Bitrix24\Query\Bitrix24Settings;

/** Экран «Настройки → Битрикс24». Полного адреса вебхука здесь нет и быть не должно. */
final readonly class Bitrix24SettingsView
{
    public function __construct(
        public bool $connected,
        public ?string $portal = null,
        public ?string $mask = null,
        public bool $enabled = false,
        public ?\DateTimeImmutable $checkedAt = null,
        public ?string $lastError = null,
        public ?\DateTimeImmutable $updatedAt = null,
        public ?string $lastErrorLabel = null,
    ) {
    }
}
