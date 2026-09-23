# Stage 4 — Admin Auth: мини-раздел контент-менеджера (Twig, Symfony Security, без сущностей)

## Контекст

Сайт будет обрастать модулями, контент-менеджеру нужен защищённый вход в админку.
Решения пользователя: **вариант 1** (Twig-админка до появления React Admin),
пароль задаётся переменной окружения, сущностей пользователей нет,
**изменения prod-конфига подтверждены** (Production Gate).

Факты из кода, которые определяют дизайн:
- `.env` не используется (Dotenv выключен, `site/tests/bootstrap.php`). Env приходят из
  `docker-compose.yml`, `docker-compose.prod.yml`, `phpunit.dist.xml`, `.github/workflows/ci.yml`.
  Значит, «пароль в .env» — это env-переменная, на проде — GitHub secret.
- В prod работают 2 реплики php-fpm (`docker-compose.prod.yml`), поэтому файловые сессии
  не подходят: нужны общие сессии в Postgres.
- `symfony/security-bundle` не установлен, `framework.yaml` не настраивает session/csrf/trusted proxies.
- Цепочка на проде: Traefik (TLS) → nginx → fpm. Без `trusted_proxies` Symfony не видит HTTPS:
  cookie `secure: auto` не включится, redirect на логин пойдёт на `http://`.
- `SmokeTest` обходит все GET-маршруты без параметров и ждёт 200, а `/admin` для гостя отдаёт 302.
- CI не поднимает БД, поэтому тесты не должны зависеть от Postgres-сессий.

## Решения

| Вопрос | Решение |
|---|---|
| Пользователь | `memory` provider, один пользователь `admin` (логин фиксирован в `security.yaml`), роль `ROLE_ADMIN` |
| Пароль | env `ADMIN_PASSWORD_HASH` — хеш из `security:hash-password`, hasher `auto`. Открытый пароль нигде не хранится |
| Вход | `form_login` на `/admin/login`, `enable_csrf: true`, `default_target_path: admin_dashboard` |
| Выход | `/admin/logout` через POST-форму с CSRF (`logout.enable_csrf: true`) |
| Подбор пароля | `login_throttling` (5 попыток/мин, `symfony/rate-limiter`). Кеш лимитера у каждой реплики свой: на 2 репликах фактический лимит до 2×. Это приемлемо, фиксирую в отчёте |
| Сессии | `PdoSessionHandler` с `%env(DATABASE_URL)%` в dev/prod (URL→PDO DSN он строит сам, подключение ленивое); в test `session.storage.factory.mock_file` |
| Таблица сессий | миграция `CREATE TABLE sessions (...)` + индекс по `sess_lifetime`. `schema_filter` не нужен: DoctrineBundle сам добавляет таблицу в схему через `PdoSessionHandlerSchemaListener` (уточнено на ревью). Миграция только создаёт — безопасна |
| Cookie path | `/admin` — cookie не уходит на публичные страницы (уточнено на ревью) |
| Публичный сайт | firewall только на `^/admin`: публичные страницы сессию не стартуют, кешируемость не меняется |
| HTTPS за прокси | `framework.trusted_proxies: private_ranges`, `trusted_headers: [x-forwarded-for, x-forwarded-proto, x-forwarded-port]` |
| Cookie сессии | `cookie_secure: auto`, `cookie_httponly: true`, `cookie_samesite: lax` |
| Модуль | `site/src/Admin/Controller/` (новый код по модульной структуре; deptrac-регэксп `App\<Module>\Controller` уже покрывает) |
| Шаблоны | `site/templates/admin/` — отдельный минимальный layout со своим небольшим `<style>`, **без** правок `site/assets/**` и Tailwind: публичные assets и `vf_asset_version` не меняются, временная Twig-админка не связана с design system сайта. `noindex, nofollow` |
| Исключение из AGENTS §8 | дописать в `AGENTS.md` §8: «До появления React Admin административный раздел временно реализуется на Twig (`templates/admin/`, `src/Admin/`); страница входа остаётся Symfony-формой и после появления React» |

Вне объёма: сущность User, роли кроме `ROLE_ADMIN`, CRUD контента, React, Redis, изменения Traefik.

## Шаги реализации

1. **План в репо** — `docs/plan/Stage_4_Admin_Auth.md` (этот план + чек-лист готовности).
2. **Пакеты** — через `make shell` → `composer require symfony/security-bundle symfony/rate-limiter`
   (при необходимости `symfony/security-csrf`). Проверить созданные Flex-рецептом `config/packages/security.yaml`,
   `config/routes/security.yaml`, `bundles.php`.
3. **Конфиг**
   - `site/config/packages/security.yaml`: hasher `InMemoryUser: auto`; provider `admin_user` (memory, `admin` → `%env(ADMIN_PASSWORD_HASH)%`, `ROLE_ADMIN`);
     firewalls `dev`, `admin` (`pattern: ^/admin`, `form_login`, `logout`, `login_throttling`);
     `access_control`: `^/admin/login$` PUBLIC_ACCESS, `^/admin` ROLE_ADMIN.
     `when@test`: hasher с минимальной стоимостью (`cost: 4`).
   - `site/config/packages/framework.yaml`: `csrf_protection: true`, `session` (handler PdoSessionHandler, cookie-атрибуты),
     `trusted_proxies`/`trusted_headers`; `when@test`: `session.storage_factory_id: session.storage.factory.mock_file`.
   - `site/config/services.yaml`: сервис `Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler` с аргументом `'%env(DATABASE_URL)%'`.
   - `site/config/routes.yaml`: resource `../src/Admin/Controller/` (namespace `App\Admin\Controller`, type attribute).
4. **Миграция** — `make console CMD="doctrine:migrations:generate"`, SQL таблицы `sessions` в `up()` и `DROP` в `down()`; `make migrate`.
5. **Код**
   - `site/src/Admin/Controller/LoginController.php` — `GET|POST /admin/login` (`admin_login`), `AuthenticationUtils` → last username + ошибка; залогиненного редиректит на дашборд.
   - `site/src/Admin/Controller/DashboardController.php` — `GET /admin` (`admin_dashboard`), `#[IsGranted('ROLE_ADMIN')]`, рендер заглушки «разделы появятся по мере добавления модулей».
6. **Шаблоны** — `site/templates/admin/layout.html.twig` (шапка, имя пользователя, кнопка «Выйти» с CSRF-формой), `login.html.twig` (поля `_username`, `_password`, `_csrf_token`, ошибка через `error.messageKey|trans(error.messageData, 'security')`), `dashboard.html.twig`.
7. **Env**
   - `docker-compose.yml` (fpm + cli): `ADMIN_PASSWORD_HASH` — dev-хеш пароля `admin`, `$` экранирован как `$$`.
   - `docker-compose.prod.yml` (fpm + cli): `ADMIN_PASSWORD_HASH: ${VF_ADMIN_PASSWORD_HASH:?VF_ADMIN_PASSWORD_HASH is required}`.
   - `.github/workflows/deploy-vashfindir.yml`: `export VF_ADMIN_PASSWORD_HASH='${{ secrets.VF_ADMIN_PASSWORD_HASH }}'`.
   - `.github/workflows/ci.yml`: `ADMIN_PASSWORD_HASH` — фиктивный хеш для `lint:container`/`cache:warmup`.
   - `site/phpunit.dist.xml`: `ADMIN_PASSWORD_HASH` — хеш тестового пароля с `force="true"` (иначе dev-значение из compose перекроет его в `make test`).
   - `README.md`: как сгенерировать хеш (`make console CMD="security:hash-password"`), dev-логин `admin/admin`, секрет `VF_ADMIN_PASSWORD_HASH` в GitHub.
8. **Тесты**
   - `SmokeTest`: пропускать маршруты с префиксом `/admin`, их покрывает отдельный тест.
   - `site/tests/Admin/AdminAuthTest.php` (Functional): гость `/admin` → 302 на `/admin/login`; страница логина 200 + `noindex`; неверный пароль → остаёмся на логине с ошибкой; верный пароль → дашборд 200; POST без/с неверным CSRF → отказ; logout → повторный `/admin` снова 302; публичная `/` не отдаёт `Set-Cookie` (сессия не стартует).
9. **AGENTS.md** — исключение в §8 (см. таблицу решений).

## Уровни тестирования

- Unit — N/A: бизнес-логики нет, только конфигурация Security.
- Integration — PdoSessionHandler + таблица проверяются вручную в dev (ниже); в CI БД нет.
- Functional — `AdminAuthTest` + обновлённый `SmokeTest`.
- E2E — ручной сценарий через curl/браузер в dev.

## Проверка

1. `make migrate`, затем `make console CMD="doctrine:migrations:diff"` — изменений нет (schema_filter работает).
2. `make up`, в браузере `http://localhost:8001/admin` → логин `admin/admin` → дашборд → выход.
   В `sessions` появляется строка (`make console CMD="dbal:run-sql 'select count(*) from sessions'"`).
3. `curl -I http://localhost:8001/` — без `Set-Cookie`.
4. `make ci` зелёный (lint, cs, phpstan, deptrac, test).
5. Self-review полного diff и Claude Code review по AGENTS §14.7, отчёт `docs/plan/Stage_4_Admin_Auth_Report.md`.

## Перед релизом (действие пользователя)

Сгенерировать хеш боевого пароля (`make console CMD="security:hash-password"`), завести GitHub secret
`VF_ADMIN_PASSWORD_HASH` **до** деплоя: без него `docker-compose.prod.yml` упадёт на `:?`.
Миграция `sessions` применится при деплое штатным шагом.
