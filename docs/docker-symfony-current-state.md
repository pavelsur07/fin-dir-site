# Docker/Symfony: текущее состояние инфраструктуры

## 1) Текущие Symfony-сервисы

### Development (`docker-compose.yml`)
- `site` (`fin_dir_site`): собирается из `./site`, порт `8001:80`, mounts `./site`, `site_vendor`, `site_var`.
- `site-php-cli` (`fin_dir_site_php_cli`): профиль `cli`, собирается из `./site`, `working_dir=/var/www/html`, те же mounts.
- Оба сервиса получают env:
  - `APP_ENV=dev`
  - `APP_DEBUG=1`
  - `APP_SECRET=dev-site-secret`
  - `DEFAULT_URI=http://localhost:8001`

### Production (`docker-compose.prod.yml`)
- `site` (`vashfindir_site`): **использует image из GHCR**, а не `build`:
  - `image: ghcr.io/${GHCR_OWNER:-pavelsur07}/vashfindir-site:${IMAGE_TAG:-latest}`
- `site-php-cli` (`vashfindir_site_php_cli`): профиль `cli`, **тот же GHCR image**.
- Оба сервиса получают env:
  - `APP_ENV=prod`
  - `APP_DEBUG=0`
  - `APP_SECRET=${VF_SITE_APP_SECRET:?VF_SITE_APP_SECRET is required}`
  - `DEFAULT_URI=https://newversion.vashfindir.ru`

## 2) Как сейчас запускается `site`

- Runtime сейчас Apache-based:
  - `site/Dockerfile` использует `php:8.3-apache-bookworm`.
  - Включается `mod_rewrite` и переносится `DocumentRoot` на `/var/www/html/public`.
  - Веб-порт контейнера: `80`.
- Это **не** `nginx + php-fpm` и не встроенный PHP-server.

## 3) Как сейчас запускается `site-php-cli`

- В dev/prod используется отдельный сервис `site-php-cli` через `docker compose run --rm site-php-cli ...`.
- Это подтверждается Makefile-целями:
  - dev: `site-console`, `site-cache-clear`, `site-lint`, `site-shell` и др.
  - prod: `prod-site-console`, `prod-site-cache-clear`, `prod-site-lint-container` и др.

## 4) Build context и Dockerfile

- `docker-compose.yml` (dev): `site` и `site-php-cli` собираются из `context: ./site`.
- CI build/push (`.github/workflows/site-ci-cd.yml`):
  - `context: ./site`
  - `file: ./site/Dockerfile`
- Текущий `site/Dockerfile`:
  - stage `vendor`: `composer:2`, `composer update --no-dev ...`
  - runtime: `php:8.3-apache-bookworm`
  - `docker-php-ext-install intl opcache zip`
  - `EXPOSE 80`

## 5) Env-переменные Symfony (dev/prod/CI)

### Dev compose
- `APP_ENV=dev`
- `APP_DEBUG=1`
- `APP_SECRET=dev-site-secret`
- `DEFAULT_URI=http://localhost:8001`

### Prod compose
- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_SECRET=${VF_SITE_APP_SECRET:?VF_SITE_APP_SECRET is required}`
- `DEFAULT_URI=https://newversion.vashfindir.ru`

### CI (`site-ci-cd.yml`, job `symfony-checks`)
- `APP_ENV=test`
- `APP_SECRET=ci-secret`
- `DEFAULT_URI=https://example.test`

### Проверка `.env` файлов
- `site/.env` и `site/.env.dev` **в текущем дереве репозитория не найдены** (проверено `find`/`ls`).

## 6) Traefik labels для Symfony

В `docker-compose.prod.yml` сервис `site` имеет:
- `traefik.enable=true`
- `traefik.docker.network=traefik_web`
- HTTPS router:
  - `traefik.http.routers.vf-new.rule=Host(`newversion.vashfindir.ru`)`
  - `traefik.http.routers.vf-new.entrypoints=websecure`
  - `traefik.http.routers.vf-new.tls.certresolver=le`
- HTTP->HTTPS redirect router/middleware:
  - `traefik.http.routers.vf-new-http.rule=Host(`newversion.vashfindir.ru`)`
  - `traefik.http.routers.vf-new-http.entrypoints=web`
  - `traefik.http.routers.vf-new-http.middlewares=vf-new-https-redirect`
  - `traefik.http.middlewares.vf-new-https-redirect.redirectscheme.scheme=https`
- service backend port:
  - `traefik.http.services.vf-new.loadbalancer.server.port=80`

## 7) Makefile зависимости, связанные с Symfony

### Есть в текущем Makefile
- Переменные:
  - `SITE_BASE_IMAGE := vashfindir-site-php-apache-base:8.3-bookworm`
  - `SITE_BASE_DOCKERFILE := site/docker/base/Dockerfile`
  - `SITE_CLI`, `PROD_SITE_CLI`
- Цели base image:
  - `site-base-build`
  - `site-base-rebuild`
  - `prod-site-base-build`
  - `prod-site-base-rebuild`
- Symfony dev/prod цели (`site-*`, `prod-site-*`) для composer/console/cache/lint/router.

## 8) GitHub Actions шаги, связанные с Symfony build/deploy

### `.github/workflows/site-ci-cd.yml`
- Триггеры по `site/**`, workflow-файлу и `docker-compose.prod.yml`.
- `symfony-checks`:
  - `composer.lock` check
  - PHP 8.4 setup
  - `composer validate`
  - `composer update --no-dev`
  - `lint:twig`, `lint:container`
- `build-and-push-site-image`:
  - build/push Docker image (GHCR)
  - `context: ./site`, `file: ./site/Dockerfile`

### `.github/workflows/deploy-vashfindir.yml`
Текущий deploy-скрипт делает:
1. `docker-compose -f ... down || true`
2. `docker rm -f vashfindir_wp vashfindir_db vashfindir_site vashfindir_site_php_cli || true`
3. `docker-compose -f ... pull`
4. `docker-compose -f ... up -d --remove-orphans`

#### Риск (важно)
- Текущий deploy **явно останавливает** стек и **принудительно удаляет** контейнеры WordPress и MariaDB (`vashfindir_wp`, `vashfindir_db`).
- Это конфликтует с целевым ограничением “WordPress и MariaDB не трогать” в будущем процессе миграции Symfony.

## 9) Текущие проблемы относительно целевой архитектуры

1. Веб-runtime сейчас Apache/mod_php, а цель — `nginx + php-fpm + php-cli`.
2. Runtime сейчас на PHP 8.3 и Debian (bookworm), а цель — PHP 8.4 и Alpine-based images.
3. CI проверка использует `composer update --no-dev`, что менее детерминированно, чем lock-based install.
4. Deploy workflow затрагивает/перезапускает WordPress и MariaDB (см. риск выше).

## Какие файлы нужно менять дальше

Только Symfony Docker-инфраструктура:
- `site/Dockerfile` (или разделение на fpm/cli/nginx Dockerfile’ы)
- `site/docker/**` (конфиги nginx/php-fpm/entrypoint/php.ini)
- `docker-compose.yml` (только Symfony-сервисы)
- `docker-compose.prod.yml` (только Symfony-сервисы/labels backend для Symfony)
- `Makefile` (только Symfony-related цели)
- `.github/workflows/site-ci-cd.yml` (Symfony build/check pipeline)
- опционально Symfony-части deploy workflow (без изменений поведения WP/DB в рамках отдельных задач и согласований)

## Какие файлы нельзя менять

- Сервисы `wordpress` и `db`.
- `wp-content/**`.
- WordPress доменные Traefik настройки (`vashfindir.ru` блоки).
- Существующие секреты и обязательные env.

## WordPress и MariaDB не трогать

Неприкосновенные области:
- `docker-compose.yml`: блоки `db`, `wordpress`.
- `docker-compose.prod.yml`: блоки `db`, `wordpress` (включая healthcheck/labels/volumes).
- `wp-content/**`.

При последующей миграции Symfony эти блоки должны оставаться без изменений.

## Подготовлена новая структура Symfony Docker configs

Добавлены подготовительные конфиги для будущей миграции Symfony на `nginx + php-fpm` (без подключения к текущим compose/Dockerfile runtime):

- `site/docker/common/php/conf.d/common.ini`
- `site/docker/common/php/fpm/www.conf`
- `site/docker/common/nginx/default.conf`
- `site/docker/development/php/conf.d/development.ini`
- `site/docker/development/php/conf.d/xdebug.ini`
- `site/docker/development/php/fpm/www.conf`
- `site/docker/production/php/conf.d/production.ini`
- `site/docker/production/php/conf.d/opcache.ini`
- `site/docker/production/php/fpm/www.conf`

## Подготовлены отдельные Dockerfile для будущих Symfony PHP images

Добавлены:
- `site/docker/development/php-cli/Dockerfile`
- `site/docker/development/php-fpm/Dockerfile`
- `site/docker/production/php-cli/Dockerfile`
- `site/docker/production/php-fpm/Dockerfile`

Старый `site/Dockerfile` временно оставлен в legacy Apache runtime до миграции `docker-compose`/CI.

## 10) Ревизия минимальных PHP extensions и apk packages для PHP 8.4 Alpine

Проверка platform requirements выполнена командой:

- `cd site && composer check-platform-reqs`

Результат для lock-файла без `vendor`: требуются `php`, `ext-iconv`, `ext-xml`.
`ext-xml` обязателен для Symfony-стека, поэтому добавлен в новые Dockerfile. `ext-iconv` в `php:8.4-alpine` уже доступен и отдельно не устанавливается.

| Extension / package | Зачем нужен | Где нужен | Источник требования |
|---|---|---|---|
| `pdo_pgsql`, `pgsql` | Подключение Symfony-приложения к PostgreSQL | dev-cli, dev-fpm, prod-cli, prod-fpm | Текущая архитектура Symfony-контейнеров и runtime DB в проекте (`libpq`/`libpq-dev` уже были базовыми в Dockerfile) |
| `opcache` | Производительность PHP runtime в HTTP production | только prod-fpm runtime | Production-safe практика + `site/docker/production/php/conf.d/opcache.ini` |
| `intl` | Локализация/строковые операции Symfony | dev-cli, dev-fpm, prod-cli, prod-fpm | Symfony зависимости (`symfony/string`, framework stack) |
| `zip` | Работа с zip-архивами пакетов/артефактов | dev-cli, dev-fpm, prod-cli, prod-fpm | Composer dist-поток и стандартный Symfony toolchain |
| `xml` | Требование Symfony/platform check | dev-cli, dev-fpm, prod-cli, prod-fpm | `composer check-platform-reqs` (`ext-xml`) |
| `bcmath` | Доменное требование финансового проекта (decimal-safe вычисления) | dev-cli, dev-fpm, prod-cli, prod-fpm | Архитектурное runtime-требование (зафиксировано в задаче 2.3) |
| `xdebug` | Отладка в CLI разработке | только dev-cli | Требование dev-удобства; в production не нужен |
| `composer` | Установка зависимостей и консольные операции | dev-cli, prod-cli, prod-fpm (только в стадии `vendor`) | Процесс сборки и эксплуатации CLI |
| `libpq` / `postgresql-dev` | Runtime и сборка `pdo_pgsql`/`pgsql` | runtime: все 4 образа; build: все 4 образа | Нужно для PostgreSQL extensions |
| `icu-libs` / `icu-dev` | Runtime и сборка `intl` | runtime: все 4 образа; build: все 4 образа | Нужно для `intl` |
| `libzip` / `libzip-dev` | Runtime и сборка `zip` | runtime: все 4 образа; build: все 4 образа | Нужно для `zip` |
| `libxml2-dev` | Сборка `xml` | build: все 4 образа | Нужно для `ext-xml` |
| `tzdata` | Корректная TZ (`Europe/Moscow`) | dev-cli, dev-fpm, prod-cli, prod-fpm | `ENV TZ=Europe/Moscow` в Dockerfile |

### Итог по минимизации
- `prod-fpm`: не содержит `composer/git/unzip/bash/coreutils/xdebug`.
- `prod-cli`: переведён в multi-stage; `composer` используется только в build stage `vendor`, в runtime stage `composer` не остаётся.
- `dev-fpm`: без `composer/git/unzip/bash/coreutils`, без `xdebug` (HTTP debug через FPM не включали).
- `dev-cli`: оставлены `composer` и `xdebug`, а также dev-утилиты (`bash/coreutils/git/unzip`) для локальной работы.
- `gd/redis/mysql/node` не добавлялись, т.к. в platform requirements/конфигах Symfony этого требования нет.

## 11) Оптимизация времени сборки (задача 2.4)

- Build dependency для PostgreSQL extensions заменён с `postgresql-dev` на `libpq-dev` во всех новых PHP 8.4 Alpine Dockerfile.
- Причина: `postgresql-dev` на Alpine тянул тяжёлые зависимости (`postgresql18-dev`, `clang`, `llvm`), что резко замедляло сборку dev CLI образа.
- `dev php-cli` остаётся инструментальным (composer + xdebug), но без `opcache` для ускорения сборки и уменьшения лишних compile steps.
- `opcache` оставлен только в `production php-fpm`; для него добавлен `docker-php-ext-configure opcache --disable-opcache-jit` перед установкой.
