# Identity Service — Monorepo Implementation Plan

> **Для исполнителя:** применять `executing-plans` и выполнять задачи inline.
> Процесс выбирается по риску: meaningful contract/integration tests, одно
> независимое security review перед релизом. Автоматическая делегация, отдельное
> ревью каждого шага и обязательный TDD для документации не требуются.

**Goal:** развернуть переиспользуемый self-hosted OAuth/OIDC сервис с первым
проверенным M2M-потребителем — Documentation MCP.

**Architecture:** самостоятельный сервис `identity/` в текущем монорепо
`pavelsur07/fin-dir-site`, готовый Keycloak, PostgreSQL, Compose и HTTPS ingress.
Клиенты и token profiles управляются
версионированными manifests; бизнес-права остаются в потребителях.

**Tech Stack:** Keycloak 26.8.2 как проверенная база, PostgreSQL 17, Docker Compose v2,
Traefik 3.x, Python 3.13 tooling, Makefile, GitHub Actions. Все образы — exact
version + digest; tooling-зависимости — lock с проверкой hash.

**Spec:** [Identity_Service_Architecture.md](Identity_Service_Architecture.md).
Документы остаются в текущем `docs/plan/`; эксплуатационные инструкции сервиса — в `identity/docs/`.

## Поэтапная поставка

Выполнение начато: локальные этапы 1–5 реализованы и проверяются; CI/review/release и интеграция MCP выполняются далее. Этапы ниже являются вехами поставки;
технические задачи 1–7 далее описывают конкретные файлы, действия и проверки.
Переход к следующей вехе выполняется после её критериев готовности, без
повторного согласования обычных локальных действий. Production scope отделён.

| Этап | Что поставляем | Критерий завершения | Технические задачи |
|---|---|---|---|
| 1. Сервис в монорепо | `identity/`, собственные правила/Makefile/Compose, root-команды `identity-*`, ADR и фиксация версий | Identity запускается самостоятельно из текущего checkout; сайт продолжает работать по прежним командам | Начало задачи 1 |
| 2. Доказательство совместимости | Одноразовый Keycloak/PostgreSQL, реальный M2M token и вызов настоящего Symfony verifier | Корректный token принят; чужая audience, истёкший и испорченный tokens отклонены; ADR фиксирует поддержанный профиль | Остальная задача 1 |
| 3. Сетевая безопасность | HTTPS, закрытые DB/management, отдельный ограниченный admin ingress, production topology | Внешний клиент получает только разрешённые endpoints; admin login работает из доверенной сети, обходы Host/proxy отклонены | Задача 2 |
| 4. Подключение проектов по шаблону | Manifests, scopes/audience/clients, безопасные plan/apply, минимальные права автоматизации | Повторный apply ничего лишнего не меняет; токен одного проекта не подходит другому; secrets не выводятся | Задача 3 |
| 5. Эксплуатационная готовность | Backup/restore, rotation credentials/keys, audit и incident/upgrade runbooks | Backup реально восстановлен на isolated стенде; rotation и отказ IAM проверены; RPO/RTO измерены | Задача 4 |
| 6. CI и staging | CI, независимое security review, deployment в изолированное staging, внешние проверки | CI зелёный, существенные findings закрыты, HTTPS/изоляция/rotation на staging подтверждены; подготовлен release manifest | Первая часть задачи 5 |
| 7. Production Identity | Согласованные VM/DNS/TLS/secrets, immutable release и проверенная процедура восстановления | Production issuer/JWKS/token flow работают, admin защищён MFA и сетью, зафиксированы SHA/digests и rollback | Завершение задачи 5 |
| 8. Production MCP | Отдельный PR сайта, issuer env, локальный grant и управляемое включение HTTP | Внешний клиент читает опубликованное; drafts/writes/чужие tokens закрыты; отзыв grant работает на двух FPM | Задача 6 |
| 9. Повторное использование | Проверенный onboarding и подключение следующего выбранного API отдельной интеграцией | Новый проект использует стандартные OAuth-интерфейсы, собственные credentials/audience и tests без изменения IAM core | Задача 7; для реального API — отдельный integration plan |

### Контрольные точки и зависимости

- **После этапа 2:** подтверждаем применимость выбранного Keycloak profile к
  текущему MCP. Если proof не проходит, исправляем конфигурацию или пересматриваем
  ADR до покупки/подготовки production-инфраструктуры. Verifier не ослабляем.
- **После этапа 5:** сервис технически воспроизводим и восстанавливаем;
  это ещё не production release и не обещание высокой доступности.
- **После этапа 6:** готов конкретный пакет выпуска. Решение о production
  относится к его доменам, хостам, образам, credentials, backup и rollback.
- **После этапа 8:** достигнут первый работающий MVP — Identity + защищённый
  Documentation MCP. Расширять его можно без миграции текущего Admin на SSO.
- **После этапа 9:** доказано переиспользование. Пользовательский SSO, массовая
  миграция пользователей и HA планируются следующими отдельными работами.

Один этап может состоять из нескольких связанных commits/PR; его завершение
фиксируется результатом проверок, commit/image SHA и кратким отчётом. На локальных
этапах вместо публичного deployment используются disposable endpoints и synthetic data.

### Что и когда требуется от владельца

| Когда | Что нужно | Что можно делать до этого |
|---|---|---|
| Перед этапом 1 | Текущего workspace достаточно; проверить base branch и наличие нужной версии HTTP MCP | Сразу начать локальную реализацию в `identity/`, без нового репозитория |
| Перед этапом 6 | Адрес/доступ к staging, его hostname, ограниченный admin access и защищённый канал доставки secrets | Полностью выполнить локальные этапы 1–5 с временными TLS/credentials |
| Перед этапом 7 | Подтвердить регион/VM, production/admin hostname, DNS/proxy scope, RPO/RTO и конкретный production release | Завершить CI, staging, review и подготовку release manifest |
| Перед этапом 8 | Указать первого внешнего MCP-потребителя и место безопасного хранения его credentials; подтвердить release scope сайта | Проверить HTTP MCP с реальным issuer в isolated стенде |
| Перед реальной интеграцией этапа 9 | Выбрать следующий API и его владельца/требования к доступу | Доказать onboarding и изоляцию на synthetic втором API |

Секреты передаются защищённым каналом, не в чат. Оценку календарных сроков
делать после compatibility proof и выбора инфраструктуры: ожидание VM/DNS,
настройки сети и владельца аккаунта не является временем разработки.

## Global Constraints

- Сначала disposable compatibility proof; production до его успеха не создавать.
- Реализация — в `/home/deploy/projects/vashfindir-site/identity/`, текущем workspace.
  Новый репозиторий, sibling checkout и дополнительный доступ к файлам не требуются.
- Production — текущий сервер сайта по явному выбору владельца; отдельные state/releases/лимиты памяти (ADR0002).
- В MVP только registered M2M, один client → один API audience, `docs:read`.
- `resource-indicators`, CIMD, offline access и token exchange выключены; password grant выключен для клиентов приложений.
- MCP verifier сохраняет RS256 / at+jwt / client_id / точные issuer, audience / TTL≤300с.
- Prod и staging: разные экземпляры, БД, credentials и signing keys.
- Secrets не входят в manifests/git/images/artifacts/logs; output команд маскируется.
- Provisioning идемпотентен, не удаляет неизвестные ресурсы и не сбрасывает secrets.
- Realm export не заменяет PostgreSQL backup и проверку восстановления.
- Миграция Admin на SSO, изменение Finance/Analytics и HA — последующие отдельные этапы.
- Identity имеет отдельные Compose projects/state/secrets, tests, release artifact и deploy.
- Root Makefile делегирует `identity-*` в `identity/Makefile`, реализации команд не дублируются.
- Production/DNS/proxy/secrets/DB/deploy-workflow изменения проходят конкретный Production Gate.

## Review Focus

1. Токен другого проекта или окружения не открывает MCP; malformed/ID/expired
   tokens, дополнительный scope и изменённый resource не расширяют права — задачи 1, 3, 6.
2. Admin API на публичном hostname и spoofed forwarded headers не открывают
   административный доступ; оператор реально входит через разрешённую сеть — задачи 2, 5.
3. Повторное применение manifests, drift и partial failure не уничтожают clients,
   не меняют секреты/ключи и дают безопасный diff — задача 3.
4. Rotation, IAM outage и восстановленная старая БД не дают ложной гарантии
   мгновенного отзыва JWT или безопасного image-only rollback — задачи 4, 5, 6.
5. Новая интеграция воспроизводится из шаблона без Management API credential
   в приложении, доступа к БД IAM и копирования security-кода — задачи 3, 6, 7.

## 1. Структура монорепо и области изменений

```text
vashfindir-site/
├── AGENTS.md                          # явная граница Symfony ↔ vendor IAM
├── Makefile                           # новые wrappers identity-*, прежние targets сохраняются
├── site/                              # существующий Symfony/MCP consumer
├── conwix/                            # прежний отдельный deployment
├── infra/traefik/                     # прежний общий proxy хоста
├── identity/
│   ├── AGENTS.md                      # IAM/configuration/security/release rules
│   ├── README.md
│   ├── Makefile                       # реализация только Identity operations
│   ├── compose.yaml
│   ├── compose.smoke.yaml
│   ├── compose.prod.yaml
│   ├── versions.env                   # exact image references, без секретов
│   ├── .env.example
│   ├── .gitignore
│   ├── docker/keycloak/Dockerfile     # optimized image из pinned official base
│   ├── tooling/{Dockerfile,pyproject.toml,requirements.lock}
│   ├── config/
│   │   ├── keycloak.conf
│   │   ├── schema/project-v1.schema.json
│   │   └── projects/{documentation,example}.json
│   ├── ingress/{compose.yaml,routes.yml}
│   ├── scripts/
│   │   ├── bootstrap.sh
│   │   ├── provision.py
│   │   ├── backup.sh
│   │   ├── restore-check.sh
│   │   ├── rotate-client.py
│   │   ├── smoke.py
│   │   └── package-release.sh         # allowlist identity/, manifest + checksums
│   ├── tests/
│   │   ├── conftest.py
│   │   ├── test_manifests.py
│   │   ├── test_provisioning.py
│   │   ├── test_token_profile.py
│   │   ├── test_project_isolation.py
│   │   ├── test_ingress.py
│   │   ├── test_release_package.py
│   │   ├── test_ci_impact.py
│   │   └── consumers/symfony-mcp-verifier.php
│   └── docs/
│       ├── adr/0001-registered-m2m-profile.md
│       ├── adr/0002-monorepo-boundaries.md
│       ├── integrations/{documentation-mcp,new-project}.md
│       └── runbooks/                  # network, backup, restore, rotation, release
├── docs/
│   ├── architecture/PATTERNS.md       # точечная граница vendor IAM и приложения
│   └── plan/Identity_Service_*.md     # текущие архитектура и план
└── .github/
    ├── CODEOWNERS                     # сохраняет прежних owners, добавляет Identity scope
    └── workflows/
        ├── identity-ci.yml            # PR gate + impact jobs + workflow_call
        ├── deploy-identity.yml        # явный Identity release по SHA
        └── ...                       # существующие workflows сайта/Conwix/Traefik
```

Далее пути в блоках **Файлы** относятся к `identity/`, если явно не написано
**root** или `site/`. Root workflow paths нельзя размещать внутри `identity/`:
GitHub обнаруживает workflows в root `.github/workflows/`.

Отдельный публичный proxy не создаётся. На общем хосте используются labels `identity/compose.prod.yaml` для существующего Traefik;
второй proxy на 80/443 не создаётся. Топология Identity не добавляется в обычные
root Compose сайта: прежние `make up/down`, `make ci` и site deploy сохраняют свою область.

Root Makefile — единый пользовательский интерфейс текущего repo; компонентный
Makefile содержит реализации Identity и запускается теми же wrappers. Изолированный
release package сервиса использует его собственный Makefile без root checkout,
`../site`, исходников Conwix или глобальной БД сайта. Consumer source требуется
только contract tests, а не работе Identity на production.

Root AGENTS/PATTERNS должны явно разрешить самостоятельный vendor IAM в `identity/`
и делегирование компонентных Make targets. Это не новый Symfony business-module,
не замена текущего Admin session auth и не изменение Public Website rendering.

## 2. Договорённость по Make targets

Это будущие команды **корневого Makefile текущего репозитория**, сейчас они не существуют:

| Target | Назначение и результат |
|---|---|
| `make identity-validate` | Schema/Compose/versions/secrets-boundary checks, exit 0 |
| `make identity-build` | Optimized Keycloak и tooling images из зафиксированных base/dependencies |
| `make identity-up` / `make identity-down` | Только dev; shutdown не удаляет state volumes |
| `make identity-test` | Unit + isolated real-Keycloak integration, cleanup transient stack |
| `make identity-contract-probe IDENTITY_CONSUMER_REVISION=<sha>` | Реальный consumer verifier, не production deployment |
| `make identity-project-plan IDENTITY_ENV=staging IDENTITY_PROJECT=documentation` | Без записи, только redacted diff |
| `make identity-project-apply IDENTITY_ENV=staging IDENTITY_PROJECT=documentation` | Только owned resources, summary без credentials |
| `make identity-smoke IDENTITY_ENV=staging` | Non-destructive checks с отдельным smoke client |
| `make identity-backup IDENTITY_ENV=production` | Согласованный production backup, checksum/TOC |
| `make identity-restore-check IDENTITY_BACKUP_MANIFEST=...` | Disposable restore, никогда не production DB |
| `make identity-rotate-client IDENTITY_ENV=staging IDENTITY_CLIENT=...` | Явная ротация с проверкой old/new поведения |
| `make identity-ci` | Validate, tests, contract probe и backup/restore rehearsal |
| `make identity-deploy IDENTITY_ENV=production IDENTITY_RELEASE=<sha>` | Только согласованный immutable release |

IDENTITY_ENV допускает только `dev|smoke|staging|production`; destructive operations
отдельны от обычных `up/down/apply`. Ошибка окружения останавливает команду.
Команды не принимают client_secret/password/token как shell argument.

## Задача 1. Bootstrap Identity в монорепо и compatibility proof

**Файлы:** AGENTS.md, README.md, Makefile, versions.env, docker/keycloak/Dockerfile, tooling/*;
**root:** AGENTS.md, Makefile, docs/architecture/PATTERNS.md.
Остальные service files:
compose.yaml, compose.smoke.yaml, tests/test_token_profile.py,
tests/conftest.py, tests/consumers/symfony-mcp-verifier.php,
docs/adr/0001-registered-m2m-profile.md, docs/adr/0002-monorepo-boundaries.md.

**Вход:** архитектурное предложение; Symfony HTTP MCP из того же monorepo.
Проверить base branch и зависимость от HTTP MCP PR: отдельный Identity PR не
должен случайно включить незавершённые посторонние изменения.
Проверенная исходная ревизия на дату плана: `670eb4c3c259649899bdd5df57b6010b505555eb`;
перед исполнением проверить актуальный согласованный commit и зафиксировать его.
**Выход:** реальные JWT, успешно принятые текущим verifier, ADR с ограничениями
и lock/digests. Ни одного изменения production.

- [ ] Создать feature branch в текущем repo и каталог `identity/`. Зафиксировать
  root/service AGENTS, ADR о монорепо и root Make wrappers; прежние команды сайта
  не переопределять. Архитектуру/план оставить по текущим путям.
- [ ] Consumer contract tests по умолчанию используют site source того же Git SHA.
  Если HTTP MCP ещё не в согласованной базе, проверить pinned consumer revision
  из git history этого же repo и явно записать dependency; не копировать verifier
  и не смешивать unrelated changes в Identity PR.
- [ ] Проверить security updates Keycloak, совместимость PostgreSQL 17 и registry;
  записать exact image refs/digests. Tooling dependencies HTTPX/PyJWT[crypto]/pytest/jsonschema
  фиксировать hash lock; использовать поддерживаемые patch versions Python 3.13.
- [ ] Собрать optimized runtime из official base с PostgreSQL/health/metrics
  build settings, без credentials и runtime secrets в build args/layers.
  Зафиксировать собственный image digest и commit SHA, не fork исходников IAM.
- [ ] Поднять `identity-smoke` с одноразовой БД, временным HTTPS/CA и synthetic
  credentials. Fixtures создаются текущим UID и удаляются при success/failure.
- [ ] Через временный bootstrap создать realm `projects`, M2M client, scope,
  Audience mapper, at+jwt header и service-account client_id mapping.
- [ ] Получить token с `grant_type=client_credentials`, `scope=docs:read`,
  `resource=https://vashfindir.ru/mcp/docs`; проверить подпись/claims по JWKS.
- [ ] PHP consumer probe импортирует **настоящие** DocMcpHttpSettings,
  DocMcpIssuerHttpClient и DocMcpOAuthVerifier из pinned Symfony CLI image и
  вызывает `verify(token)`. Клиент HTTP доверяет только временной CA; token
  читается из transient file, а не CLI argument. БД Symfony для probe не нужна.
- [ ] Image для probe собрать штатным root `make php-production-build` из
  pinned site source этого же repo либо использовать проверенный image@digest.
  Источник и image ID записать; никакие production credentials/fetch из другого
  private repo для этого не нужны. Site deploy не запускается.
- [ ] Проверить отказ токену второго synthetic client с другим audience,
  подпорченному и просроченному token. Не заменять реальный verifier его копией.
- [ ] В ADR зафиксировать отсутствие native RFC8707/CIMD и ограничение single
  audience. Если без experimental/custom SPI контракт не проходит, остановить
  adoption этого варианта и пересмотреть профиль/платформу до production.

**Проверки:** `make identity-validate`; `make identity-test`; `make identity-contract-probe IDENTITY_CONSUMER_REVISION=<sha>`.
Успех: подпись и identity реального token приняты, негативные случаи отклонены,
никакие private keys/tokens не напечатаны, transient stack и fixtures убраны.

## Задача 2. Production topology и сетевые границы

**Файлы:** compose.prod.yaml, config/keycloak.conf, ingress/*,
.env.example, tests/test_ingress.py, docs/runbooks/network.md.
**Вход:** digests/совместимый profile из задачи 1.
**Выход:** проверенный локально production topology без выкладки на живой хост.

- [ ] Описать optimized image build/start, фиксированные public/admin hostname,
  только доверенный proxy, закрытую DB network и отдельные volumes.
- [ ] Выставить HTTPS frontend, закрыть `/admin` и Admin REST на публичном host;
  admin hostname ограничить на ingress реальным network allowlist/VPN.
- [ ] Не публиковать DB и management ports. Health/metrics доступны только
  private monitoring path. Настроить readiness; limit queued requests и
  операционные timeouts измерить на smoke и записать в runbook.
- [ ] Проверить TLS failure без CA, чужой Host, spoofed forwarding headers,
  запросы `/admin`, `/metrics`, health и Admin REST из недоверенной сети.
- [ ] Проверить вход администратора из доверенной сети, включая необходимые
  realm authentication/static resource paths: запрет Admin API не должен ломать login.
- [ ] Production secrets доставлять вне release tree; container non-root там,
  где это поддерживается image, без privileged/Docker socket mount в Keycloak.

**Проверки:** `make identity-validate`; `make identity-test`; isolated production-topology smoke.
Успех: публичные OAuth endpoints работают; private interfaces закрыты;
readiness корректен; администратор имеет рабочий разрешённый путь входа.

## Задача 3. Воспроизводимое подключение проектов

**Файлы:** config/schema/project-v1.schema.json, config/projects/*.json,
scripts/provision.py, scripts/bootstrap.sh, tests/test_manifests.py,
tests/test_provisioning.py, tests/test_project_isolation.py.

**Контракт manifest v1:** schemaVersion=1, projectId, environment,
resourceAudience, clients[{clientId, type=m2m, allowedScopes, secretRef}].
Все разрешения объявлены явно; validation через JSON Schema. Unknown fields, duplicate clients, wildcard
audiences и literal secrets отклоняются. `secretRef` указывает на protected
runtime secret в namespace Identity, значения в manifest нет; ref не является
произвольным URL/путём к файлу. Production placeholders запрещены. Keycloak
resources помечаются `identity.managedBy=app-service-identity` и projectId;
существующий unowned resource с совпавшим именем не присваивается автоматически.

**Выход:** plan/apply через Admin REST, совместимый с задачей 1 token profile.

- [ ] Реализовать validation и redacted read-only plan с детектированием drift.
- [ ] Apply обновляет только owned clients/scopes/mappers в указанном realm;
  partial failure возвращает ненулевой exit и безопасный список выполненных шагов.
  Scope/role mappings задаются явно; service accounts не получают универсальный
  Full Scope Allowed или административные роли из шаблона приложения.
- [ ] Первичное создание secret выполняется через отдельный protected канал;
  если доставить его безопасно невозможно, provisioning прекращается.
  Повторный apply не запрашивает/не печатает существующий secret.
- [ ] Bootstrap superadmin используется однократно. Настроить MFA operator,
  break-glass и минимальные права provisioning service account, затем удалить
  временный bootstrap admin. Никакого Management API credential в приложениях.
- [ ] Проверить первый apply, второй apply с нулевым diff, partial failure/retry,
  отсутствие удаления неизвестного client и отсутствие rotation при повторе.
- [ ] Проверить две аудитории и два окружения реальными токенами: каждый
  resource validator отвергает чужие issuer/audience/scope. Изменённый `resource`
  не добавляет новый audience; не ожидать `invalid_target` при отключённой feature.

**Проверки:** `make identity-project-plan ...`; `make identity-project-apply ...`; `make identity-test`.
Успех: repeat apply безопасен, изоляция доказана, onboarding воспроизводим.

## Задача 4. Backup, rotation и восстановление

**Файлы:** scripts/backup.sh, scripts/restore-check.sh, scripts/rotate-client.py,
docs/runbooks/{backup-restore,rotation,incident,upgrade}.md, соответствующие tests.
**Выход:** измеренный recovery report, а не только наличие dump-файла.

- [ ] Делать PostgreSQL logical backup согласованной БД, checksum, TOC и manifest
  с image/config SHA; включить нужные keystore/encryption материалы отдельной
  защищённой процедурой. Внешняя зашифрованная копия и retention описаны явно.
- [ ] Restore target по умолчанию только disposable. Проверить отказ операции,
  если target указывает production network/DB или совпадает с исходной БД.
- [ ] Восстановить grants/clients/keys на isolated host и проверить token flow;
  старый issuer в dump не заменять молча. Измерить фактические RPO/RTO относительно
  предложенных 24ч/4ч и утвердить пригодность до production.
- [ ] Проверить rotation client credential и signing key: новые tokens работают,
  старый public key сохраняется на согласованное время жизни tokens + skew/cache;
  этап удаления старого key выполняется отдельно после проверки потребителей.
- [ ] Проверить ошибочный backup/checksum, неуспешную rotation и IAM outage.
  При emergency key compromise описать ускоренный запрет, учитывая JWKS caches.
- [ ] Зафиксировать rollback matrix image/schema. После необратимой DB migration
  использовать согласованный restore/cutover, не запускать старый image на новой схеме.

**Проверки:** `make identity-backup IDENTITY_ENV=smoke`; `make identity-restore-check IDENTITY_BACKUP_MANIFEST=...`;
`make identity-rotate-client IDENTITY_ENV=smoke IDENTITY_CLIENT=...`; rotation/incident tests.

## Задача 5. CI, независимое ревью и выпуск Identity

**Файлы root:** .github/workflows/identity-ci.yml,
.github/workflows/deploy-identity.yml, .github/CODEOWNERS, Makefile.
**Файлы Identity:** Makefile, scripts/package-release.sh, tests/test_release_package.py,
tests/test_ci_impact.py, docs/runbooks/release.md.
**Вход:** задачи 1–4; выбранные domain/VM/region, защищённый admin access и secrets delivery.

- [ ] PR CI: config validation, exact dependency/image checks, real Keycloak
  token/negative/isolation/provision tests, consumer probe и restore rehearsal.
  Все secrets synthetic, issuer локальный HTTPS; внешний production не вызывается.
  Consumer source — тот же monorepo SHA; исключительный pinned ref берётся только
  из versioned contract definition. CI token имеет contents:read, дополнительные
  cross-repository secrets не нужны, persist-credentials выключен.
- [ ] Actions фиксировать immutable commit references; CI token permissions
  минимальны. Release manifest содержит application/config SHA и image digests.
- [ ] Реализовать явную impact matrix: `identity/**` и его workflow definitions
  запускают IAM suite и consumer probe; `site/src/Documentation/**`, `site/config/**`,
  `site/composer.*`, `site/docker/**` запускают consumer contract checks также
  при неизменном Identity. Root Makefile/AGENTS/PATTERNS и изменения самой
  классификации запускают все относящиеся к ней проверки.
- [ ] Required job `identity-gate` стартует на каждом PR; определение changed
  paths выполняется внутри workflow. Gate с `always()` проверяет результаты
  нужных jobs и отклоняет их failure/cancelled/unexpected skipped. Workflow-level
  paths не должны оставлять required context в Pending. Существующий site CI
  сохраняется; его оптимизация не входит автоматически в IAM bootstrap.
- [ ] Покрыть impact matrix: Identity-only diff, verifier-only diff, shared-root
  diff, unrelated website diff и изменение самого workflow. Для unrelated diff
  gate завершается успешно без IAM jobs; при падении обязательного job gate падает.
  Не заявлять, что существующий site CI уже пропускает unrelated PR.
- [ ] Пакет релиза собирать из git tree `identity/` точного проверенного SHA,
  без чтения untracked runtime files. Проверить состав, checksums и отсутствие
  `site/`, `conwix/`, secrets, абсолютных/выходящих наружу symlinks. После распаковки
  компонентный Makefile и runtime operations работают без checkout монорепо.
- [ ] Deploy workflow в MVP запускается явно по SHA защищённой основной ветки.
  Перед выдачей production credentials он требует полную Identity/recovery/consumer
  проверку именно этого SHA, совпадение artifact digest и release manifest.
  PR gate с пропущенными по impact jobs не является release evidence.
- [ ] Использовать отдельные `identity-staging` / `identity-production` deployment
  environments, secrets и deployment concurrency. Добавить CODEOWNERS/review policy
  для Identity и управляющих workflows. Возможности защиты environments проверить
  для текущего GitHub account; при их отсутствии сохранить явный operator Gate.
  Не использовать production secrets в PR или `pull_request_target` с untrusted code.
- [ ] Проверить независимость релизов: Identity-only release не вызывает site/Conwix/
  общего Traefik deployment; root site `make up/down` не меняет IAM state;
  rollback Identity не переключает image/БД сайта. На общем хосте не разделять
  один mutable git checkout с существующими deployment scripts.
- [ ] Сделать self-review и одно независимое read-only security review полного
  результата; закрыть обоснованные существенные findings с regression checks.
- [ ] Сначала staging deployment и внешний TLS/Admin/network/rotation smoke.
  Protected deployment credentials не доступны fork PR или произвольному branch.
- [ ] Подготовить конкретный Production Gate: host/domain/proxy scope, image
  digests, secrets, backup/restore report, admin MFA, rollback и допускаемый простой.
  При использовании общего Traefik явно указать затрагиваемые labels/hostnames.
- [ ] После разрешённого Gate выпустить production Identity, проверить discovery,
  JWKS, issuer/TTL и отсутствие публичных admin/management interfaces.
  Зафиксировать фактическую готовность, SHA и CI/deploy ссылки.

**Проверки:** `make identity-ci`; `make identity-smoke IDENTITY_ENV=staging`; после Gate —
`make identity-deploy IDENTITY_ENV=production IDENTITY_RELEASE=<sha>` и `make identity-smoke IDENTITY_ENV=production`.
Успех задачи не означает автоматически подключённый production MCP.

## Задача 6. Подключение Documentation MCP отдельным PR

**Область монорепо:** `site/` и его release, отдельный integration PR.
Одно хранение кода не связывает site/Identity deployment автоматически.
**Файлы root:** тестовая Compose integration override, Makefile/README,
согласованные FPM/CLI env и site workflow в production deployment.
**Файлы site:** отдельный consumer contract test в `site/tests/Documentation/`.
Существующие места: DocMcpOAuthVerifier, DocMcpHttpSettings, client:allow/disable.

- [ ] Выпустить token настоящего issuer, прогнать реальный HTTP SDK через
  production artifacts Symfony в isolated стенде, обе ревизии 2025-11-25/2026-07-28.
  Текущий consumer имеет фиксированные Host/resource: тестовый маршрут использует
  canonical Host и audience `https://vashfindir.ru/mcp/docs`, но staging issuer,
  test CA и отдельную одноразовую БД. Production DNS/контент не используются.
  Это не обещание работоспособности отдельного внешнего staging MCP hostname.
  Проверить только шесть read tools, draft isolation, запрет writes, token refresh,
  чужую audience, недостаточный scope и отсутствие cookie/session.
- [ ] Передать FPM/production CLI одинаковый точный DOCS_MCP_OAUTH_ISSUER;
  production CA — системная доверенная цепочка, без отключения TLS checks.
  HTTP flag первоначально 0. Credentials M2M client остаются у внешнего сервиса.
- [ ] Применить текущий additive grant migration только через штатный release
  с проверенным backup сайта. Создать локальный grant командой
  `app:docs:mcp:client:allow docs-mcp-reader-prod` в production CLI с нужным issuer.
- [ ] После успешного CI/review и конкретного Gate включить HTTP флаг обычным
  обновлением контейнеров. Пройти внешнюю проверку отдельным smoke client.
- [ ] Проверить немедленный local grant revoke на двух FPM и независимость
  stdio/Admin session/CSRF. Не создавать production documentation fixtures.
- [ ] При rollback выключить HTTP/revoke grant; additive grant table сохраняется.
  Зафиксировать client SDK/version и инструкцию фактически работающего подключения.

**Проверки:** штатные root `make ci`, `make php-production-smoke`, новый real-issuer
integration check и внешний production smoke после Gate.

## Задача 7. Приёмка переиспользования и следующий этап

**Файлы:** config/projects/example.json, docs/integrations/new-project.md,
docs/integrations/documentation-mcp.md, docs/runbooks/onboarding.md.

- [ ] По шаблону подключить второй synthetic API в тестовом стенде без изменения
  provisioning logic и без общего секрета с MCP; другой инженер воспроизводит шаги.
- [ ] Подготовить checklist нового ресурса: issuer/audience/scopes, client identity,
  grant/объектные права, TTL/revocation, секреты, tests и ответственный владелец.
- [ ] Для следующего реального проекта составить отдельный integration plan
  в его репозитории; подключать только выбранный владельцем API. Повторить
  проверки audience/scope/окружения/отзыва и зафиксировать работающий пример.
  Изменение Finance/Analytics не входит автоматически в bootstrap IAM.
- [ ] Принять пакет переиспользования: воспроизводимый deployment, изоляция,
  backup/restore, rotation, MCP и onboarding; отсутствие утверждений о непроверенных clients/HA.
- [ ] Отдельно спланировать пользовательский OIDC SSO: PKCE/state/nonce,
  `(iss, sub)`, server sessions, MFA/recovery, Symfony User model и миграция Admin.
  Current MCP client-grant contract не выдавать за per-user authorization.
- [ ] До массового SSO решить SLO/HA. Native resource indicators/CIMD, offline
  refresh и каждый конкретный desktop/chat client проходят отдельную оценку
  совместимости и безопасности, без blanket enable preview/experimental.

## 3. Итоговая готовность

- [ ] `identity/` существует в текущем repo; root/service rules и Make wrappers согласованы.
- [ ] Images/dependencies воспроизводимы, CI зелёный, security findings закрыты.
- [ ] Совместимость с настоящим MCP verifier/HTTP клиентом доказана.
- [ ] Изоляция клиентов/окружений, secret lifecycle и audit проверены.
- [ ] Восстановление из backup измерено; SLO/RPO/RTO и ограничения приняты.
- [ ] Identity и MCP выпущены отдельными контролируемыми releases.
- [ ] Подключение второго проекта доказано шаблоном без изменений IAM core.

Текущий статус (2026-11-21): Identity и Documentation MCP развёрнуты на выбранном
текущем сервере сайта. DNS/TLS auth/auth-admin проверены; public admin закрыт,
operator через SSH, MFA/password change обязательны при первом входе. GitHub CI
и независимое ревью пройдены, существенные findings исправлены. Реальные SDK/
HTTP/двеFPM/отзыв подтверждены в production; encrypted production backup скопирован
вне хоста и восстановлен на isolated стенде за38.1с. Подробные SHA/проверки/
ограничения: [отчёт выпуска](Identity_Service_Release_2026-11-21.md).
Новый реальный API, пользовательский SSO, HA и непрерывная off-host backup
репликация остаются отдельными работами; synthetic second-API isolation доказана.
