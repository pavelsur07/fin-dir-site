# Documentation MCP HTTP — реализация и рекомендации

Дата: 21.11.2026. Ветка: `feat/documentation-mcp-http`.
План: [Stage_Documentation_MCP_HTTP.md](Stage_Documentation_MCP_HTTP.md).

## Реализовано

- Symfony resource server `https://vashfindir.ru/mcp/docs` и protected-resource metadata.
  По умолчанию HTTP выключен, включая metadata (`404`).
- Изолированный stateless Bearer firewall; Admin session/CSRF остаются отдельными.
- SDK 0.10.0, sessionless Streamable HTTP; обе ревизии `2025-11-25` / `2026-07-28`.
  Конечные синхронные ответы, без subscriptions, GET streams, sticky routing.
- Только шесть published-reader tools. Ни JWT scope, ни stdio env не открывают
  черновики и write tools по HTTP.
- Проверка RS256/at+jwt, точных issuer/audience, client_id, scope, iat/exp/nbf,
  TTL до 300с, skew 30с. Ограниченные HTTPS discovery/JWKS, кеш и защита от
  unknown-kid flood. Токен не является ключом кеша или устойчивым actor.
- Аддитивная таблица локальных grants; Console allow/disable; проверка БД
  на каждом запросе, без положительного кеша. Никакой выдачи токенов сайтом.
- PostgreSQL cache + отдельный advisory lock factory для MCP counters.
  У существующих Lead/Admin не меняется default lock factory.
- Host/Origin guards, лимиты входа/выхода 1 MiB, заголовков и request ID;
  JSON не обрезается. Issuer JSON 256 KiB, TLS verification, без redirects,
  timeout 2с / max duration 3с, outbound quota 10/мин на URI.
- HTTP audit через отдельный PSR info logger: actor, известное имя tool,
  status, duration, correlation ID; строковый ID хешируется. Body, Markdown,
  Authorization и сырые JWT exceptions не передаются в audit.
- Одноразовый HTTPS issuer и БД, две production FPM-реплики, настоящий SDK-клиент.
  Временные RSA/TLS keys и tokens создаются только для проверки и удаляются.
  Новый check входит в существующий CI через `make php-production-smoke`;
  workflow/production Compose не изменены.

## Рекомендация при отсутствии выбранного issuer/клиента

Для **согласованного ограничения доступа выбранными интеграциями** базовая схема:

1. Управляемый OAuth issuer, например Auth0, чтобы не обслуживать новый
   auth-сервис рядом с Symfony ради одного MCP. Это рекомендация, не выбранный
   пользователем провайдер и не созданный tenant. Доступность, тариф и условия
   конкретного аккаунта проверяются до provisioning.
2. Отдельный API resource identifier `https://vashfindir.ru/mcp/docs`, RS256,
   RFC 9068 access-token profile и TTL 300с. Default Auth0 profile не подходит:
   он использует `JWT`/`azp` вместо `at+jwt`/`client_id`.
3. Для стандартных MCP resource indicators включить Auth0 Resource Parameter
   Compatibility Profile; проверить фактический `aud` в отдельном smoke.
   Не подменять resource metadata token proxy или выдачей токенов в Symfony.
4. Отдельный M2M client для каждой интеграции и каждого окружения; только
   `docs:read`, плюс локальный grant в Symfony. Секрет остаётся у клиента,
   resource server хранит только публичные ключи issuer и локальные grants.
5. Client Credentials для автоматического сервиса. Предпочесть JWT assertions /
   private_key_jwt, когда поддерживают issuer и SDK; для первого MVP допустим
   client_secret из secret store с ротацией. Access token кешировать в памяти
   до обновления перед expiry; не получать новый token для каждого tool call.
6. Внешний клиент — официальный MCP SDK на языке сервиса. Для отдельного
   эталонного M2M-клиента подходит TypeScript SDK с ClientCredentialsProvider
   и закреплённым expectedIssuer. Версию фиксировать в lockfile. В этой ветке
   реально проверен PHP SDK 0.10.0 с готовым Bearer token; обмен с живым Auth0
   и TypeScript/Python-клиентом ещё не проверен.
7. Для приложения, действующего от имени пользователя, отдельно выбрать
   Authorization Code + PKCE и регистрацию клиента. M2M credential не является
   универсальной настройкой любого desktop/chat MCP-клиента.

Если цель — доступ **любому клиенту к уже публичной документации**, открытый
published-reader MCP без OAuth проще и допускается спецификацией. Сохраняются
IP quotas, размерные ограничения, Host/Origin policy и запрет записи/черновиков.
Это иной access policy; в данной реализации обязательная OAuth-проверка не
отменена автоматически. Keycloak имеет смысл при требовании self-hosting или
существующем IAM, после проверки его фактического token profile с validator.

## Проверки и ревью

Команды: `make ci`, `make php-production-build`, `make php-production-smoke`.
Последний включает `make documentation-mcp-http-check`.

Независимое read-only ревью: `/root/documentation_http_final_review`.
Существенный finding: unknown-method error мог превысить 1 MiB из-за JSON
HEX escaping. Regression test воспроизвёл 1,200,062 байта, затем прошёл после
замены отражённого ввода на фиксированное сообщение. Исправлена также проверка
двух запрещённых headers в smoke, чтобы каждый проверялся самостоятельно.

Production smoke проверяет обе SDK revision, наблюдает обе FPM upstream,
публикационную видимость, немедленный отзыв grant, IP quota и конкурентный
actor quota: ровно 120 accepted / 1 rejected после очистки только тестового pool.
Также проверяются прежние stdio, Website, docs, Admin login/shared session/CSRF/logout.
Итог 21.11.2026:

- `make ci` — успешно: 443 теста / 3686 assertions, lint/CS/PHPStan/deptrac.
- `make php-production-build` — оба no-dev образа успешно собраны.
- `make php-production-smoke` — успешно, включая HTTP MCP SDK / concurrency
  и все прежние проверки сайта, документации, Admin и stdio.
- `git diff --check` — успешно. Локально для isolated smoke использован только
  дополнительный свободный subnet override: default Docker pools на хосте заняты.
  Этот локальный override не добавлен в репозиторий.
- Найденные замечания закрыты; проверка audit privacy/correlation — в functional tests.

## Что требуется до включения production

- Выбор access policy: выбранные OAuth-интеграции либо отдельное решение об
  открытом published-reader. Текущий безопасный default остаётся выключенным.
- Для OAuth: tenant/issuer и зарегистрированный клиент у владельца аккаунта;
  реальные credentials не присылать в чат и не добавлять в репозиторий.
- Конкретный release scope по Production Gate: FPM env/Compose/deploy wiring,
  pinned images, backup с проверкой checksum/TOC, аддитивная migration, grant,
  публичный HTTPS smoke и проверка фактического клиента.
- Проверить reverse-proxy/HTTPS, доступность issuer из FPM и его TTL/profile,
  operational request timeouts и сбор audit logs. Существующие DB timeouts
  применяются внутри tools; PHP/Nginx timeout не выдаётся за общий wall-clock
  deadline 30с. Идея жёсткого 30с process deadline требует отдельного production
  решения, если появится измеренная необходимость.

Ветка не создаёт OAuth accounts, платные подписки, production credentials,
сертификаты или grants и не включает endpoint на production. Деплой текущего
stdio-модуля не является подтверждением подключения нового живого issuer.

## Первичные источники

- [MCP authorization: optional auth и resource server](https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization).
- [MCP Client Credentials, SDK providers и защита credentials](https://modelcontextprotocol.io/extensions/auth/oauth-client-credentials).
- [Auth0 token profiles](https://auth0.com/docs/secure/tokens/access-tokens/access-token-profiles).
- [Auth0 access token lifetime](https://auth0.com/docs/secure/tokens/access-tokens/update-access-token-lifetime).
- [Auth0 MCP resource/audience compatibility](https://support.auth0.com/center/s/article/mcp-audience-error-with-auth0).
- [Symfony Rate Limiter locks](https://symfony.com/doc/7.4/rate_limiter.html).
