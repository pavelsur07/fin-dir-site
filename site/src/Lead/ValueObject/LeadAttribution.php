<?php

declare(strict_types=1);

namespace App\Lead\ValueObject;

/**
 * Источники визитов человека до заявки: первый визит и последний значимый
 * (не прямой заход). Приходит JSON-ом из localStorage браузера, поэтому всё
 * чужое отбрасывается, а длины ограничиваются. Не валидно -- null, заявка
 * принимается без источника.
 *
 * @phpstan-type Touch array{ts?: int, channel?: string, source?: string, medium?: string, campaign?: string, content?: string, term?: string, landing?: string, referrer?: string, click?: array<string, string>}
 */
final readonly class LeadAttribution
{
    private const int MAX_JSON_BYTES = 4096;
    private const int MAX_VALUE_LENGTH = 200;
    private const int MAX_CLICK_ID_LENGTH = 100;
    private const int MAX_VISITS = 10000;
    /** Часы браузера могут спешить; дальше -- подделка или сбой. */
    private const int FUTURE_TOLERANCE_SECONDS = 86400;
    /** Браузер хранит источник 90 дней с последнего визита; первый визит бывает и раньше. */
    private const int MAX_AGE_SECONDS = 400 * 86400;

    private const array TEXT_KEYS = ['source', 'medium', 'campaign', 'content', 'term'];
    private const array CLICK_KEYS = ['yclid', 'gclid'];

    /**
     * @param Touch      $first
     * @param Touch|null $last
     */
    private function __construct(
        public array $first,
        public ?array $last,
        public ?int $visits,
    ) {
    }

    public static function fromJson(?string $json, \DateTimeImmutable $now): ?self
    {
        if (null === $json || '' === $json || \strlen($json) > self::MAX_JSON_BYTES) {
            return null;
        }

        try {
            $data = json_decode($json, true, 8, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (!\is_array($data)) {
            return null;
        }

        $first = self::touch($data['first'] ?? null, $now);
        if (null === $first) {
            return null;
        }

        $visits = $data['visits'] ?? null;

        return new self(
            $first,
            self::touch($data['last'] ?? null, $now),
            \is_int($visits) && $visits >= 1 && $visits <= self::MAX_VISITS ? $visits : null,
        );
    }

    /**
     * Уже сохранённые данные: собраны fromJson(), повторная очистка не нужна.
     *
     * @param array<mixed> $stored
     */
    public static function fromStored(array $stored): ?self
    {
        if (!isset($stored['first']) || !\is_array($stored['first'])) {
            return null;
        }

        /** @var Touch $first */
        $first = $stored['first'];
        /** @var Touch|null $last */
        $last = isset($stored['last']) && \is_array($stored['last']) ? $stored['last'] : null;

        return new self($first, $last, isset($stored['visits']) && \is_int($stored['visits']) ? $stored['visits'] : null);
    }

    /**
     * @return array{first: Touch, last: Touch|null, visits: int|null}
     */
    public function toArray(): array
    {
        return ['first' => $this->first, 'last' => $this->last, 'visits' => $this->visits];
    }

    public function firstVisitAt(): ?\DateTimeImmutable
    {
        return isset($this->first['ts']) ? (new \DateTimeImmutable())->setTimestamp($this->first['ts']) : null;
    }

    /**
     * @return Touch|null
     */
    private static function touch(mixed $raw, \DateTimeImmutable $now): ?array
    {
        if (!\is_array($raw)) {
            return null;
        }

        $touch = [];

        $ts = $raw['ts'] ?? null;
        $nowTs = $now->getTimestamp();
        if (\is_int($ts) && $ts <= $nowTs + self::FUTURE_TOLERANCE_SECONDS && $ts >= $nowTs - self::MAX_AGE_SECONDS) {
            $touch['ts'] = $ts;
        }

        $channel = $raw['channel'] ?? null;
        if (\is_string($channel) && '' !== $channel) {
            $touch['channel'] = 1 === preg_match('/^[a-z0-9_-]{1,32}$/', $channel) ? $channel : 'unknown';
        }

        foreach (self::TEXT_KEYS as $key) {
            $value = self::text($raw[$key] ?? null, self::MAX_VALUE_LENGTH);
            if (null !== $value) {
                $touch[$key] = $value;
            }
        }

        $landing = self::text($raw['landing'] ?? null, self::MAX_VALUE_LENGTH);
        if (null !== $landing && WebAddress::isLocalPath($landing)) {
            $touch['landing'] = $landing;
        }

        $referrer = WebAddress::origin(self::text($raw['referrer'] ?? null, self::MAX_VALUE_LENGTH));
        if (null !== $referrer) {
            $touch['referrer'] = $referrer;
        }

        $click = [];
        foreach (self::CLICK_KEYS as $key) {
            $value = self::text(\is_array($raw['click'] ?? null) ? ($raw['click'][$key] ?? null) : null, self::MAX_CLICK_ID_LENGTH);
            if (null !== $value) {
                $click[$key] = $value;
            }
        }
        if ([] !== $click) {
            $touch['click'] = $click;
        }

        return [] === $touch ? null : $touch;
    }

    private static function text(mixed $value, int $maxLength): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        // Управляющие символы из localStorage в админке не нужны.
        $value = trim((string) preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $value));

        return '' === $value ? null : mb_substr($value, 0, $maxLength);
    }
}
