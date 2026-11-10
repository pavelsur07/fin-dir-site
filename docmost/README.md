# Docmost — docs.vashfindir.ru

Независимая база знаний SaaS на Docmost Community. Она хранит собственные
страницы, пользователей и вложения и не обращается к базе или сессиям Symfony.
Лицензия ядра — AGPL-3.0; платные возможности не включаются.

Состав: Docmost 0.97.0, PostgreSQL 18.6, Redis 8.10.3. Версии и digests
закреплены в `../docker-compose.docmost.yml`. Compose project — `vf-docmost`.
Только приложение входит в `traefik_web`; БД и Redis находятся в собственной
внутренней сети без опубликованных портов. Постоянные данные находятся в
volumes `vf-docmost_database`, `vf-docmost_storage`, `vf-docmost_redis`.

## Первое развёртывание

1. Создать DNS A `docs.vashfindir.ru` → `176.124.218.113` (текущий общий хост,
   см. `../conwix/README.md`). Если хост перенесён, использовать его новый IP.
   AAAA добавлять только при работающем IPv6 на том же сервере.
2. На сервере должен работать существующий Traefik с сетью `traefik_web`.
   Его конфигурация не меняется; используются `web`, `websecure` и `le`.
3. Из корня checkout на сервере выполнить:

   ```bash
   make docmost-init
   make docmost-pull
   make docmost-up
   make docmost-health
   ```

   `docmost-init` генерирует APP_SECRET и URL-safe пароль БД в `docmost/.env`
   с правами `0600` и отказывается перезаписывать существующий файл. Значения
   не выводятся. `.env` исключён из Git. В нём `DOCMOST_PUBLIC=false`:
   Traefik пока не публикует приложение, доступ есть на `127.0.0.1:3100`.

4. На своём компьютере открыть SSH-туннель:

   ```bash
   ssh -N -L 3100:127.0.0.1:3100 <ssh-user>@<ssh-host>
   ```

   Открыть `http://localhost:3100`, создать workspace и владельца с рабочим
   email и собственным паролем. APP_URL уже равен публичному HTTPS URL:
   cookie входа требует HTTPS, поэтому войти после завершения setup нужно
   через публичный адрес. Не публиковать пустую установку.

5. В `docmost/.env` установить `DOCMOST_PUBLIC=true`, затем выполнить:

   ```bash
   make docmost-up
   make docmost-public-health
   ```

   `docmost-up` перед публичным запуском проверяет наличие созданного
   workspace в работающей БД. Первый выпуск сертификата может занять время.
   Войти на `https://docs.vashfindir.ru`, проверить создание/сохранение
   страницы и совместное редактирование в двух браузерах. Traefik передаёт
   WebSocket-соединения Docmost без дополнительного middleware.

Вход редакторов требует учётную запись Docmost. Не включать public sharing
для внутренних материалов. Права участников назначаются внутри Docmost.
Старый вариант `doc.vashfindir.ru` не настраивается: согласован домен `docs`.

### Публичная главная

Корень `https://docs.vashfindir.ru/` перенаправляется на уже опубликованную
страницу «Документация сервиса Ваш Финдир». Посетителям не нужен аккаунт.
Для редактирования входить через `https://docs.vashfindir.ru/login`, затем
открывать рабочее пространство через `/home`.

Роутер главной действует только на `/`; вход, API, файлы и прочие страницы
сохраняют обычные маршруты и права доступа Docmost. Подстраницы доступны
согласно настройкам sharing владельца. При замене публичной главной обновить
`traefik.http.middlewares.vf-docmost-home.redirectregex.replacement` в Compose
и выполнить обычный релиз. Редирект `302` не закрепляет старый URL в браузере.

## Email

В `.env` предусмотрены `DOCMOST_SMTP_HOST`, `DOCMOST_SMTP_PORT`,
`DOCMOST_SMTP_USERNAME`, `DOCMOST_SMTP_PASSWORD`, `DOCMOST_SMTP_SECURE`,
`DOCMOST_MAIL_FROM_ADDRESS`. Без SMTP приложение работает, но доставка
приглашений и восстановление пароля не настроены. Для 587 обычно
`DOCMOST_SMTP_SECURE=false` (STARTTLS), для 465 — `true`.
После настройки выполнить `make docmost-up` и проверить доставку письма.
Значения с `$` и `#` заключать в одинарные кавычки по синтаксису Compose env.

## Обслуживание и обновления

Из корня репозитория:

```bash
make docmost-config         # проверка без вывода секретов
make docmost-ps
make docmost-health
make docmost-logs
make docmost-down           # остановить стек; volumes сохраняются
make docmost-up
```

Общий Traefik и соседние сайты эти команды не пересоздают.
Не выполнять `down --volumes` для production и не менять имя Compose project.
Не выводить обычный `docker compose config`: он раскрывает credentials.
Изменение пароля в `.env` не меняет пароль уже созданного PostgreSQL.
Сохранять исходный APP_SECRET — он нужен и при восстановлении.

Перед обновлением сохранить согласованный backup: остановить приложение,
оставив PostgreSQL и Redis работающими, выгрузить БД и файлы, сохранить `.env`
в закрытый каталог вне checkout:

```bash
umask 077
backup_dir="$HOME/backups/docmost-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup_dir"
make docmost-stop
make --no-print-directory docmost-db-dump > "$backup_dir/database.dump"
make --no-print-directory docmost-files-dump > "$backup_dir/storage.tar.gz"
cp docmost/.env "$backup_dir/.env"
cp docker-compose.docmost.yml "$backup_dir/"
make docmost-up
```

Хранить отдельную копию вне сервера. Восстановление проверять на отдельном
стеке с теми же версиями: сначала БД, затем storage и исходный `.env`.
Перезапись production-данных требует отдельного разрешения. Redis AOF хранится
в собственном volume; приведённая процедура сохраняет БД/вложения и не является
резервной копией находящихся в Redis очередей.

Для обновления изучить release notes, сделать backup, заменить закреплённые
версию/digest, выполнить `make docmost-smoke`, затем `make docmost-pull` и
`make docmost-up`. Docmost применяет свои миграции при старте. Возврат одного
старого image после миграций не считается безопасным откатом: может
потребоваться восстановление согласованного backup.

## CI и ручной deploy

`make docmost-check` проверяет конфигурацию, секреты, bootstrap и изоляцию.
`make docmost-smoke` использует отдельный project `vf-docmost-smoke`, сеть
`vf-docmost-smoke-web`, временный env и loopback-порт `18042`. Он создаёт только
синтетического владельца, проверяет HTTP/auth и сохранность workspace/storage
при пересоздании контейнеров, затем удаляет свой стек и volumes. Если project
уже занят, проверка завершается без его удаления. Требуется Compose 2.24.4+
для `!override`; исходники Symfony и действующие контейнеры не затрагиваются.

CI — `.github/workflows/ci-docmost.yml`. Ручной workflow
`Deploy docs.vashfindir.ru` сначала выполняет этот smoke, затем подключается
к хосту с существующими `SSH_*` и `DEPLOY_PATH` secrets. Секреты Docmost
остаются в `.env` на сервере; если файла ещё нет, workflow создаёт его
с закрытым публичным доступом. Checkout должен быть чистым и находиться на
выбранной ветке `main`/`master`; разрешён только fast-forward до проверенного
commit. Автоматического deploy по push нет.

Для первого запуска через Actions задать временный repository secret
`DOCMOST_BOOTSTRAP_PASSWORD` (случайный URL-safe пароль не короче 24 символов),
запустить workflow с `initialize=true` и email владельца в `owner_email`.
`make docmost-bootstrap` передаёт пароль через stdin внутри контейнера и
создаёт владельца только в пустой закрытой установке. Повторный запуск
не меняет существующие учётные записи. Пароль не выводится в логи; передать
его владельцу отдельно и удалить временный GitHub secret после установки.

После проверки DNS запустить workflow с `publish=true`. Команда
`make docmost-publish` разрешает маршрутизацию только после создания
workspace, атомарно обновляет `.env` с правами `0600`, затем workflow применяет
конфигурацию и проверяет публичный HTTPS. Вручную после создания владельца
можно выполнить `make docmost-publish docmost-up docmost-public-health`.

При отсутствии Make на сервере сформировать сценарий на машине с Make:

```bash
make --no-print-directory --dry-run docmost-init docmost-pull docmost-up docmost-health > /tmp/docmost-install.sh
```

Передать и проверить сценарий, затем выполнить его через `bash -eu` из корня
актуального checkout на сервере. Требуются Docker Compose и Python 3.
Ручной workflow формирует команды через Makefile тем же способом.
После bootstrap он проверяет внутренний health; публичные DNS/TLS, SMTP и
редактор проверяются отдельно после создания владельца.

Официальные инструкции: [установка](https://docmost.com/docs/installation),
[переменные окружения](https://docmost.com/docs/self-hosting/environment-variables),
[Traefik](https://docmost.com/docs/self-hosting/reverse-proxy/traefik),
[backup](https://docmost.com/docs/self-hosting/backups).
