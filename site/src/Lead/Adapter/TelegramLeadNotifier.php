<?php

declare(strict_types=1);

namespace App\Lead\Adapter;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Уведомление в Telegram через Bot API. Синхронно, с коротким таймаутом:
 * недоступный Telegram задерживает ответ формы максимум на TIMEOUT секунд
 * и никогда не теряет обращение -- оно уже сохранено до вызова.
 */
final class TelegramLeadNotifier
{
    private const float TIMEOUT = 3.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%vf.site_url%')] private readonly string $siteUrl,
        #[Autowire('%env(TELEGRAM_BOT_TOKEN)%')] private readonly string $botToken,
        #[Autowire('%env(TELEGRAM_LEAD_CHAT_ID)%')] private readonly string $chatId,
    ) {
    }

    public function isEnabled(): bool
    {
        return '' !== $this->botToken && '' !== $this->chatId;
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
            $response = $this->httpClient->request('POST', \sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken), [
                'timeout' => self::TIMEOUT,
                'max_duration' => self::TIMEOUT,
                'json' => [
                    'chat_id' => $this->chatId,
                    'text' => $notification->text($this->siteUrl.$this->urlGenerator->generate('admin_lead_show', ['id' => $notification->leadId])),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ],
            ]);

            $status = $response->getStatusCode();
            if (200 !== $status) {
                return \sprintf('Telegram API responded %d', $status);
            }
        } catch (ExceptionInterface $e) {
            // Сообщение исключения может содержать URL с токеном -- наружу только класс.
            return 'Telegram request failed: '.$e::class;
        }

        return null;
    }
}
