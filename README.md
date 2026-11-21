# Ваш ФинДиректор

Сайт на Symfony с Nginx и PHP-FPM. Dev, production и CI используют PHP 8.4.27;
Docker-образы закреплены на Alpine 3.24 с digest.

## Локальный запуск

```bash
make init
```

После запуска сайт доступен на <http://localhost:8001>.

Основные команды:

```bash
make up
make check
make logs
make down
```

## Проверки

```bash
make ci        # весь набор, как в .github/workflows/ci.yml
```

По отдельности: `make lint` (composer validate и audit, lint:yaml/twig/container),
`make cs` (форматирование кода, чинится через `make cs-fix`), `make phpstan` (level 8),
`make deptrac` (границы слоёв), `make test` (PHPUnit; `CMD="--filter ..."` для
точечного прогона).

Тесты ходят в отдельную базу `site_test` (`dbname_suffix` в `doctrine.yaml`),
dev-данные не трогают. `make test` сам создаёт её и накатывает миграции
(`make test-db`), а `dama/doctrine-test-bundle` откатывает каждый тест транзакцией.
В CI для этого поднимается одноразовый postgres.

Тот же набор гоняется на каждом PR и первым шагом деплоя — `deploy-vashfindir.yml`
вызывает `ci.yml` и раскатывает только после зелёного результата.

`tests/SmokeTest.php` проходит по всем маршрутам без параметров и требует 200.
Он ловит то, чего не видит статика: ошибки Twig во время рендера и любые 500-е.
Новый маршрут попадает в него сам, фикстур не требуется.

## База данных

PostgreSQL 17, база и пользователь — `site`. Пароль в dev — `secret`
(в `docker-compose.yml`, локальная база наружу не смотрит). Подключение идёт
через `DATABASE_URL`, порт на хост не публикуется — `make shell`, дальше
`psql -h site-postgres -U site site`.

Doctrine ORM с миграциями. Сущности — `site/src/Entity`, миграции —
`site/migrations` (в git, генерируются, не пишутся руками):

```bash
make diff      # сгенерировать миграцию по разнице сущностей и схемы
make migrate   # применить
```

Прод миграции применяет сам, шагом деплоя, из нового php-cli образа и **до**
раскатки php-fpm: упавшая миграция валит деплой, старые реплики продолжают
обслуживать запросы на старом коде. Отсюда требование к миграциям — быть
совместимыми со ещё живущим предыдущим кодом (добавлять nullable-колонки,
удалять их следующим релизом).

## Админка

CSS/JS отделены от Public Website; правила — [ADMIN_RULES.md](ADMIN_RULES.md).
`make admin-assets` собирает только Admin, `make admin-asset-version` выдаёт
хеш для admin layout, `make admin-assets-check` проверяет свежесть.
`make assets` собирает обе зоны. `make admin-browser-check` проверяет меню,
формы и адаптивность в Chromium на тестовых данных (нужен dev nginx на 8001).

`/admin` — раздел контент-менеджера (временно на Twig, см. `AGENTS.md` §8).
Пользователь один — `admin`, сущности нет. Пароль задаётся **хешем** в env
`ADMIN_PASSWORD_HASH`; открытый пароль нигде не хранится. В dev вход
`admin` / `admin` (хеш в `docker-compose.yml`, `$` экранирован как `$$`).

Сессии хранятся в Postgres (таблица `sessions`, `PdoSessionHandler`), потому
что php-fpm на проде в нескольких репликах. Таблицу в схему добавляет
`PdoSessionHandlerSchemaListener` из DoctrineBundle, поэтому `make diff` её видит
и не удаляет. Публичный сайт сессию не открывает: firewall стоит только на
`^/admin`, cookie с `path=/admin`. Сессия живёт по `session.gc_maxlifetime` из
php.ini (по умолчанию 24 минуты бездействия).

Сменить пароль:

```bash
make console CMD="security:hash-password"   # ввести пароль, скопировать хеш
```

Хеш положить в GitHub secret `VF_ADMIN_PASSWORD_HASH` и перевыкатить.

## Обращения с сайта

Формы сайта (виджет `sections/_lead_form.html.twig`, формы описаны в
`src/Lead/ValueObject/LeadFormCatalog.php`) отправляют заявки на `POST /lead`.
Заявка сначала сохраняется в базе, затем уходит уведомление в Telegram —
**без персональных данных**: только номер заявки и ссылка на карточку
`/admin/leads/{id}`. Подробности видны только в админке. Работа с заявками —
в админке, раздел «Обращения».

Сайт не обращается к Telegram напрямую: RU-хостинг не достаёт до
api.telegram.org (блокировка). Уведомление отправляется релею бота —
`POST https://mybots.vashfindir.ru/api/leads` с заголовком `X-Lead-Token`,
а бот (хостинг NL) пересылает готовый текст в группу. Уведомления включаются
двумя переменными (пусто — выключены, заявки всё равно сохраняются):
GitHub secrets `VF_LEAD_RELAY_URL` (URL релея) и `VF_LEAD_RELAY_TOKEN` (общий
секрет с ботом). Локально — те же имена в окружении перед `make up`.

Если Telegram был недоступен, заявка остаётся «без уведомления». Дослать за
последние 7 дней: `make console CMD="app:lead:notify-pending"` или кнопка в
карточке обращения.

## Production

Production-окружение описано в `docker-compose.prod.yml`. Для запуска нужны
`VF_SITE_APP_SECRET`, `VF_SITE_DB_PASSWORD` и `VF_ADMIN_PASSWORD_HASH`; PHP-образы публикуются workflow
`.github/workflows/deploy-vashfindir.yml`.

`VF_SITE_DB_PASSWORD` должен быть URL-safe: он подставляется в DSN, и `@`, `/`,
`%`, `$` в нём сломают либо разбор DSN, либо интерполяцию compose. Генерировать
`openssl rand -hex 32`. Менять пароль после первого деплоя мало — `POSTGRES_PASSWORD`
применяется только при инициализации тома, дальше нужен `ALTER USER`.

Reverse-proxy хоста вынесен в `infra/traefik/` — см. README там.

`docs.vashfindir.ru` обслуживает модуль Symfony `Documentation`: разделы,
инструкции, поиск и sitemap. Управление — в `/admin/docs/pages` и
`/admin/docs/sections` основного сайта. На docs-хосте вход в Admin не работает.

Создайте раздел, затем страницу с адресом, описанием и Markdown. После сохранения
доступен защищённый предпросмотр; публикация требует текста и описания. Ссылки
в текст и «Читайте также» выбираются по названию страницы через отдельное окно;
без JavaScript можно скопировать готовую Markdown-ссылку. До пяти страниц
«Читайте также» задаются по порядку, каждая ссылка с новой строки.

При публикации все цели ссылок должны быть опубликованы. Снятие цели или
изменение её заголовка блокируется, если это ломает ссылки опубликованных
материалов: входящие ссылки показаны в форме редактирования. Изменения
опубликованного текста применяются сразу при сохранении. Адрес страницы после
первой публикации, а раздела — после создания, сохраняется. Перенос между
разделами URL не меняет. Поиск использует PostgreSQL FTS (`russian`) и доступен
только для опубликованного контента.

Публичные пути: `/`, `/sections/{slug}`, `/articles/{slug}`, `/search`,
`/sitemap.xml`, `/robots.txt`. Прежние адреса вне этих пространств сохраняют
301 на тот же URI основного сайта. Отдельные контейнеры и новые секреты не нужны.
Проверки: `make documentation-http-check` (реальный локальный Nginx) и
`make documentation-browser-check` (Playwright на HTML из откатываемых тестовых
данных, без изменений server data и без внешних запросов).
План и результаты проверок: `docs/plan/Stage_Documentation_MVP.md`.

### Резервная копия перед миграциями

Каждый production deploy до обновления checkout и миграций создаёт PostgreSQL custom-format dump в
`$HOME/backups/vashfindir`, проверяет архив через `pg_restore --list`
и SHA-256, затем запускает миграции. Ошибка backup останавливает выкладку.
Каталог имеет права `0700`, архив и checksum — `0600`; данные и credentials
не публикуются в Actions artifacts или логах. Автоматического удаления копий нет.

`make site-database-backup` требует `VF_BACKUP_DIR`; по умолчанию выбирает
ровно один работающий PostgreSQL проекта Compose `vashfindir`.
`make site-database-backup-check` проверяет dump и настоящее восстановление
на синтетической БД в одноразовом контейнере без сети, не обращаясь к production.

### Выкатка без простоя

Замеряно на проде: 264 запроса подряд во время деплоя, все 200.

Держится на трёх вещах, каждую из которых легко сломать не заметив:

1. **`site` (nginx) не пересоздаётся.** Именно он — бэкенд Traefik, и его
   при его пересоздании Traefik остаётся без единственного сервера и отдаёт
   собственный 404. Деплой вызывает обычный `up -d` (no-op, если конфигурация
   не менялась) и `nginx -s reload`.
2. **Upstream резолвится динамически.** В `default.conf` адрес php-fpm идёт
   через переменную при `resolver ... valid=1s`. Со статическим `fastcgi_pass`
   nginx кэширует IP навсегда и после замены php-fpm стучится в мёртвый
   контейнер — поэтому раньше nginx и приходилось пересоздавать.
3. **php-fpm держится в двух репликах** и заменяется поочерёдно: новые
   поднимаются рядом со старыми, `--wait` ждёт healthcheck, и только потом
   старые удаляются.

Менять `site` (образ, метки, healthcheck) — законно, но такой деплой один раз
пересоздаст nginx и даст короткое окно 404. Правки только кода и php-образа
простоя не дают.

### Проверки обновления PHP

```bash
make build install
make php-runtime-check
make php-production-build
make php-production-smoke
```

Production smoke собирается без dev dependencies и использует отдельный Compose
project `vf-php-upgrade-smoke`, временный PostgreSQL и порт `127.0.0.1:18041`.
Проверяются версия/расширения PHP, FPM config, OPcache, права, прогрев кеша,
публичные страницы и Admin login/session/CSRF/logout на синтетических данных.
После проверки окружение удаляется. Эти же проверки выполняются в PR CI
и до публикации production-образов. Composer platform.php остаётся 8.4.1:
это минимальная платформа разрешения зависимостей, а не версия контейнера.

`make php-deployed-check` проверяет обе FPM-реплики и CLI. Workflow формирует
этот сценарий через `make --dry-run` на runner и выполняет его на deploy-хосте:
установка Make на production-сервер не требуется.
Команда требует обычные production environment variables и IMAGE_TAG релиза.
Workflow сохраняет предыдущие image references/digests в логе перед обновлением.
Перед аварийным откатом проверить наличие обоих images в GHCR и совместимость
checkout (включая public assets/config). Экспортировать production variables и
`PHP_ROLLBACK_FPM_IMAGE=ghcr.io/<owner>/vashfindir-site-php-fpm@sha256:<digest>`,
`PHP_ROLLBACK_CLI_IMAGE=ghcr.io/<owner>/vashfindir-site-php-cli@sha256:<digest>`
из записи предыдущего релиза. Команда `make php-production-rollback` использует
эту неизменяемую пару images, перезагружает
Nginx и проверяет health/DB. Она может кратко прервать PHP-запросы; после неё
нужно проверить публичный HTTP и Admin. БД и volumes не откатываются.

Если Make отсутствует на deploy-хосте, сформировать сценарий отката на машине
с Make через `make --no-print-directory --dry-run php-production-rollback > rollback.sh`
при заданных digest variables. Перед выполнением `bash -eu rollback.sh` на
сервере экспортировать те же digest variables и production environment.
Проверить совместимость checkout и содержимое сценария до запуска.

## MCP документации

MCP работает внутри Symfony: stdio для доверенных локальных интеграций и
отдельный HTTP transport для внешних клиентов. После получения кода установите
зависимости и обновите dev-БД/контейнерный кеш:

```bash
make install
make migrate
make console CMD="cache:clear --no-debug"
```

MCP запускается с `--no-debug`, поэтому после изменения кода очищается именно
этот вариант dev-контейнера Symfony. Production образы получают свежий кеш при выпуске.

Для доверенного локального клиента:

```bash
DOCS_MCP_ENABLED=1 DOCS_MCP_ACTOR=local-editor DOCS_MCP_PROFILE=draft-editor make documentation-mcp
```

В конфигурации MCP-клиента укажите `command: make`, `args: [documentation-mcp]`,
абсолютный рабочий каталог этого репозитория и эти переменные окружения.
Локальному stdio-клиенту необходим доступ к запуску Docker Compose рядом с приложением.
HTTP-подключение описано ниже и включается отдельно.

По умолчанию MCP выключен. При включении обязательный `DOCS_MCP_ACTOR`
идентифицирует доверенную интеграцию. `DOCS_MCP_PROFILE` принимает:

- `published-reader` (по умолчанию): чтение только опубликованного;
- `draft-reader`: также чтение черновиков;
- `draft-editor`: также создание и изменение черновиков.

Это полномочия локального запуска, не аутентификация удалённых пользователей:
оператор с Docker/DB доступом уже находится внутри доверенной границы.
Профили и actor задаёт оператор процесса, tools не могут их изменить.

Инструменты: `docs_list_sections`, `docs_search`, `docs_get_page`,
`docs_get_links`, `docs_validate_page`, `docs_preview_page`,
`docs_create_draft`, `docs_update_draft`. Два последних видны только редактору.
Поиск и списки разбиты по 20; page начинается с 1. Markdown возвращается
в `body`, заголовки — в `headings`; межстраничные ссылки — `[Текст](doc:123#anchor)`.
Предпросмотр возвращает безопасный HTML и защищённую ссылку Admin.
Публикация и управление разделами выполняются в существующем Admin UI.

Создание/изменение требует `requestKey` (UUID v4) и полной модели `page`:
`title`, `slug`, `sectionId`, необязательные `description`, `body`, `position`,
`metaTitle`, `metaDescription`, `relatedIds` (до пяти разных ID). Отсутствующие
необязательные поля обнуляются; update не является PATCH.
Для update также нужны `id` и `expectedVersion` из `docs_get_page`.
Исходный Markdown сохраняется без trim. `status` и поля публикации не принимаются.

При потере ответа повторяйте **тот же** запрос с **тем же** requestKey:
он вернёт прежний pageId с `replayed=true`. Тот же ключ с другим содержанием —
`IDEMPOTENCY_CONFLICT`. После записи перечитайте страницу для актуальной версии.
Устаревшая версия даёт `VERSION_CONFLICT`; новую запись в опубликованную страницу
MCP отклоняет. Даже снятая с публикации страница сохраняет прежний стабильный slug.

Изменения и квитанции `documentation_tool_receipt` сохраняются одной транзакцией;
квитанции автоматически не удаляются. Сбой не оставляет частичную запись.
В stderr пишутся actor/tool/ID/ключ/результат/длительность без тела статьи;
сбор и хранение stderr настраивает оператор клиента. Stdout содержит только MCP.
Ограничения: входная строка/ответ до 1 MiB; слишком длинная входная строка
отбрасывается SDK до JSON decode; следующая строка обрабатывается нормально.
Проверка большого графа ссылок ограничена 10 MiB внутренних текстов и при
превышении возвращает `LIMIT_EXCEEDED`, а не успешный отчёт готовности.

Проверки входят в `make test`/`make ci`. Точечно:

```bash
make test CMD="--filter DocMcp"
```

Protocol test использует одноразовую отдельную test-БД и реальный PHP SDK-клиент
с обеими ревизиями протокола. Production smoke дополнительно проверяет MCP
в CLI-образе без dev-зависимостей. Миграция только добавляет таблицу квитанций.

Для уже развёрнутого production checkout используется `make documentation-mcp-production`
с теми же переменными MCP, обязательным `IMAGE_TAG=sha-<полный SHA развёрнутого релиза>`
и штатным production-окружением Compose; запуск сверяет SHA работающих PHP-реплик и использует
production CLI-образ текущего релиза, не bind mount исходников. Этот target сам
не выполняет deploy/миграции. Не подставляйте dev launch вместо production launch.

Отключение: завершите MCP-процесс клиента и уберите `DOCS_MCP_ENABLED=1` из его
launch-конфигурации. Изменение окружения не отключает уже работающий процесс.
При откате кода таблицу квитанций оставляют; восстановление всей БД для обычного
отключения MCP не требуется. План и критерии: [Documentation MCP](docs/plan/Stage_Documentation_MCP.md).

### Внешний MCP по HTTPS

HTTP endpoint: `https://vashfindir.ru/mcp/docs`. По умолчанию он **выключен**
(`404`), независимо от локального stdio. Для включения нужны согласованные
OAuth issuer, клиент и production wiring; эта реализация сама не создаёт
OAuth-сервер, `/oauth/token` или credentials. Admin продолжает использовать
Symfony session cookie и CSRF. HTTP Bearer не даёт доступ в Admin.

Конфигурация Symfony (переменные должны быть переданы именно FPM-контейнерам):

| Переменная | Значение |
|---|---|
| `DOCS_MCP_HTTP_ENABLED` | `0` по умолчанию; `1` после настройки issuer/клиента |
| `DOCS_MCP_OAUTH_ISSUER` | Точный HTTPS issuer, без query/fragment/userinfo |
| `DOCS_MCP_HTTP_ORIGINS` | Дополнительные точные Origin через запятую; пусто по умолчанию, `*` запрещён |
| `DOCS_MCP_JWKS_HOSTS` | Дополнительные доверенные хосты JWKS; issuer host разрешён автоматически |
| `DOCS_MCP_OAUTH_CA_FILE` | Необязательный путь к CA bundle; TLS-проверки всегда включены |

Issuer публикует OAuth/OIDC discovery и JWKS. Принимаются подписанные `RS256`
JWT access tokens с `typ=at+jwt`, точным `iss`, audience
`https://vashfindir.ru/mcp/docs`, непустым `client_id`, строковым `scope`,
целочисленными `iat`/`exp` и временем жизни не более 300 секунд (clock skew 30с).
При наличии `azp` он должен совпадать с `client_id`. Нужен `scope=docs:read`.
ID token и токены другого resource не подходят.

Дополнительно оператор разрешает конкретный `client_id` для настроенного issuer:

```bash
make console CMD="app:docs:mcp:client:allow external-reader"
make console CMD="app:docs:mcp:client:disable external-reader"
```

Команды выполняются в нужном окружении Symfony с тем же issuer и БД, что у FPM.
`make console` использует dev-окружение; production-команду запускать через
штатный production CLI после согласованного выпуска. Миграция grants аддитивна.
Отзыв действует со следующего запроса любой реплики: положительного кеша grant нет.
Actor вычисляется из issuer/client_id и сохраняется при обновлении access token.

Внешний клиент получает access token у issuer и передаёт только заголовок
`Authorization: Bearer <access_token>`. Токены/credentials не передавать в URL,
репозиторий, команды с буквальным секретом или журналы. Token endpoint определяется
issuer; на сайте нет собственной выдачи токенов. Для M2M нужен согласованный
`client_credentials`; интерактивному клиенту может потребоваться Authorization Code
+ PKCE. Выбор зависит от фактического клиента и его версии.

Discovery ресурса:
`https://vashfindir.ru/.well-known/oauth-protected-resource/mcp/docs`.
Запрос без токена получает `401` и `WWW-Authenticate` со ссылкой на эту metadata.
Неверный токен — `401`, недостаточный scope/отозванный клиент — `403`, отказ issuer
без пригодного кеша — `503`. Metadata публикуется только при включённом HTTP.

HTTP всегда имеет профиль `published-reader`: шесть read tools, только
опубликованные страницы и связи. Draft/write tools отсутствуют даже у токена
с дополнительными scope. Поддерживаются проверенные SDK 0.10.0 ревизии
`2025-11-25` и `2026-07-28`; protocol headers/metadata формирует клиентский SDK.
Сессий MCP, sticky routing, подписок и долгих GET-stream нет. GET/DELETE дают `405`,
OPTIONS — `204`; POST возвращает конечный JSON. CORS по умолчанию выключен;
список разрешённых Origin сам по себе не включает browser CORS.

Лимиты: IP — 60/мин, actor — 120/мин, metadata — 30/мин; общий PostgreSQL cache
и advisory locks. Запрос/ответ до 1 MiB; issuer JSON до 256 KiB, outbound запросы
до 10/мин на URI, timeout 2с / max duration 3с, без redirects. `429` содержит
`Retry-After`. MCP-ответы `private, no-store`, без session cookie.
Отдельный HTTP audit пишет actor, известный tool, статус, duration и correlation
ID (строковый ID хешируется); body, Authorization и JWT в audit не передаются.

Проверки:

```bash
make ci
make php-production-build
make documentation-mcp-http-check
make php-production-smoke
```

`documentation-mcp-http-check` поднимает одноразовую БД, две production FPM-реплики
и тестовый HTTPS issuer; RSA/TLS keys и tokens генерируются во временный каталог,
удаляемый при завершении. Production issuer, credentials и данные не используются.
HTTP-проверка также входит в `php-production-smoke`, используемый CI.

Отключение — штатное обновление FPM с `DOCS_MCP_HTTP_ENABLED=0`; для одного клиента
достаточно `client:disable`. Grants при откате кода оставляются в БД.
План: [Documentation MCP HTTP](docs/plan/Stage_Documentation_MCP_HTTP.md).

Рекомендации по выбору issuer/клиента и оставшийся release scope:
[отчёт внедрения](docs/plan/Stage_Documentation_MCP_HTTP_Implementation.md).
