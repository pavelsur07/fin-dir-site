<?php

declare(strict_types=1);

namespace App\Lead\Adapter;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Уведомление о заявке через релей бота (vf_materials_bot, POST /api/leads).
 * Сайт на RU-хостинге не достаёт до api.telegram.org (блокировка), поэтому
 * отдаёт готовый текст релею, а тот пересылает его в Telegram из NL.
 * Синхронно, с коротким таймаутом: недоступный релей задерживает ответ формы
 * максимум на TIMEOUT секунд и никогда не теряет обращение -- оно уже сохранено
 * до вызова.
 */
final class TelegramLeadNotifier
{
    private const float TIMEOUT = 3.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%vf.site_url%')] private readonly string $siteUrl,
        #[Autowire('%env(LEAD_RELAY_URL)%')] private readonly string $relayUrl,
        #[Autowire('%env(LEAD_RELAY_TOKEN)%')] private readonly string $relayToken,
    ) {
    }

    public function isEnabled(): bool
    {
        return '' !== $this->relayUrl && '' !== $this->relayToken;
    }

    /**
     * @return ?string null -- отправлено, иначе причина сбоя (без токена и ПД)
     */
    public function send(LeadNotification $notification): ?string
    {
        if (!$this->isEnabled()) {
            return 'Telegram notifications are disabled';
        }

        try {
            $response = $this->httpClient->request('POST', $this->relayUrl, [
                'timeout' => self::TIMEOUT,
                'max_duration' => self::TIMEOUT,
                'headers' => ['X-Lead-Token' => $this->relayToken],
                'json' => [
                    'text' => $notification->text($this->siteUrl.$this->urlGenerator->generate('admin_lead_show', ['id' => $notification->leadId])),
                ],
            ]);

            $status = $response->getStatusCode();
            if (200 !== $status) {
                return \sprintf('Lead relay responded %d', $status);
            }
        } catch (ExceptionInterface $e) {
            // Сообщение исключения может содержать URL с токеном -- наружу только класс.
            return 'Lead relay request failed: '.$e::class;
        }

        return null;
    }
}
