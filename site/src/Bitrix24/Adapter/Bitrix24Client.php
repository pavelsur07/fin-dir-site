<?php

declare(strict_types=1);

namespace App\Bitrix24\Adapter;

use App\Bitrix24\DTO\CrmLeadData;
use App\Bitrix24\Exception\Bitrix24RequestFailed;
use App\Bitrix24\ValueObject\WebhookUrl;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TimeoutExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * REST Битрикс24 через входящий вебхук. Синхронно, с коротким таймаутом.
 * Сообщения исключений HTTP-клиента содержат URL с токеном, поэтому наружу
 * выходит только категория сбоя (Bitrix24RequestFailed::$reason).
 */
final readonly class Bitrix24Client
{
    public const string ORIGINATOR = 'vashfindir.ru';
    private const float TIMEOUT = 4.0;
    private const array UTM_FIELDS = [
        'utm_source' => 'UTM_SOURCE', 'utm_medium' => 'UTM_MEDIUM', 'utm_campaign' => 'UTM_CAMPAIGN',
        'utm_content' => 'UTM_CONTENT', 'utm_term' => 'UTM_TERM',
    ];

    /**
     * Клиент без логгера (config/services.yaml): HttpClient пишет полный URL запроса
     * в лог уровня info, а в URL вебхука -- токен. Запросы во внутреннюю сеть запрещены.
     */
    public function __construct(#[Autowire(service: 'bitrix24.http_client')] private HttpClientInterface $httpClient)
    {
    }

    /** Проверка вебхука: метод требует право crm. */
    public function ping(WebhookUrl $webhook): void
    {
        $this->call($webhook, 'crm.lead.fields', []);
    }

    public function findLead(WebhookUrl $webhook, string $originId): ?int
    {
        $result = $this->call($webhook, 'crm.lead.list', [
            // Префикс «=» -- точное совпадение: ORIGIN_ID 1 не должен найти лид 12.
            'filter' => ['=ORIGINATOR_ID' => self::ORIGINATOR, '=ORIGIN_ID' => $originId],
            'select' => ['ID'],
        ]);

        $id = \is_array($result) && \is_array($result[0] ?? null) ? ($result[0]['ID'] ?? null) : null;

        return is_numeric($id) ? (int) $id : null;
    }

    public function addLead(WebhookUrl $webhook, CrmLeadData $lead): int
    {
        $fields = [
            'TITLE' => $lead->title,
            'NAME' => $lead->name,
            'SOURCE_ID' => 'WEB',
            'SOURCE_DESCRIPTION' => 'Форма на '.self::ORIGINATOR,
            'COMMENTS' => implode('<br>', array_map(static fn (string $line): string => htmlspecialchars($line, \ENT_QUOTES), $lead->comments)),
            'OPENED' => 'Y',
            'ORIGINATOR_ID' => self::ORIGINATOR,
            'ORIGIN_ID' => $lead->originId,
        ];
        if (null !== $lead->phone) {
            $fields['PHONE'] = [['VALUE' => $lead->phone, 'VALUE_TYPE' => 'WORK']];
        }
        if (null !== $lead->email) {
            $fields['EMAIL'] = [['VALUE' => $lead->email, 'VALUE_TYPE' => 'WORK']];
        }
        if (null !== $lead->telegram) {
            $fields['IM'] = [['VALUE' => $lead->telegram, 'VALUE_TYPE' => 'TELEGRAM']];
        }
        foreach (array_intersect_key($lead->utm, self::UTM_FIELDS) as $key => $value) {
            $fields[self::UTM_FIELDS[$key]] = $value;
        }

        $id = $this->call($webhook, 'crm.lead.add', ['fields' => $fields]);

        return is_numeric($id) && (int) $id > 0 ? (int) $id : throw new Bitrix24RequestFailed('bad_response', true);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function call(WebhookUrl $webhook, string $method, array $params): mixed
    {
        try {
            $response = $this->httpClient->request('POST', $webhook->reveal().$method.'.json', [
                'json' => $params,
                'timeout' => self::TIMEOUT,
                'max_duration' => self::TIMEOUT,
                'max_redirects' => 0,
            ]);
            $status = $response->getStatusCode();
            $body = $response->toArray(false);
        } catch (TimeoutExceptionInterface) {
            throw new Bitrix24RequestFailed('timeout', true);
        } catch (ExceptionInterface) {
            // Сеть, DNS, TLS или не-JSON ответ: дошёл ли запрос -- неизвестно.
            throw new Bitrix24RequestFailed('network', true);
        }

        if (200 === $status && \array_key_exists('result', $body)) {
            return $body['result'];
        }

        $code = \is_string($body['error'] ?? null) && 1 === preg_match('/^[A-Za-z0-9_]{1,64}$/', $body['error']) ? $body['error'] : 'http_'.$status;

        throw new Bitrix24RequestFailed($code, $status >= 500);
    }
}
