# Documentation MCP over HTTPS — план внедрения

> Для исполнителя: выполнять задачи последовательно по AGENTS.md §14.
> Самостоятельная реализация без делегирования; проверки по риску, одно
> независимое read-only ревью перед релизом. Этот документ — план, не начало
> реализации, не разрешение создавать credentials и не подтверждение внешнего issuer.

**Дата:** 21.11.2026.

**Статус:** план подготовлен. HTTP endpoint и OAuth-интеграция ещё не реализованы.

**Цель:** внешний MCP-клиент подключается по HTTPS, проходит авторизацию,
находит и читает опубликованную документацию без SSH и доступа к Docker/БД.

**Архитектура:** Symfony остаётся OAuth resource server, владельцем данных
и авторизации. Streamable HTTP адаптирует существующие MCP tools/Query;
выдача OAuth-токенов остаётся у выбранного authorization server.

**Стек:** существующие PHP 8.4.27, Symfony 7.4, PostgreSQL 17, Doctrine,
Symfony Security/HttpClient/RateLimiter, MCP PHP SDK 0.10.0. Дополнительные
PHP-библиотеки только для PSR-7 bridge и проверенного SDK JWT validator.

**Спецификация:** запрос внешнего доступа к MCP в переписке; MVP, предлагаемые
технические defaults, API-контракт и критерии приёмки зафиксированы ниже.

## 1. Контекст и ограничения

Существующий stdio MCP выпущен через PR #254, production SHA
`d673a453b4f1cbcac758b4d8b5bd7d364143f617`. Восемь tools работают внутри
`Documentation`; профили published-reader/draft-reader/draft-editor задаются
доверенным локальным запуском. Есть отдельные SQL scope, validation/preview,
оптимистичная версия, общая advisory lock и идемпотентные квитанции записи.
HTTP endpoint отсутствует.

Ключевые файлы: `Mcp/DocMcpServer.php`, `DocMcpAccess.php`, `DocReadTools.php`,
`DocMcpSchema.php`, `Query/PublishedDocMcpQuery.php`, `EditorialDocMcpQuery.php`.
История реализации: [stdio-план](Stage_Documentation_MCP.md).

Текущий Admin использует Symfony form_login, memory provider и session cookie
с Path=/admin. На docs-хосте firewall выключен. Рендер публичного Website — Twig.
Две production FPM-реплики обслуживаются существующими Nginx/Traefik.
Публичный API авторизации или OAuth authorization server в приложении не найден.

**Риск реализации высокий:** новая сетевая граница, credentials внешних клиентов,
OAuth claims, отзыв разрешений, защита от раскрытия черновиков и исчерпания FPM.
Подготовка самого плана — документальное изменение, без тестов приложения.

Обязательные ограничения:

- [PATTERNS.md](../architecture/PATTERNS.md) и [AGENTS.md](../../AGENTS.md) обязательны.
- Бизнес-логика, данные и security остаются в Symfony. Нет отдельного MCP daemon,
  Node/Python backend, новой БД, Redis или универсального API gateway.
- HTTP не наследует DOCS_MCP_PROFILE/DOCS_MCP_ACTOR локального запуска.
- Admin login/session/CSRF сохраняются; Bearer применяется только к новому MCP
  firewall как обоснованное исключение для внешних клиентов.
- Публичные/административные Query не объединяются флагом видимости.
- Проверки и запуск — через [Makefile](../../Makefile).
- Миграции аддитивные; Production Gate сохраняется для production config,
  workflow, credentials и фактического включения внешнего доступа.

## 2. Предлагаемый MVP и выбор решения

| Подход | Компромисс | Решение |
|---|---|---|
| SSH → stdio | Нужен доверенный запуск на сервере | Уже доступная транспортная основа, не предмет этого этапа |
| Streamable HTTP + OAuth | Подходит сетевым MCP-клиентам, нужен issuer/клиентский контракт | Рекомендуемый вариант |
| Отдельный REST API | Удобен обычным приложениям, требует ещё одного внешнего контракта | Отдельный запрос при реальном потребителе |

Пока тип конкретного внешнего клиента не подтверждён. Рабочее предположение
плана — серверный сервис/AI-агент без интерактивного пользователя.
Для него выбран OAuth client_credentials, описанный отдельным расширением MCP.
Если нужен вход пользователя через браузер, меняется задача подключения клиента:
authorization_code + PKCE у того же issuer, отдельно проверенные consent,
redirect URI и клиентская регистрация. Не считать поддержку client_credentials
доказательством совместимости любого MCP-приложения.

Первый сетевой выпуск предоставляет **шесть read tools**:
`docs_list_sections`, `docs_search`, `docs_get_page`, `docs_get_links`,
`docs_validate_page`, `docs_preview_page`. Только опубликованные материалы,
без закрытых разделов, черновиков, adminEditUrl и рабочих preview-ссылок Admin.
Возвращаемое поле adminPreviewUrl в существующей схеме остаётся null.

Создание/редактирование черновиков продолжают работать через stdio/Admin.
Удалённая запись, публикация, consent UI, новый Admin UI, массовый импорт,
MCP resources/prompts и асинхронные задачи не входят в первый HTTP-выпуск.
Избыточные scopes токена не расширяют этот MVP автоматически.

Нужен полноценный OAuth resource server, но не новый самописный OAuth server.
Существующий memory-admin не выдаёт токены для внешних клиентов. Выбор issuer
— отдельная эксплуатационная зависимость с контрактом из раздела 4;
план не предполагает, что такой сервис уже подключён.

## 3. Сетевой контракт

Канонический resource identifier и MCP endpoint:

```text
https://vashfindir.ru/mcp/docs
```

| Метод/адрес | Поведение |
|---|---|
| POST /mcp/docs | MCP JSON-RPC через SDK, OAuth на каждом запросе |
| GET /mcp/docs | 405 для авторизованного запроса: SSE GET-stream не предоставляется |
| DELETE /mcp/docs | 405: постоянных MCP-сессий нет |
| OPTIONS /mcp/docs | 204 без выполнения tools после проверки Host/Origin |
| GET /.well-known/oauth-protected-resource/mcp/docs | Публичные metadata этого ресурса |

Endpoint существует только на основном хосте; на docs-хосте по этим новым
адресам — 404, без перенаправления с Authorization на основной хост.
Неверный trailing slash не приводит к автоматическому переносу POST.
Отключённая HTTP-функция возвращает 404; metadata также не публикуются.
Неподдерживаемые методы не выполняют tools; точный Allow задаёт адаптер/SDK.

Поддерживаемые MCP revision: 2025-11-25 и 2026-07-28 через установленный SDK.
В новой ревизии нет protocol-level sessions/GET stream; legacy-клиенты
обслуживаются в предусмотренном SDK sessionless режиме. Не вызывать
Builder::setSession(): не нужны shared MCP session store, sticky routing
или привязка к конкретной FPM-реплике. Совместимость выбранного клиента
с отсутствием Mcp-Session-Id проверяется явно.

Используются конечные JSON-ответы синхронных tools. Не включать sampling,
elicitation, resources, prompts, subscriptions/listen или task/stream-сценарии,
способные занять FPM на неопределённое время. SDK отвечает за JSON-RPC,
согласование версии, handshake старой ревизии, metadata новой и protocol errors.
Не собирать собственный протокол поверх HTTP.

HTTP headers: Content-Type application/json; Accept и version metadata —
по согласованной ревизии и SDK. Размер входного тела/полного ответа — до 1 MiB.
Ограничивать поток до JSON decode/PSR-7 buffering, включая chunked input и
недостоверный Content-Length. Не обрезать JSON. Oversize request — 413;
oversize tool result — короткий LIMIT_EXCEEDED с сохранённым request ID.

Ответы MCP: Cache-Control private, no-store; Vary: Authorization;
X-Content-Type-Options nosniff. Metadata: public, max-age=300, без session cookie.
Не запускать PHP session ни для MCP, ни для metadata. Не использовать cookies
как запасной способ аутентификации MCP.

## 4. OAuth-контракт и входные данные для подключения

### Что нужно до production-подключения

1. Имя/версия реального MCP-клиента и подтверждение его HTTP/auth режима.
2. Доверенный HTTPS issuer, его metadata и поддержка audience/resource для
   `https://vashfindir.ru/mcp/docs`.
3. Зарегистрированный внешний клиент с правом docs:read; клиентские credentials
   хранятся у внешнего клиента. Их не размещать в Symfony/public, README или логах.
4. Зарегистрированный read-only smoke-клиент для внешней проверки после deploy
   либо согласованный эквивалентный безопасный способ получения короткого токена.

Пока этих значений нет, можно выполнять локальные задачи на тестовом issuer,
но нельзя объявлять конкретную внешнюю интеграцию настроенной или включать
production endpoint с фиктивным issuer/общим бессрочным ключом.

### Выдача и проверка

Сервис получает короткоживущий access token у своего authorization server через
client_credentials с resource/audience MCP; затем передаёт его только в
Authorization: Bearer на каждый POST. Token endpoint находится у issuer,
его URL берётся из проверенных metadata, не придумывается на основном сайте.
Свой /oauth/token и authorization UI в Symfony не добавлять. При истечении токена
M2M-клиент повторяет client_credentials у issuer; наличие refresh_token не предполагается.
Клиентский secret не отправляется на MCP endpoint.

Предлагаемый профиль токена для первого поддержанного issuer:

- подписанный JWT access token, typ=at+jwt, alg=RS256, проверяемый через JWKS;
- точные iss и aud; aud содержит канонический MCP resource;
- обязательные exp, iat, scope и непустой client_id; scope содержит docs:read;
- срок жизни максимум 300 секунд; допустимое расхождение часов 30 секунд;
- client_credentials разрешён этому client_id на стороне issuer;
- если одновременно есть client_id и azp, разные значения отклоняются.

Это явное ограничение первого issuer-контракта, не требование JWT от самого MCP.
Если реальный issuer выдаёт opaque tokens или иной профиль, до реализации
проверить introspection/валидатор и скорректировать этот раздел; не ослаблять
iss/aud/expiry ради успешного входа.

Для криптографии использовать SDK JwtTokenValidator с firebase/php-jwt,
без своего JWT parser/signature verification. Проверять дополнительные claims
в тонком адаптере после SDK. Access token не путать с OIDC ID token или client
assertion, которую клиент предъявляет issuer при получении access token.

JWKS/discovery берутся только от настроенного issuer/разрешённых HTTPS-хостов;
не читать jku/x5u/URL из токена. Не следовать произвольным redirect. HTTP timeout
2 секунды, max duration 3 секунды, redirects 0. JWT header/Authorization ограничить
8 KiB. Проверки malformed/alg выполняются до сетевого обращения.

Кеш ключей — существующий Symfony Cache, отдельный pool. Ротация kid проверяется;
refresh ограничивается и защищается от stampede/unknown-kid flood. Переиспользовать
SDK failure caching/rate limiting и подтвердить поведение тестом. При необходимости
разделяемого лимита использовать текущий PostgreSQL cache adapter, без Redis.
Если проверить ключ невозможно — 503 с Retry-After, не анонимный доступ и не 401,
провоцирующий бесконечную повторную авторизацию. Уже проверенные допустимые ключи
могут использоваться в пределах принятой cache policy.

### Metadata и ошибки

Resource metadata публикуются по RFC 9728:

```json
{
  "resource": "https://vashfindir.ru/mcp/docs",
  "authorization_servers": ["<подтверждённый HTTPS issuer>"],
  "scopes_supported": ["docs:read"],
  "bearer_methods_supported": ["header"],
  "resource_name": "Documentation MCP"
}
```

- Нет токена — 401 с WWW-Authenticate: Bearer, resource_metadata URL и docs:read.
- Невалидный/просроченный токен, другой issuer/audience, ID token — 401 invalid_token.
- Валидный токен без docs:read — 403 insufficient_scope с нужным scope.
- Валидный токен клиента без локального разрешения — 403 access_denied;
  неизвестный и отключённый client_id снаружи не различаются.
- Неисправная конфигурация/недоступная проверка ключей — 503, без login redirect.
- Ошибки прикладного tool остаются MCP tool results; protocol errors — SDK.

Metadata не содержат токены/секреты и не обещают scopes записи.
Authorization server metadata остаются на issuer: локальный адрес
/.well-known/oauth-authorization-server не создаётся для чужого issuer.

## 5. Symfony Security, права и отзыв доступа

Новый stateless firewall охватывает только точный /mcp/docs на основном хосте.
Использовать Symfony access_token authenticator с token handler; не дублировать
валидацию того же токена второй независимой auth middleware в SDK.
Symfony создаёт отдельный DocMcpPrincipal; ROLE_ADMIN из claims не принимается.
Admin firewall и его form_login/session/CSRF остаются отдельными.

До authentication работают scoped guards: feature flag, Host/Origin, размер
тела и pre-auth IP rate limit. Metadata/OPTIONS имеют отдельный публичный contract.
После authentication проверяются scope, локальный grant и per-client лимит.
Тот же доступ повторно обеспечивается при tools/call. Удалённый вызов write tool
не выполняет Service даже при ручном запросе имени или избыточном OAuth scope.

В gateway верифицированный principal переводится в существующий DocMcpAccess
с неизменяемым published-reader. Actor стабилен между обновлениями токена:
`http.` + первые 59 hex-символов SHA-256 от однозначного кодирования [iss,client_id].
Не использовать токен, jti, произвольный заголовок actor или полученный profile
как идентификатор интеграции. Для будущего делегированного пользователя отдельно
потребуется subject — не считать M2M actor готовой моделью browser consent.

Для немедленного локального отключения клиента добавить таблицу
`documentation_mcp_client_grant`, владелец — Documentation:

- id; issuer_key CHAR(64) = SHA-256 доверенного issuer; client_id VARCHAR(255);
- enabled BOOLEAN; created_at и updated_at; уникальность (issuer_key,client_id).

JWT доказывает идентичность, но не заменяет локальное разрешение. Grant читается
на каждом запросе, без положительного кеша между репликами. Отключение запрещает
следующий запрос даже с ещё непросроченным JWT. При недоступной БД — отказ 503.
Уже начатый синхронный read-запрос может завершиться; механизм не обещает
прервать его задним числом.

Управление без нового UI:

```text
app:docs:mcp:client:allow <client-id>
app:docs:mcp:client:disable <client-id>
```

Issuer берётся из доверенной конфигурации процесса. Команды идемпотентны,
используют Application Service/Repository, транзакцию и Clock; не печатают токены.
Регистрация клиента у OAuth issuer не выполняется этими командами.

## 6. Ограничения нагрузки и HTTP-защита

Предлагаемые стартовые значения, уточняемые измерением:

- до authentication: 60 запросов/минуту на IP; после него: 120/минуту на client actor;
- metadata: 30/минуту на IP; для превышения 429 и Retry-After;
- счётчики общие для двух FPM через существующий cache.rate_limiter;
- не использовать сырой Bearer как cache key; неизвестные токены не создают
  бесконечный набор per-token счётчиков;
- список по 20, FTS q до 200; существующие DB/read/graph ограничения сохраняются;
- synchronous request budget 30 секунд; timeout issuer/DB ниже этого бюджета;
- большие тела/неразрешённые методы/Origin отклоняются до тяжёлого tool;
- log: request ID, устойчивый actor, tool, HTTP status/error code, duration;
  без Authorization, полного JSON, Markdown и необработанного JWT exception.

Host разрешён только vashfindir.ru. При наличии Origin — same-origin либо
явный allowlist; Origin:null и чужой Origin возвращают 403. Отсутствующий Origin
допустим серверному клиенту. Не переносить default localhost-only host policy SDK
в production без настройки. Передавать в PSR-7 канонический HTTPS URI с учётом
только доверенных proxy headers; не доверять произвольному X-Forwarded-Host/IP.

CORS по умолчанию не включается: server-to-server клиент в нём не нуждается.
Если подтверждён browser caller — точный allowlist и OPTIONS contract, без '*'
и credentialed cookies. Origin не заменяет OAuth/authorization.

Не менять глобальные лимиты upload/Lead или отключать buffering всего Nginx
ради этого endpoint. Изменение точного location/timeout допустимо только после
проверки необходимости в задаче 5 и в согласованном production scope.

## 7. Карта файлов и контракты

Пути относительно `site/src/Documentation/`, если не указано иное.

| Файл | Ответственность |
|---|---|
| Controller/Mcp/DocMcpHttpController.php | HTTP entrypoint, проверенный principal, один вызов gateway |
| Controller/Mcp/DocMcpMetadataController.php | GET metadata, без session |
| Mcp/Http/DocMcpHttpGateway.php | Symfony/PSR-7 bridge, request-scoped SDK transport, bounded HTTP response |
| Mcp/Http/DocMcpHttpRequestGuard.php | Только новые пути: feature flag, Host/Origin/body/method/pre-auth limits |
| Mcp/Http/DocMcpHttpConfiguration.php | Неизменяемая проверенная конфигурация; нет сетевого I/O в constructor |
| Adapter/DocMcpOAuthVerifier.php | SDK JWT/JWKS и дополнительные claims → проверенная DTO identity |
| DTO/DocMcpTokenIdentity.php | issuer, clientId, scopes, expiresAt; без сырого токена |
| Security/DocMcpAccessTokenHandler.php | Symfony UserBadge из проверенной identity |
| Security/DocMcpPrincipal.php | UserInterface без Entity; роли только по scope/grant |
| Security/DocMcpSecurityResponses.php | Централизованные 401/403/503, OAuth challenge без redirect |
| Query/DocMcpClientGrantQuery.php | Проекция разрешения по issuer_key/clientId |
| Entity/DocMcpClientGrant.php, Repository/DocMcpClientGrantRepository.php | Локальный grant и persistence |
| Service/DocMcpClientGrantEditor.php | Allow/disable, одна транзакция/flush |
| Command/DocMcpClientGrantCommand.php | Две административные Console-команды |
| site/migrations/Version<timestamp>.php | Сгенерированная аддитивная миграция grant |

Изменяемые существующие файлы: DocMcpServer/DocMcpResultMapper при выделении
общей фабрики/ограничения HTTP-результатов; config/routes.yaml при необходимости,
security.yaml, framework.yaml, services.yaml, deptrac.yaml, composer.json/lock,
Makefile, README и isolated smoke scripts/config.
Production Compose/workflow/env меняются только в явно согласованном release scope.
Public Website и Admin templates/CSS/JS не затрагиваются.

Основные интерфейсы:

- `DocMcpOAuthVerifier::verify(string $token): DocMcpTokenIdentity`;
- `DocMcpClientGrantQuery::isEnabled(string $issuerKey, string $clientId): bool`;
- `DocMcpClientGrantEditor::allow(string $issuer, string $clientId): void`,
  `disable(string $issuer, string $clientId): void`;
- `DocMcpHttpGateway::handle(Request $request, DocMcpTokenIdentity $identity): Response`;
- `DocMcpServer::build(DocMcpAccess $access): Server` переиспользуется без setSession;
- UserBadge/Principal содержит только проверенную identity и разрешённые роли;
  controller/gateway не загружает Entity и не читает БД самостоятельно.

Deptrac: HTTP-controller — входная граница к DocumentationMcp/Symfony;
Security — Symfony/Adapter/DTO/Query/Exception без прямого Doctrine;
OAuth Adapter получает McpSdk и PSR HTTP interfaces, без Repository/Entity;
Mcp gateway — SDK/Symfony bridge, без Doctrine. Service → Query не разрешать.
Все новые классы включить в collectors; не скрывать нарушения baseline ignore.

## 8. Задачи реализации

### Задача 1. Подтвердить issuer/клиент и HTTP SDK contract

**Файлы:** README/этот план; tests/Documentation/Mcp/Http fixtures;
при начале реализации composer.json/lock и Makefile.

- [ ] Зафиксировать конкретный клиент/режим и issuer из раздела 4. Если данных
  ещё нет — использовать только изолированный тестовый issuer, без production enable.
- [ ] Проверить SDK 0.10.0 StreamableHttpTransport sessionless на обеих revision:
  client handshake/discovery, tools/list/call, GET/DELETE 405, конечный JSON.
- [ ] Проверить, что не включены sessions/subscriptions/sampling/async streams
  и один клиент работает последовательно через разные FPM-реплики.
- [ ] Добавить только необходимые symfony/psr-http-message-bridge 7.4.*,
  nyholm/psr7 и firebase/php-jwt совместимой версии. До install проверить
  dependency solver; точные выбранные версии закрепить lock, существующие
  зависимости не обновлять попутно. MCP SDK не обновлять без отдельной причины.
- [ ] Проверить настоящий access token выбранного issuer: audience/resource,
  typ/alg/claims/TTL. Если контракт не подходит — изменить план до реализации
  auth, не ослаблять проверки и не подменять flow статическим API key.

**Доказательство:** тест совместимости реального клиента и тестового issuer;
`make test CMD="--filter DocMcpHttpProtocolTest"`, `make lint`.
Токены и credentials в отчёт не включаются.

### Задача 2. Реализовать OAuth resource boundary и grants

**Файлы:** OAuthVerifier, DTO identity, Security classes, client grant Query/
Entity/Repository/Service/Command; security.yaml/services.yaml/deptrac.yaml;
новая migration; tests/Documentation/Security/DocMcpOAuthTest.php,
Service/DocMcpClientGrantTest.php.

- [ ] Добавить stateless firewall и централизованные challenge/errors.
  Admin cookie/ROLE_ADMIN не дают доступа к MCP; Bearer не даёт доступа к Admin.
- [ ] Реализовать SDK-backed JWT verification, issuer/JWKS cache и scope checks;
  для grant проверять текущую запись в БД на каждом запросе.
- [ ] Добавить миграцию через `make diff`; проверить additive SQL и `make migrate`
  только в dev/test. Allow/disable через Application Service, без токенов в БД.
- [ ] Проверить подпись/alg/typ/iss/aud/exp/iat/nbf, пустой scope/client identity,
  разные client_id/azp, malformed/duplicated Authorization и query/cookie tokens.
- [ ] Проверить unknown/disabled client, отзыв между двумя запросами, возобновление
  после allow, expired token после refresh и стабильность actor между токенами.
- [ ] Проверить ротацию kid, JWKS outage/timeout/неразрешённый redirect,
  отсутствие обращения по URL из токена и ограничение unknown-kid refresh.

**Доказательство:** реальные RSA подписи на синтетических ключах, MockHttpClient
для issuer/JWKS, реальная test-БД grants; HTTP 401/403/503 и headers.
`make test CMD="--filter 'DocMcpOAuthTest|DocMcpClientGrantTest'"`, `make deptrac`.

### Задача 3. Добавить HTTP gateway и metadata

**Файлы:** controllers, gateway/configuration/guard, общая MCP factory/mapper;
services/routes; tests/Documentation/Controller/DocMcpHttpTest.php.

- [ ] Реализовать точные host/path routes и metadata resource. Функция выключена
  по умолчанию, не ломает запуск Website/stdio при пустой HTTP-конфигурации.
- [ ] Конвертировать Symfony Request/Response через PSR-7 bridge; тело ограничить
  до buffering/decode, SDK factory создавать на запрос с опубликованным scope.
- [ ] Передавать проверенную identity от Symfony, игнорировать входные actor/
  profile/scopes и DOCS_MCP_* env stdio при HTTP-запросе.
- [ ] Проверить tools/list = 6 и прямой tools/call обоих write tools; не допускается
  ни изменение страницы, ни создание receipt. Admin URL/черновики не выдаются.
- [ ] Проверить metadata, OAuth challenge, неправильный Host/Origin, feature off,
  trailing slash, методы, Content-Type/Accept/version metadata и request ID.
- [ ] Проверить, что HTTP transport не применяет stdio output filter/STDERR-константы
  к веб-процессу. HTTP диагностика идёт через PSR logger, без body/tokens.
- [ ] Прогнать прежние stdio tests: изменения factory не меняют восемь tools,
  профили, idempotency и существующий transport.

**Доказательство:** `make test CMD="--filter 'DocMcpHttpTest|DocMcpProtocolTest'"`,
`make phpstan`, `make deptrac`. Token ошибок не превращается в 302 Admin login.

### Задача 4. Закрыть нагрузочные границы и протокольные ошибки

**Файлы:** guard/rate limit services, framework.yaml, bounded response adapter;
tests/Documentation/Mcp/Http/DocMcpHttpLimitsTest.php.

- [ ] Реализовать shared IP/client limits и 429 Retry-After через существующий
  PostgreSQL cache adapter; отказ не выполняет Query/tool.
- [ ] Проверить body >1 MiB, chunked/no Content-Length, response >1 MiB,
  длинный отсутствующий якорь/ID, malformed JSON и непрерывные неизвестные kid.
- [ ] Проверить отсутствие Authorization, raw token, Markdown и issuer credentials
  в HTTP errors, debug output, Symfony/Nginx logs и rate-limit keys.
- [ ] Проверить scope в search/count/sections/links/preview/validation и гонку
  unpublish → edit между SQL-запросами; прежние regression cases сохраняются.
- [ ] Проверить отключение клиента на другой реплике и недоступность grant DB:
  следующий запрос не наследует старое разрешение и не проходит анонимно.
- [ ] Убедиться, что неизвестный streaming/subscription method быстро завершается
  SDK error, не удерживает FPM worker и не требует изменения глобального timeout.

**Доказательство:** `make test CMD="--filter DocMcpHttpLimitsTest"` и измеренный
smoke на двух FPM; не дублировать unit assertions поверх уже проверенного SDK.

### Задача 5. Проверить реальные HTTP-образы и документацию

**Файлы:** Makefile, scripts/check-documentation-mcp-http.php,
`docker-compose.mcp-http-smoke.yml` (только изолированный smoke),
`scripts/mcp-http-smoke/issuer.conf`, `generate-fixtures.php`, README.

- [ ] Ввести `make documentation-mcp-http-check`: SDK HTTP client, обе revision,
  metadata → authorization → tools/list → search → get → links/preview/validation.
- [ ] Тестировать на реальном Nginx/двух production FPM и CLI без require-dev.
  Issuer/JWKS fixtures изолированы от production, синтетические ключи не становятся
  fallback-параметрами production validator. Не обращаться к внешнему issuer из CI.
  Test-only issuer — существующий Nginx image со статическими discovery/JWKS
  по HTTPS; RSA keys/test CA генерируются на запуск smoke в временный каталог.
  Проверяющему HttpClient передаётся только тестовый CA; verify_peer/verify_host
  не выключаются. Private key не монтируется в приложение и не коммитится.
  Тестовые grants/страницы создаются только в одноразовой smoke-БД.
- [ ] Проверить, что запросы одного клиента через разные реплики не требуют
  Mcp-Session-Id, sticky routing или shared in-memory session.
- [ ] Проверить cookie/session headers, HTTPS/proxy URI, byte limits, обычные
  Website/docs/Admin/CSRF smoke; Nginx менять только при доказанной необходимости.
- [ ] Описать URL, scope, получение токена у issuer, client_credentials flow,
  refresh/retry по истечении токена, типовые 401/403/429/503 и локальный отзыв grant.
- [ ] Сохранить конфигурацию реального клиентского SDK без секретов и записать
  проверенные версии клиента/SDK/protocol. Не обещать совместимость непроверенного UI.

**Доказательство:** `make ci`, `make php-production-build`,
`make php-production-smoke`, `make documentation-mcp-http-check`.
Новые test/smoke commands остаются определены Makefile.

### Задача 6. Выпустить выключенный endpoint, затем подключить клиента

**Файлы:** README, Makefile, согласованные production env/Compose/workflow и отчёт.

- [ ] Self-review полного diff, одно независимое read-only ревью OAuth/доступа/
  limits/границ; существенные замечания закрыть соответствующими regression tests.
- [ ] Зафиксировать конкретные issuer/audience/origin/config и клиентов до релиза.
  Подготовить production wiring с DOCS_MCP_HTTP_ENABLED=0 по умолчанию;
  существующие DOCS_MCP_* stdio flags не переиспользовать.
- [ ] Получить разрешение на конкретный release scope, если оно ещё не дано:
  production config, workflow, issuer/client provisioning и проверочные credentials.
  Предыдущее разрешение на stdio-релиз не выдаёт credentials нового OAuth issuer.
- [ ] Commit/PR → успешный CI → merge. Перед production migration — проверенный
  backup с TOC/checksum; применить additive grant migration и выпустить образы.
- [ ] При выключенном HTTP зарегистрировать разрешения smoke/рабочего client_id
  административной Console-командой. Credentials остаются у их владельцев.
- [ ] Включить endpoint для согласованного issuer/клиентов штатным release-процессом.
  Проверить оба FPM image SHA и отсутствие секретов в конфигурационном выводе.
- [ ] Из внешнего клиента получить короткий read-only access token и проверить
  реальный HTTPS endpoint, metadata, оба MCP protocol, отсутствие cookie,
  отказ без token/с неверным scope и отказ write tool. Production контент не создавать.
- [ ] Smoke token получать на стороне проверяющего клиента/runner; маскировать
  динамический token до использования, не передавать credentials через URL/логи
  и не встраивать token в SSH script. Данные выдачи не включать в PR/body.
- [ ] Проверить отзыв grant, зафиксировать SHA, backup/migration, CI/deploy ссылки,
  имя/версию клиентского SDK и минимальную инструкцию рабочего подключения.

## 9. Критерии приёмки и отключение

- Реальный внешний клиент подключается к https://vashfindir.ru/mcp/docs без SSH.
- OAuth metadata/challenge ведут к реальному issuer; корректный access token
  разрешает только шесть read tools и только опубликованные данные.
- Token другого resource/issuer, cookie Admin и произвольные claims/headers
  не расширяют права. Закрытые данные и секреты отсутствуют в ответах/логах.
- Отключение локального grant действует на следующем запросе любой реплики.
- Работают обе проверенные revision без protocol sessions, долгоживущего stream
  и Redis; limits/errors сохраняют понятный ответ и корреляцию request ID.
- Пройдены CI, независимое ревью, image/HTTP smoke и внешняя production проверка;
  существующие stdio, Website и Admin не изменили поведение.

Отключение: DOCS_MCP_HTTP_ENABLED=0 через штатное обновление FPM; точечный отзыв —
client:disable. Не полагаться на изменение env уже запущенного контейнера без
его обновления. Локальный stdio продолжает работать независимо.
Таблицу grants оставить при rollback кода: она аддитивна. Не откатывать данные
документации/всю БД для обычного выключения endpoint. У issuer отозвать/ротировать
credentials при необходимости отдельной операцией владельца.

## 10. Источники и проверка документа

Первичные источники, сверены 21.11.2026:

- [MCP HTTP authorization](https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization).
- [OAuth Client Credentials extension](https://modelcontextprotocol.io/extensions/auth/oauth-client-credentials).
- [Streamable HTTP 2026-07-28](https://modelcontextprotocol.io/specification/2026-07-28/basic/transports/streamable-http).
- [Legacy transport 2025-11-25](https://modelcontextprotocol.io/specification/2025-11-25/basic/transports).
- [Symfony 7.4 access token authentication](https://symfony.com/doc/7.4/security/access_token.html).
- [Symfony PSR-7 bridge](https://symfony.com/doc/7.4/components/psr7.html).
- [PHP MCP SDK](https://github.com/modelcontextprotocol/php-sdk).

SDK details дополнительно проверены по установленному 0.10.0: sessionless builder,
StreamableHttpTransport, JwtTokenValidator, metadata, Origin policy и middleware.

Self-review плана: соответствие текущему коду/границам, отсутствие предположения
о готовом issuer, различие M2M/browser OAuth, обе revision, права/отзыв/limits,
bootstrap/release/rollback, локальные ссылки и whitespace.
Подготовка документа не означает выполненные install, migration, тесты или
подключение OAuth. Посторонние документы не изменяются; commit/deploy этого
плана/HTTP-функциональности в рамках запроса плана не выполняются.


## Статус реализации 21.11.2026

Backend, тесты, isolated production-image smoke и рекомендации подготовлены в
`feat/documentation-mcp-http`; независимое ревью выполнено, замечания исправлены.
Локальный CI: 443 теста / 3686 assertions; HTTP/stdio/Website/Admin image smoke пройден.
Подробности и оставшиеся условия выпуска:
[отчёт внедрения](Stage_Documentation_MCP_HTTP_Implementation.md).
Production HTTP не включён; live issuer/client и production wiring ещё не созданы.
