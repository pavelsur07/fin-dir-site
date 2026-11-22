<?php

declare(strict_types=1);

namespace App\Bitrix24\ValueObject;

use App\Bitrix24\Exception\InvalidWebhookUrl;

/**
 * Адрес входящего вебхука облачного Битрикс24: https://<портал>.bitrix24.<зона>/rest/<user>/<token>/.
 * Это секрет -- по нему читают и меняют CRM. Наружу (в UI, логи, ошибки) выходят только portal и mask.
 * Хосты вне облака bitrix24 не принимаются: адрес вводят в админке, сервер сам ходит по нему.
 */
final readonly class WebhookUrl
{
    /** Зоны облачного Битрикс24; прочие домены вида *.bitrix24.xyz могут принадлежать кому угодно. */
    private const string HOST = '/^[a-z0-9][a-z0-9-]{0,62}\.bitrix24\.(ru|com|by|kz|ua|de|eu|es|fr|it|pl|in|vn|id|jp|mx|cn|com\.br|com\.tr|co\.uk)$/';
    private const string PATH = '#^/rest/(\d{1,10})/([a-z0-9]{8,64})/?$#';

    private function __construct(
        public string $portal,
        private string $userId,
        private string $token,
    ) {
    }

    public static function fromString(string $raw): self
    {
        $parts = parse_url(trim($raw));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (false === $parts
            || 'https' !== strtolower((string) ($parts['scheme'] ?? ''))
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])
            || 1 !== preg_match(self::HOST, $host)
            || 1 !== preg_match(self::PATH, strtolower($path), $match)
        ) {
            throw new InvalidWebhookUrl();
        }

        return new self($host, $match[1], $match[2]);
    }

    /** Полный адрес -- только для HTTP-клиента и шифрования. */
    public function reveal(): string
    {
        return \sprintf('https://%s/rest/%s/%s/', $this->portal, $this->userId, $this->token);
    }

    public function mask(): string
    {
        return \sprintf('%s/rest/%s/…%s/', $this->portal, $this->userId, substr($this->token, -4));
    }
}
