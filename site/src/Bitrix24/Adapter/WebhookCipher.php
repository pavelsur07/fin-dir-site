<?php

declare(strict_types=1);

namespace App\Bitrix24\Adapter;

use App\Bitrix24\Exception\Bitrix24NotConnected;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Шифрует адрес вебхука для хранения в БД (libsodium secretbox).
 * Ключ выводится из APP_SECRET с отдельным контекстом: новый секрет в окружении не нужен.
 * Смена APP_SECRET делает сохранённый адрес нечитаемым -- админка просит переподключиться.
 */
final readonly class WebhookCipher
{
    private const string CONTEXT = 'vashfindir/bitrix24-webhook/v1';

    private string $key;

    public function __construct(#[Autowire('%kernel.secret%')] string $appSecret)
    {
        if ('' === $appSecret) {
            throw new \LogicException('APP_SECRET is required to store the Bitrix24 webhook.');
        }
        $this->key = sodium_crypto_generichash(self::CONTEXT."\0".$appSecret, '', \SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public function encrypt(string $plain): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($plain, $nonce, $this->key));
    }

    public function decrypt(string $stored): string
    {
        $raw = base64_decode($stored, true);
        if (false === $raw || \strlen($raw) <= \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new Bitrix24NotConnected('undecryptable');
        }

        $plain = sodium_crypto_secretbox_open(
            substr($raw, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($raw, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key,
        );

        return false === $plain ? throw new Bitrix24NotConnected('undecryptable') : $plain;
    }
}
