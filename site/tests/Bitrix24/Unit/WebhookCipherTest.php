<?php

declare(strict_types=1);

namespace App\Tests\Bitrix24\Unit;

use App\Bitrix24\Adapter\WebhookCipher;
use App\Bitrix24\Exception\Bitrix24NotConnected;
use PHPUnit\Framework\TestCase;

final class WebhookCipherTest extends TestCase
{
    private const string URL = 'https://a.bitrix24.ru/rest/1/abcdefgh12/';

    public function testRoundTripWithoutPlaintextInStorage(): void
    {
        $cipher = new WebhookCipher('app-secret-1');
        $stored = $cipher->encrypt(self::URL);

        self::assertStringNotContainsString('abcdefgh12', $stored);
        self::assertNotSame($stored, $cipher->encrypt(self::URL), 'Каждый раз новый nonce.');
        self::assertSame(self::URL, $cipher->decrypt($stored));
    }

    public function testOtherAppSecretCannotDecrypt(): void
    {
        $stored = (new WebhookCipher('app-secret-1'))->encrypt(self::URL);

        $this->expectException(Bitrix24NotConnected::class);
        (new WebhookCipher('app-secret-2'))->decrypt($stored);
    }

    public function testTamperedValueIsRejected(): void
    {
        $cipher = new WebhookCipher('app-secret-1');
        $raw = base64_decode($cipher->encrypt(self::URL), true);
        self::assertIsString($raw);
        $raw[-1] = \chr(\ord($raw[-1]) ^ 1);

        $this->expectException(Bitrix24NotConnected::class);
        $cipher->decrypt(base64_encode($raw));
    }
}
