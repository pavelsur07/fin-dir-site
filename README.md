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

На deploy-хосте `make php-deployed-check` проверяет обе FPM-реплики и CLI.
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
