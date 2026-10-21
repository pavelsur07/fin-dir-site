# Обновление PHP-FPM и PHP-CLI до PHP 8.4.27

Дата: 2026-10-21. Статус: реализация. Пользователь разрешил обновление, мерж и деплой.

## Цель и границы

Перевести dev и production образы FPM/CLI и CI на PHP 8.4.27.
Риск высокий: меняется runtime production, включая обработку запросов,
сессии и выполнение console-команд. Перед релизом нужны независимое ревью
и Production Gate по AGENTS.md §12.1.

PHP 8.4.27 — официальный security release:
https://www.php.net/releases/8_4_27.php
Изменения: https://www.php.net/ChangeLog-8.php#8.4.27

Не менять бизнес-логику, схему БД, Symfony, Composer packages, UI, Traefik,
conwix и соседние сервисы. Не выполнять composer update.

## Исходное состояние

- Четыре Dockerfile в `site/docker/{development,production}/{php-fpm,php-cli}/`
  используют плавающие `php:8.4-fpm-alpine` / `php:8.4-cli-alpine`.
  Версия работающих контейнеров из этих тегов не следует; её нужно измерить.
- Все четыре образа собирают `pdo_pgsql`, `pgsql`, `zip`, `bcmath`.
- CI `.github/workflows/ci.yml` использует `php-version: "8.4"`.
- Production workflow `.github/workflows/deploy-vashfindir.yml` собирает
  отдельные FPM/CLI образы, публикует `sha-<commit>` и `latest`, после CI
  выполняет deployment. Production Compose содержит две реплики FPM.
- Composer требует `>=8.4.1`, platform.php — `8.4.1`. Это минимальная
  платформа разрешения зависимостей; для patch-обновления менять её не нужно.
- Makefile пока не содержит целей сборки и проверки production-образов.

## Шаги

1. **Проверить исходную и целевую платформу.** Зафиксировать PHP/SAPI,
   расширения, Alpine, архитектуру, image ID/digest текущих dev и production
   образов. Проверить публикацию официальных 8.4.27 FPM/CLI тегов для нужной
   архитектуры. Выбрать одинаковую ветку Alpine, по возможности текущую,
   и закрепить точные теги с Alpine suffix и digest отдельно для FPM и CLI.
   Доступность тегов пока не подтверждена. Если их нет, дождаться публикации;
   не подменять релиз RC, сторонним образом или плавающим 8.4.

2. **Подготовить изменения runtime и воспроизводимые проверки.** В четырёх
   Dockerfile заменить только базовые PHP images. В CI закрепить 8.4.27 и
   проверить фактический PHP_VERSION. Сохранить существующие расширения,
   ini, пользователя, права, entrypoint и multi-stage сборку. Добавить в
   Makefile цели проверки версии/расширений обоих dev images и изолированной
   сборки/проверки обоих production images. Проверять точное равенство
   PHP_VERSION=8.4.27, а не только успешный запуск команды. Для production
   проверок не использовать production secrets, БД и тома. Обновить README
   с принятой версией и новыми make-командами.

3. **Проверить dev.** Выполнить `make build`, `make install`, новые проверки
   runtime, затем `make up`, `make check`, `make ci`, `make ds-check`.
   Проверить PHP-FPM SAPI/версию через `php-fpm -v`, конфигурацию через
   `php-fpm -t`, наличие расширений и отсутствие startup warnings. Для
   Composer добавить в make-проверку `composer check-platform-reqs`:
   она проверяет реальный runtime независимо от config.platform.
   Использовать тестовую БД по существующей схеме Makefile.

4. **Проверить production artifacts до публикации.** Через новые make-цели
   собрать оба production images с получением закреплённых базовых образов;
   проверить версию, расширения, FPM config, запуск Symfony в APP_ENV=prod,
   прогрев кеша, права app и работу OPcache. Проверить платформенные требования
   production vendor без dev dependencies через отдельный проверочный этап
   с Composer, не добавляя Composer в финальный runtime. Выполнить HTTP smoke
   через Nginx/FPM в изолированном окружении: health, публичная страница,
   вход/выход Admin и session/CSRF. Использовать тестовые credentials и
   синтетические данные, отключить реальные внешние уведомления.

5. **Подготовить релиз и независимое ревью.** Проверить diff, результаты CI
   и сборок, совпадение PHP в FPM/CLI и отсутствие изменений зависимостей.
   В существующем deploy workflow добавить проверки обоих собранных images
   до push и версии каждой FPM-реплики и CLI после deployment; команды
   проверок держать в Makefile. Не менять топологию Compose без необходимости.
   Зафиксировать предыдущую пару production image tags/digests и совместимый
   commit checkout для отката. Получить явное подтверждение production-части
   перед изменением production Dockerfile/workflow и запуском релиза:
   разрешение получено следующим сообщением пользователя.

6. **Выпустить через существующий pipeline.** После Production Gate и
   зелёного CI выпустить FPM/CLI из одного commit с одним SHA-тегом.
   Проверить обе FPM-реплики, одноразовый CLI, health, DB connectivity,
   публичный HTTP, Admin session и отсутствие новых startup errors/5xx.
   Не считать TCP healthcheck доказательством правильной версии PHP.
   Не обещать zero downtime без проверки фактической процедуры обновления.

## Откат

При сбое сборки или preflight не запускать deployment. При регрессии после
выкатки восстановить согласованную предыдущую пару FPM/CLI images по
сохранённым tags/digests и совместимый checkout через контролируемую
make-цель отката; повторить health/HTTP/DB проверки. Не использовать latest
для отката, не удалять volumes и не откатывать БД: схема здесь не меняется.
Откат может вернуть уязвимую версию PHP, поэтому это временная аварийная
мера с немедленной подготовкой исправленного релиза.

## Критерии приёмки

- Все четыре images и CI показывают PHP 8.4.27; обе production FPM-реплики
  и CLI после релиза используют проверенную пару artifacts.
- Все необходимые расширения и ini загружаются без ошибок; dev CI и
  production smoke проходят на новых images.
- Сессии/CSRF Admin, публичные HTTP-ответы и доступ к PostgreSQL работают.
- Нет незапланированных изменений composer.lock, схемы БД или UI.
- Независимое ревью, Production Gate, предыдущие artifacts и результаты
  проверок зафиксированы перед выпуском; процедура отката подготовлена.

## Выполнение

- Исходный commit: `31e92f6d5ea44673755d3a40bc7536fbfd5d9f6c`, последний успешный
  production workflow: https://github.com/pavelsur07/fin-dir-site/actions/runs/38438781927.
  Предыдущая пара образов: `ghcr.io/pavelsur07/vashfindir-site-php-{fpm,cli}:sha-31e92f6d5ea44673755d3a40bc7536fbfd5d9f6c`.
- Локальный исходный FPM image `ff60f05df004`: PHP 8.4.25, Alpine 3.24.2.
  Registry подтвердил новые multiarch теги PHP 8.4.27 на Alpine 3.24.
- Решение: работать в отдельной ветке существующего checkout; несвязанные
  незакоммиченные спецификации не включать в изменения.
- Решение: rollback возвращает предыдущую пару artifacts в совместимом checkout
  по сохранённым digest через make-цель; процедура аварийная и допускает краткое прерывание запросов.
- Buildx хранит локальное служебное состояние в `/tmp/vf-php-buildx`, поскольку
  стандартный каталог buildx недоступен на запись в текущей среде.
- Registry подтвердил предыдущие artifacts: FPM digest
  `sha256:582266774b96d40083868b902602cdce7b7961f79024e798ac2e28a11cc1ab27`,
  CLI digest `sha256:0eff28e1b3591be6cb7c262564b903aa8dd69218d52cfdffadde3a7e1851328a`.
- Проверки реализации: `make build`, `make install php-runtime-check up check`,
  `make ci ds-check`, `make php-production-build`, `make php-production-smoke`
  прошли. PHPUnit: 361 тест, 2746 assertions. Runtime checker отклоняет 8.4.25.
- Независимое read-only ревью: исправлены readiness race Nginx в smoke и откат
  по перезаписываемому SHA-тегу. Добавлены HTTP healthcheck и обязательная
  digest-пара в отдельном rollback override. Guard отклоняет latest; проверка
  Compose подтверждает разрешение обоих записанных digest. Native mbstring
  подтверждён в собранном production runtime.
- Production smoke на двух FPM-репликах прошёл: public HTTP без сессии,
  login, shared session, отказ поддельного CSRF для login/logout, logout.
  Изолированное окружение удалено после проверки.
