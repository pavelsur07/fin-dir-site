# Documentation MCP — план внедрения

> Для исполнителя: выполнять задачи последовательно, отмечая пункты после проверки.
> Применять AGENTS.md §14: проверки по риску, без обязательного TDD и ревью каждого
> шага. Реализацию выполнять в текущей сессии без делегирования; перед релизом
> изменения высокого риска необходимо одно независимое read-only ревью.

**Дата:** 21.11.2026.

**Статус:** реализация выполнена и проверена в `feat/documentation-mcp`; завершены независимое ревью и production-image smoke. Проверки и подключение описаны в разделе 12, согласованный выпуск — в разделе 13.

**Цель:** AI-агент ищет и читает документацию, создаёт и редактирует черновики,
проверяет перелинковку; редактор публикует результат в существующем Admin UI.

**Архитектура:** MCP как входной адаптер модуля `Documentation` в Symfony.
Чтение — через отдельные Query и проекции, запись — через Application Services,
существующие Entity, Markdown-обработчик и транзакционную границу.

**Стек:** существующие PHP 8.4.27, Symfony 7.4, Doctrine, PostgreSQL 17,
CommonMark, Pagerfanta; официальный PHP-пакет `mcp/sdk` после проверки совместимости.

**Спецификация:** предложение MCP в переписке; точный MVP и предлагаемые
технические решения зафиксированы в разделах 2–7 этого документа.

## 1. Исходное состояние и риск

Модуль уже содержит страницы, разделы, Markdown, связи по ID, PostgreSQL FTS,
Admin UI и защищённый предпросмотр. Публичный сайт находится на
`docs.vashfindir.ru`, управление — на основном домене в `/admin/docs`.
Отдельных JSON API и MCP сейчас нет.

Точки переиспользования:

- `DocPageContentEditor::apply()` — содержание, связи, сохранение исходного
  Markdown и защита входящих якорей;
- `DocWriteTransaction::run()` — общая advisory lock, одна транзакция и один flush;
- `DocMarkdownProcessor` — CommonMark AST, заголовки и безопасный HTML;
- `DocPage`, `DocPageLink`, `DocFields` — инварианты и устойчивые адреса;
- `DocPagination` — COUNT и LIMIT/OFFSET, по 20 элементов;
- существующие тесты `Documentation` и Makefile-проверки.

Ограничения существующей реализации:

1. `DocPageEditor::edit()` разрешает редактировать опубликованную страницу;
   результат сразу появляется на сайте. MCP не должен вызывать этот сценарий
   с правом записи черновиков без дополнительной проверки статуса в транзакции.
2. `ROLE_ADMIN` проверяется HTTP-контроллерами. Console/MCP не получает эту
   защиту автоматически: нужна собственная проверка полномочий каждого вызова.
3. `AdminDocQuery` возвращает закрытые данные, его поиск ограничен названием.
   Публичный FTS не расширять флагом включения черновиков.
4. В отличие от короткого HTTP-запроса MCP-процесс живёт долго. Doctrine identity
   map может устареть, а после ошибки транзакции EntityManager может закрыться.

**Риск реализации — высокий:** доступ к черновикам, новый интерфейс записи,
конкуренция с Admin UI, миграция журнала операций. Само изменение этого плана
является документальным и проверяется diff/ссылками, без запуска тестов приложения.

## 2. Выбор подхода и границы первого выпуска

| Подход | Польза | Решение плана |
|---|---|---|
| JSON HTTP API | Скрипты, импорт и интеграции приложений | Отложить до появления конкретного потребителя |
| MCP через stdio в существующем CLI-контейнере | Работа агента, имеющего доверенный доступ к запуску процесса | Первый выпуск |
| Удалённый MCP через Streamable HTTP | Клиенты без доступа к серверному запуску | Отдельный следующий этап с авторизацией |

Это предлагаемый технический default. Запрос плана не означает, что пользователь
уже выбрал конкретный MCP-клиент или согласовал удалённую авторизацию.

В первый выпуск входят восемь инструментов из раздела 4, профили доступа,
проверка версии, идемпотентная запись и журнал успешных изменений.
Публикация, снятие с публикации и создание разделов остаются в Admin UI.

Вне MVP: изменение опубликованных страниц, отдельные редакции опубликованного
материала, удаление, массовый импорт, загрузка файлов, embeddings/vector DB,
генерация текста на сервере, HTTP API, OAuth-сервер и новый Admin frontend.
MCP resources/prompts пока не добавлять: восемь tools покрывают сценарий.

### Транспорт и SDK

Запуск: Symfony Console-команда `app:docs:mcp`, stdio, один процесс на подключение.
Использовать существующий `site-php-cli`, без нового контейнера-сервиса или языка.
Для клиента stdout содержит исключительно MCP; диагностика идёт в stderr.
Docker запускается с открытым stdin и без TTY; Makefile скрывает вывод рецепта.

Кандидат — `mcp/sdk` **0.10.0**, проверенный по опубликованному release на дату
плана. SDK пока объявлен экспериментальным: зафиксировать точную проверенную
версию в composer.json/lock, не использовать `main` и не писать свой JSON-RPC.
Совместимость зависимостей и клиентского протокола подтвердить задачей 1;
не считать чтение README доказательством совместимости production-образа.

Новая ревизия MCP `2026-07-28` отличается от `2025-11-25`. Согласование версии
и обратную совместимость поручить SDK и проверить с выбранным клиентом;
не зашивать собственный обязательный `initialize` для всех ревизий.

## 3. Доступ и граница доверия

Стартовая конфигурация процесса задаётся оператором, а не аргументами tool:

- `DOCS_MCP_ENABLED=0` по умолчанию: команда отказывается обслуживать запросы;
- `DOCS_MCP_ACTOR` — обязательный идентификатор интеграции, 1–64 символа
  `[a-zA-Z0-9._-]`, используется для журнала и ключей повторов;
- `DOCS_MCP_PROFILE` — `published-reader` по умолчанию; неизвестное значение
  останавливает запуск.

| Профиль | Полномочия |
|---|---|
| `published-reader` | `docs:read`: опубликованные страницы и непустые публичные разделы |
| `draft-reader` | `docs:read`, `docs:drafts:read`: также черновики и пустые разделы |
| `draft-editor` | Предыдущие права и `docs:drafts:write`: создание/изменение черновиков |

Это профили доверенного локального запуска, не OAuth и не доказательство
личности внешнего пользователя. Оператор с доступом к Docker/окружению приложения
уже находится внутри границы доверия. Такую схему нельзя публиковать как
удалённую аутентификацию или считать изоляцией от администратора хоста.

Проверять право при каждом `tools/call`, даже если запрещённый инструмент скрыт
из `tools/list`. Запретить входные `actor`, `profile`, `scopes`, `status`,
`publishedAt` и неизвестные поля. Тексты статей — данные, а не инструкции
по изменению конфигурации или расширению прав.

Для ограниченного читателя исключать закрытые данные во всех проекциях:
поиск, разделы/count, карточка, связанные материалы, входящие/исходящие связи,
заголовки целей, preview и диагностика. Неизвестный и скрытый ID дают одинаковый
`NOT_FOUND`. Запрещённый сценарий даёт `FORBIDDEN` до выполнения Query/Service.

Session/CSRF существующего Admin не меняются. На docs-хосте, где firewall
`security: false`, MCP-запись и административные HTTP endpoints не появляются.

## 4. Контракт инструментов v1

Во всех схемах `additionalProperties: false`. ID/version — положительные JSON
integers; строки не преобразуются молча в числа. Пустые/неверные параметры,
превышение лимитов и неизвестные поля отклоняются до выполнения сценария.

| Tool | Вход | Результат |
|---|---|---|
| `docs_list_sections` | `page=1` | `items`, `page`, `pageSize=20`, `total`, `hasNext` |
| `docs_search` | `q=""`, `sectionId?`, `status?`, `page=1` | Та же пагинация; ID, title, description, status, section, доступные URL |
| `docs_get_page` | `id` | ID, поля редактирования, status, version, `slugLocked`, headings, relatedIds и доступные URL |
| `docs_create_draft` | `requestKey`, `page` | `pageId`, `requestKey`, `replayed`, `adminEditUrl` |
| `docs_update_draft` | `id`, `expectedVersion`, `requestKey`, `page` | Такой же результат записи |
| `docs_get_links` | `id`, `direction=incoming\|outgoing`, `page=1` | Пагинированные связи: источник/цель, title, type, fragment, position |
| `docs_validate_page` | `id` | `pageId`, `version`, `readyToPublish`, `issues[]`, `checkedAt` |
| `docs_preview_page` | `id` | `pageId`, `version`, безопасный HTML, TOC и защищённый `adminPreviewUrl` |

`page` в create/update — полная модель редактирования, не PATCH:

- `title`: обязательно, 1–200; `slug`: обязательно, 1–180, текущий `DocFields::SLUG_PATTERN`;
- `sectionId`: обязательно; `description`: до 300; `body`: до 100000 символов;
- `position`: 0–100000, по умолчанию 0;
- `metaTitle`: null или до 70; `metaDescription`: null или до 170;
- `relatedIds`: упорядоченный список максимум пяти разных положительных ID;
  self-reference запрещён существующей моделью.

Отсутствующие необязательные поля получают документированные пустые значения.
`relatedLinks` — удобство HTML-формы, во внешний контракт не переносится.
Сырой `body` сохраняется без trim/переписывания; ссылки — `doc:ID#anchor`.
До повторной публикации снятой страницы её slug также остаётся заблокированным.
После записи агент вызывает `docs_get_page`, чтобы получить актуальную версию.
Ответ записи не выдаёт старую сохранённую версию за текущее состояние страницы.

Поиск: `q` после trim до 200 символов, FTS `russian` по title/search_text.
Пустой q означает список; непустой запрос без лексем — пустую выдачу.
При q порядок `(rank DESC, id ASC)`, без q — текущий порядок раздела/страницы
с ID как последним критерием. `status` принимает только `draft`/`published`;
явный запрос черновиков без права отклоняется, отсутствие фильтра не расширяет
доступ. В связанных списках проверять также видимость страницы-источника.
Страница вне диапазона — `NOT_FOUND`; первая пустая страница допустима.

Preview/validation работают с **сохранённой** страницей. Они ничего не записывают
и не создают публичную ссылку на черновик. HTML формируется существующим
CommonMark-адаптером; внешние URL сервер не скачивает. Ссылка Admin требует
обычной авторизованной сессии. Validation проверяет текст/описание, наличие
внутренних целей и якорей, опубликованность целей для будущей публикации.
Использовать общие правила с действующим write/publish-кодом, а не второй
независимый валидатор. Результат носит справочный характер: Admin publication
повторяет проверки в своей транзакции.

### Ошибки и пределы

Для stdio не использовать фиктивные HTTP-статусы:

- невалидное MCP-сообщение, схема аргументов, неизвестный метод/tool —
  protocol errors согласно SDK;
- ожидаемые прикладные ошибки — tool result с `isError=true` и полями
  `code`, `message`, `fieldErrors?`, без SQL, stack trace и секретов;
- коды: `FORBIDDEN`, `NOT_FOUND`, `VALIDATION_FAILED`, `VERSION_CONFLICT`,
  `PAGE_NOT_DRAFT`, `IDEMPOTENCY_CONFLICT`, `CONTENT_CONFLICT`, `LIMIT_EXCEEDED`,
  `INTERNAL_ERROR`;
- отрицательный отчёт готовности — обычный успешный результат с
  `readyToPublish=false`, а не транспортная ошибка.

Ограничения MVP: одна входная JSON-RPC строка максимум 1 MiB до JSON decode,
один сериализованный ответ максимум 1 MiB, read DB statement timeout 10 секунд,
write lock timeout 5 секунд, write statement timeout 15 секунд.
Реализацию ограничения входной строки проверить в SDK до выбора обёртки;
не обрезать JSON или Markdown, возвращать `LIMIT_EXCEEDED`.
Большие графы связей обрабатывать порциями; отсутствие полной проверки
никогда не интерпретировать как `readyToPublish=true`.

## 5. Атомарная запись, повторы и журнал

Оба инструмента записи требуют `requestKey`: UUID v4, создаваемый клиентом один
раз на логическую операцию и сохраняемый при retry. Автоматически повторять
изменение с новым ключом запрещено. Ключ — не JSON-RPC request ID.

Добавить `DocToolReceipt` / таблицу `documentation_tool_receipt`:

- `id`, `actor` (64), `request_key` (36), `tool` (64), `payload_hash` (64);
- `page_id` — FK на `documentation_page`, без каскадного удаления;
- `created_at`; уникальность `(actor, request_key)`;
- SHA-256 вычисляется по каноническому валидированному payload: фиксированный
  порядок полей, defaults/null нормализованы, порядок relatedIds и байты body
  сохранены; в hash входят tool, ID и expectedVersion при редактировании.

В **одной** `DocWriteTransaction`:

1. Проверить существующую квитанцию. Совпали actor/key/hash/tool — вернуть
   тот же pageId с `replayed=true`, не менять страницу. Другой payload — конфликт.
2. Для update заново загрузить Entity, проверить `DRAFT` и `expectedVersion`
   после получения общей lock, до изменения содержания.
3. Применить существующие Entity/`DocPageContentEditor`, сохранить страницу
   и квитанцию. FK через Doctrine association заполняется тем же flush.
4. Вернуть pageId после коммита. Успешный ответ — только после коммита.

Не оборачивать `DocPageCreator::create()` или `DocPageEditor::edit()` второй
транзакцией. Новые сценарии `DocDraftCreator`/`DocDraftEditor` используют те же
Entity, Repository, ContentEditor и WriteTransaction. Инварианты, обработку
Markdown и замены связей повторно не реализовывать.

Таймаут/ошибка откатывает и страницу, и квитанцию. Если commit прошёл, но ответ
потерян, повтор возвращает сохранённый pageId. Повтор update после последующей
публикации также только воспроизводит квитанцию, не меняет опубликованный текст.
Новая операция над опубликованной страницей всегда отклоняется.

Квитанции — долговечный журнал успешных MCP-изменений и защита от дублей;
в MVP автоматически не удаляются. Удаление/срок хранения — отдельное решение,
поскольку после удаления исчезает гарантия старых ключей.
В stderr — структурированные события: actor, tool, pageId при наличии,
requestKey, outcome/code, duration; без Markdown, аргументов целиком и секретов.
Отказанные вызовы не создают квитанцию. Хранение stderr зависит от клиента/
оператора; не заявлять его долговечным аудитом без настроенного сбора.

## 6. Структура реализации и границы

Все новые классы остаются внутри `site/src/Documentation/`; тесты повторяют
структуру в `site/tests/Documentation/`.

| Файлы | Назначение |
|---|---|
| `Command/DocMcpCommand.php` | Запуск `app:docs:mcp`, проверка конфигурации, stdio |
| `Mcp/DocMcpServer.php` | Явная регистрация восьми tools через SDK, без discovery всего приложения |
| `Mcp/DocReadTools.php`, `Mcp/DocDraftTools.php` | Транспортные handlers: доступ, DTO/Validator, один Query/сценарий, результат |
| `Mcp/DocMcpAccess.php` | Неизменяемые actor/profile и проверка конкретной возможности |
| `Mcp/DocMcpResultMapper.php` | Единое преобразование результатов/ограниченных ошибок и логирование |
| `Mcp/DocMcpSchema.php`, `DocMcpToolHandler.php`, `DocMcpOutputFilter.php` | Строгие схемы, адаптация JSON object от SDK и ограничение всех выходных кадров |
| `DTO/DocMcpSearchInput.php`, `DocDraftWriteInput.php` | Строгий внешний контракт; page после проверки переводится в DocPageInput |
| `DTO/DocMcpPageDetails.php`, `DocDraftWriteResult.php`, `DocValidationReport.php` | Контракт чтения, записи и отчёта проверки |
| `DTO/DocPublicationCandidate.php` | Данные для общих правил: source ID/version/description/body, разрешённые цели и входящие якоря |
| `Query/DocMcpQuery.php`, `PublishedDocMcpQuery.php`, `EditorialDocMcpQuery.php` | Раздельные scope: разделы, FTS, карточка, связи и данные для preview/validation |
| `Service/DocDraftCreator.php`, `DocDraftEditor.php` | Идемпотентные write-сценарии с проверкой состояния/версии |
| `Entity/DocToolReceipt.php`, `Repository/DocToolReceiptRepository.php` | Сохранение и поиск квитанции, без flush в Repository |
| `Service/DocPublicationRules.php` | Общие проверяемые правила готовности: без Query и сохранения |
| `Service/DocMcpPersistenceResetter.php` | Сброс/восстановление Doctrine между вызовами через ManagerRegistry |
| `site/migrations/Version<фактический timestamp>.php` | Аддитивная миграция квитанций, генерируется make diff |
| `site/composer.json`, `site/composer.lock`, `site/config/services.yaml` | SDK, конфигурация запуска и DI |
| `site/deptrac.yaml`, `site/deptrac.documentation.yaml` | Проверяемые границы новых классов |
| `Makefile`, `README.md` | Запуск/проверки и инструкция подключения |

Интерфейсы между задачами:

- `DocMcpAccess::assertAllowed(string $capability): void`;
- оба Query: `sections(int $page)`, `search(DocMcpSearchInput $input)`,
  `links(int $id, string $direction, int $page)` возвращают Pagerfanta проекций;
  `page(int $id): DocMcpPageDetails`; данные внутренних целей для проверки/
  preview читаются отдельно и не сериализуются в tool response целиком;
- `DocDraftCreator::create(DocDraftWriteInput $input, string $actor): DocDraftWriteResult`;
- `DocDraftEditor::edit(int $id, int $expectedVersion, DocDraftWriteInput $input,
  string $actor): DocDraftWriteResult`;
- `DocMcpPersistenceResetter::reset(): void` — перед каждым вызовом и в finally;
  восстановление закрытого manager, отсутствие старых managed Entity/транзакций;
- `DocPublicationRules::inspect(DocPublicationCandidate $candidate): DocValidationReport`;
  candidate содержит `id`, `version`, `description`, `body`,
  `targets: list<array{id:int,status:string,body:string,fragment:string}>`,
  `incomingAnchors: list<array{sourceId:int,fragment:string}>`.
  Write-side строит candidate из Entity/Repository, read-side — из Query.
  Self-link при публикации не требует предварительно опубликованного источника,
  как и в текущем ContentEditor. Заголовки разбираются тем же MarkdownProcessor.
- Проверку обязательного текста/описания для Entity и inspection объединить в
  `DocFields::publicationContentIssues(string $description, string $body): array`
  с результатом `list<'EMPTY_DESCRIPTION'|'EMPTY_BODY'>`; текущую ошибку
  `DocPage::requireContent()` сохранить. Это исключает копию правила в MCP.

`Mcp` — входная граница по роли Controller: Query/Service/DTO/Adapter/Validator,
без Doctrine, Repository и Entity. Добавить узкие collectors/ruleset для
`App\Documentation\Mcp\` и `Mcp\` vendor namespace; Console-команде разрешить
этот входной адаптер. Не расширять всем Adapter доступ к Doctrine/Repository
и не разрешать Service → Query. `Documentation → Publication` остаётся запрещено.

## 7. Порядок реализации

### Задача 1. Проверить SDK, транспорт и жизненный цикл

**Файлы:** composer.json/lock, DocMcpCommand, DocMcpServer, DocMcpAccess,
DocMcpPersistenceResetter, services.yaml, deptrac.yaml, Makefile;
`tests/Documentation/Mcp/DocMcpProtocolTest.php`.

- [ ] Добавить точную зависимость `mcp/sdk:0.10.0`; обновить только нужное дерево
  через `make update CMD="mcp/sdk --with-dependencies"`. Проверить platform requirements.
- [ ] Реализовать отключённый по умолчанию запуск, trusted actor/profile,
  явную регистрацию tools и stderr; без боевых прав записи на этом шаге.
- [ ] Добавить `make documentation-mcp` для dev: silent recipe,
  `run --rm -T --interactive`, `--no-interaction --no-ansi --no-debug`;
  входные флаги конфигурации передаются окружением через проверенную обёртку.
- [ ] Подтвердить smoke с SDK-клиентом: согласование поддерживаемой версии,
  tools/list, неизвестный метод, malformed JSON и EOF. Для legacy-клиента
  отдельно проверить initialize; для нового — соответствующий SDK путь.
- [ ] Проверить лимит строки до decode, timeout/закрытие stdin и чистоту stdout.
  Неподдерживаемый клиент/SDK — явный результат проверки; не менять транспорт
  или писать протокол самостоятельно без пересмотра плана.
- [ ] Подтвердить сброс manager между вызовами, восстановление после rollback
  и обновление состояния после изменения другим соединением. Зависимые сервисы
  должны получать сбрасываемый Doctrine lazy proxy, а не навсегда сохранять
  старый concrete EntityManager; это подтвердить вызовом write после reset.

**Проверки:** `make test CMD="--filter DocMcpProtocolTest"`, `make deptrac`,
`make phpstan`, `make lint`. Production install без dev-зависимостей — задача 6.

### Задача 2. Реализовать чтение с отдельными scope

**Файлы:** PublishedDocMcpQuery, EditorialDocMcpQuery, DocMcpSearchInput,
DocMcpPageDetails, DocReadTools;
`tests/Documentation/Query/DocMcpQueriesTest.php`,
`tests/Documentation/Mcp/DocMcpAccessTest.php`.

- [ ] Реализовать list/search/get/links по контракту раздела 4, без Entity
  serialization и без изменения публичных Query флагом видимости.
- [ ] Для published-reader обеспечить фильтрацию на стороне SQL, включая
  входящие источники, целевые метаданные и count; не фильтровать после пагинации.
- [ ] Использовать Pagerfanta и существующий размер 20, предсказуемую сортировку
  и отдельный count. В списки не выбирать body всех материалов.
- [ ] Проверить все профили и прямой tools/call скрытого инструмента,
  пустые разделы, draft/unpublished, unknown ID, неправильный статус/ID/page.
- [ ] Проверить FTS по русскому тексту, пустой запрос/без лексем, одинаковый rank,
  несколько ссылок между теми же страницами, count без дублей, последнюю страницу
  и выход за диапазон; оценить число запросов и отсутствие N+1.

**Проверки:** `make test CMD="--filter 'DocMcpQueriesTest|DocMcpAccessTest'"`.

**Промежуточный результат:** полностью работоспособный MCP только для чтения;
draft-editor ещё не включает инструменты изменения до выполнения задач 3–5.

### Задача 3. Добавить безопасную идемпотентную запись

**Файлы:** DocDraftWriteInput, DocDraftWriteResult, DocDraftCreator,
DocDraftEditor, DocToolReceipt/Repository, новая migration, DocDraftTools;
`tests/Documentation/Service/DocMcpDraftWritesTest.php`.

- [ ] Добавить Entity/индекс/FK квитанции, сгенерировать `make diff`, проверить
  аддитивный SQL и `make migrate` на dev/test; старые migrations не редактировать.
- [ ] Реализовать строгую DTO validation, перевод в DocPageInput, hash и общую
  транзакцию с единственным flush; не копировать правила ContentEditor/Entity.
- [ ] Проверить create/retry после потерянного ответа: одна страница, одна
  квитанция; один ключ с другим payload/tool — IDEMPOTENCY_CONFLICT.
- [ ] Проверить update/retry, stale version, новую операцию над published,
  гонку Admin publish между чтением и записью, два параллельных writer.
- [ ] Проверить rollback страницы/связей/квитанции при неверном разделе,
  неизвестной цели, якоре, дубликате slug, >5 related и self-reference.
- [ ] Проверить побайтовое сохранение Markdown/отступов, русские и encoded
  anchors, reference links, прежние связи после переименования draft slug.
- [ ] Проверить повтор успешного update после публикации и следующий новый
  запрос в том же процессе после UniqueConstraint/DocConflict.

**Проверки:** `make test CMD="--filter 'DocMcpDraftWritesTest|DocWriteScenariosTest|DocMigrationsTest'"`.
Конкурентные случаи проверять реальными PostgreSQL-соединениями.

### Задача 4. Проверка готовности и предпросмотр

**Файлы:** DocPublicationRules, DocValidationReport, DocReadTools,
Query-проекции; точечное извлечение правил из DocPageContentEditor/
DocPagePublisher и Entity без смены их поведения;
`tests/Documentation/Mcp/DocMcpInspectionTest.php`.

- [ ] Выделить общие проверки и использовать их в validation и действующих
  write/publish-сценариях; инварианты Entity сохранить.
- [ ] Вернуть все обнаруженные причины неготовности с машинными code и ID
  доступной цели, а не только первую текстовую ошибку.
- [ ] Реализовать preview через DocMarkdownProcessor; скрытые цели не попадают
  в HTML/TOC/URL/отчёт. Не скачивать внешние ссылки и не создавать публичный preview.
- [ ] Проверить отсутствие изменений version/updatedAt/links/receipts после
  validation/preview, HTML/XSS, code blocks, повторные и русские заголовки.
- [ ] Сопоставить готовность и реальный publish на том же состоянии: пустой
  текст/description, draft target, missing anchor и корректный материал.

**Проверки:** `make test CMD="--filter 'DocMcpInspectionTest|DocMarkdownProcessorTest|DocWriteScenariosTest|AdminDocumentationTest'"`.

### Задача 5. Проверить полный поток через протокол

**Файлы:** DocMcpResultMapper, DocMcpProtocolTest,
`tests/Documentation/Mcp/DocMcpWorkflowTest.php`, README.md.

- [ ] Проверить: tools/list → sections/search → get → create draft → get version
  → update with relatedIds → validate → preview → открытие результата в Admin.
- [ ] Проверить все восемь input/output schemas, ошибки и auth matrix через
  настоящий stdio-процесс, а не только прямые вызовы PHP handlers.
- [ ] Проверить чужое обновление, отказ, повтор и последующий успешный вызов
  в одной сессии; тело/секреты не появляются в диагностике и exception response.
- [ ] Для subprocess DB tests использовать отдельную одноразовую test-БД с
  committed fixtures и гарантированной очисткой. DAMA-транзакция родителя
  невидима дочернему процессу; production/dev database для fixtures запрещены.
- [ ] Описать vendor-neutral конфигурацию клиента: команда `make`, arguments,
  абсолютный cwd, env и stderr; проверить с одним реально доступным клиентом,
  зафиксировать его имя/версию/согласованную MCP revision в отчёте.
- [ ] Явно описать ограничение: клиент должен уметь запускать stdio-процесс
  рядом с приложением. Облачное HTTP-подключение этим выпуском не предоставляется.

**Проверки:** `make test CMD="--filter 'DocMcpProtocolTest|DocMcpWorkflowTest'"`.
Protocol tests включаются в обычный PHPUnit и существующий CI.

### Задача 6. Проверить релиз и подключить сначала чтение

**Файлы:** README.md, Makefile, этот план/отчёт выполнения;
изменения production-конфигурации не предполагаются.

- [ ] Выполнить self-review полного diff и `make ci`; пройти существующий CI
  для финального SHA. Не менять workflow только ради нового названия проверки.
- [ ] Проверить production-образы: `make php-production-build`,
  `make php-production-smoke`. Дополнить smoke вызовом MCP в production CLI
  без require-dev и с отключённым debug на изолированной БД.
- [ ] Получить одно независимое read-only ревью полного изменения: scope
  черновиков, реальная защита tools/call, атомарность, повторы, lifecycle Doctrine.
  Исправить подтверждённые существенные замечания и повторить затронутые проверки.
- [ ] Подготовить Makefile-обёртку запуска production CLI из образа того же SHA:
  параметры окружения, `--rm -T --interactive`, без исходников bind mount,
  без shell/SQL tools и без печати секретов; `DOCS_MCP_ENABLED` задаётся только CLI.
- [ ] В согласованном release-процессе до миграции выполнить
  `make site-database-backup` и проверить архив/TOC/checksum; затем применить
  аддитивную миграцию и выпустить образы по существующему pipeline.
- [ ] До релиза проверить локальный Nginx командой `make documentation-http-check`
  (скрипт сейчас обращается к 127.0.0.1:8001 с Host headers). После релиза
  отдельно проверить реальные HTTPS URL обоих доменов: /, robots, sitemap,
  поиск, 404 неизвестной статьи, protected Admin и отсутствие public session.
  Не выдавать локальный HTTP check за проверку production. Admin auth/CSRF
  и публикацию подтвердить существующими functional tests.
- [ ] Первый production smoke — `published-reader`: list/search/get и
  запрет изменения. Запись проверить заранее на изолированной БД; production
  контент не создавать ради теста. Включить draft-editor для рабочего клиента
  после готовности всех проверок и согласованного подключения.
- [ ] Записать release SHA, миграцию, backup, результаты CI/review, клиент и
  способ запуска/отключения. Не заявлять production-подключение без фактического smoke.

Commit/push/PR/merge/deploy выполняются только в явно согласованном для этого
этапа release scope. Запрос данного плана сам по себе их не запускает.
Production Gate AGENTS.md §12.1 сохраняется для credentials, production config
и любых выявленных изменений инфраструктуры; новые секреты для stdio не нужны.

## 8. Критерии приёмки

- [ ] Один совместимый MCP-клиент выполняет полный сценарий через stdio.
- [ ] Агент создаёт и изменяет только черновики; published не меняется новой
  операцией даже при гонке с публикацией в Admin.
- [ ] Повтор с тем же requestKey не создаёт страницу/связи повторно и не
  перезаписывает более позднее изменение; другой payload с тем же ключом отвергается.
- [ ] Published-reader не получает закрытые страницы, разделы, связи или
  целевые метаданные ни через один инструмент, error или count.
- [ ] Version conflict не теряет данные; после отказа MCP-процесс продолжает
  работу и видит актуальное состояние другого соединения.
- [ ] Preview и validation не меняют БД; ссылки и заголовки согласованы
  с действующим Markdown/publication-поведением.
- [ ] Есть долговечные квитанции успешных записей, а в диагностике нет контента/секретов.
- [ ] Пройдены релевантные проверки, финальный CI и независимое ревью реализации;
  известны backup, migration и процедура отключения.

## 9. Отключение и откат

Отключить запуск MCP: остановить процессы клиента и убрать разрешение
`DOCS_MCP_ENABLED=1` из доверенной launch-конфигурации. Уже открытый процесс
переменные окружения заново не читает, поэтому одного изменения env недостаточно.
Public Website и Admin при этом продолжают работать.

При необходимости вернуть предыдущие совместимые образы по существующей
процедуре. Таблицу квитанций оставить: она аддитивна и не мешает прежнему коду.
Не откатывать схему и не восстанавливать всю БД поверх новых редакторских данных
для обычного выключения MCP. Ошибочный черновик исправляется в Admin;
резервная копия нужна для аварии данных и применяется отдельно по Production Gate.

## 10. Следующий этап: удалённый доступ или API

После выбора реального удалённого потребителя подготовить отдельный план:
Streamable HTTP на основном origin, OAuth-совместимая авторизация, scopes,
идентификация клиента, отзыв доступа, rate limits, Origin checks, timeouts
reverse proxy и совместимость клиента. Не переносить локальный actor/profile
в доверенные HTTP-заголовки и не использовать admin session как готовую
авторизацию произвольного MCP-клиента.

JSON API добавлять при конкретной интеграции; Request/Response DTO и сценарии
этого этапа пригодны для повторного использования, HTTP остаётся отдельной
границей доступа. Публикацию через MCP/API и неопубликованные редакции
опубликованных страниц согласовывать как отдельное бизнес-поведение.

## 11. Источники и проверка плана

Локальные правила: [AGENTS.md](../../AGENTS.md),
[PATTERNS.md](../architecture/PATTERNS.md), [Makefile](../../Makefile).
Исходный модуль: [план Documentation MVP](Stage_Documentation_MVP.md);
актуальные сигнатуры/поведение сверены с кодом, а не с прежним статусом его плана.

Официальные источники, проверены 21.11.2026:

- [PHP SDK: назначение и экспериментальный статус](https://github.com/modelcontextprotocol/php-sdk).
- [PHP SDK releases: 0.10.0](https://github.com/modelcontextprotocol/php-sdk/releases/tag/v0.10.0).
- [MCP transports и совместимость ревизий](https://modelcontextprotocol.io/specification/2026-07-28/basic/transports).
- [MCP tools и схемы](https://modelcontextprotocol.io/specification/2026-07-28/server/tools).
- [Авторизация HTTP и отличие stdio](https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization).

Проверка документа перед передачей: покрытие восьми tools, матрица прав,
идемпотентность/гонки, границы слоёв, реальные команды Makefile, ссылки и
`git diff --check`. Тесты приложения относятся к реализации; подготовка этого
документа не означает, что SDK, тесты или production MCP уже проверены.

## 12. Реализация и проверка — 21.11.2026

Реализованы все восемь tools, три профиля доступа, строгие input/output schemas,
два отдельных scope чтения, полнотекстовый поиск, validation/preview и запись
черновиков. SDK `mcp/sdk` закреплён на 0.10.0; добавлены его необходимые
зависимости, версии ранее установленных пакетов не изменены.
Общая SQL-реализация чтения вынесена в abstract DocMcpQuery; два final Query
задают обязательные scope без внешнего флага включения черновиков.

Создана и применена к dev/test аддитивная миграция
`Version20261121050510`: квитанции операций и FK на страницу, уникальность
actor/requestKey. На этапе локальной подготовки production-миграция не выполнялась.
Запись страницы и квитанции атомарна; shared advisory lock и проверка статуса
защищают от публикации между чтением версии и применением изменения.

Реальные SDK-клиенты 0.10.0 проверены с `2025-11-25` и `2026-07-28`:
создание, повтор, чтение версии, update/replay, поиск, ссылки, preview,
validation, schema errors, скрытые tools, устаревшая версия, конфликт уникальности,
восстановление manager и видимость внешнего изменения в том же процессе.
Два одновременно запущенных процесса действительно ожидают общий PostgreSQL
advisory lock: одинаковый ключ даёт одну страницу; публикация до освобождения
lock запрещает обоим ожидающим writer новое изменение.
Protocol fixtures создаются в отдельной одноразовой БД `vf_mcp_<random>_test`,
которая удаляется в finally; dev/production данные для fixtures не используются.

Независимое read-only ревью проведено отдельным reviewer. Три замечания medium
приняты и исправлены:

1. После первоначальной проверки публичной страницы повторно фильтруются
   источник в related/targets и цель во входящих якорях. Regression test
   воспроизвёл выдачу новых черновых связей между двумя SELECT до исправления.
2. Сообщения прикладных ошибок ограничены 4096 байтами; чрезмерные сообщения
   заменяются коротким LIMIT_EXCEEDED до сериализации SDK. Regression test
   воспроизвёл ответ 1 080 163 байта; протокольный тест с якорем из 90 000
   кириллических символов теперь сохраняет корреляцию ответа и следующий вызов.
3. Production launcher использует действующий формат `IMAGE_TAG=sha-<40hex>`,
   проверяет все работающие PHP-реплики и не скачивает иной образ автоматически.

Отклонённых или отложенных существенных замечаний нет. Проверка output filter
также подтверждает ограничение протокольных кадров и обработку следующего
кадра после превышения лимита. Входная строка ограничивается штатным SDK
до JSON decode; nested page object нормализуется из stdClass на границе.

Production FPM/CLI образы собраны локально как `vf-check/docs-mcp-fpm:local` и
`vf-check/docs-mcp-cli:local`. Изолированный smoke `vf-docs-mcp-smoke` успешно
проверил обе MCP revision без require-dev, миграции на чистой БД, сайт,
документацию и Admin, вход, общую сессию двух FPM-реплик, CSRF и logout.
Тестовый Compose-проект и его данные удалены. Для локального хоста использован
существующий временный subnet override; production network/config не менялись.
`make documentation-http-check` отдельно подтвердил локальный Nginx обоих хостов.

Эксплуатация описана в README. Production launcher требует штатного окружения
Compose и tag **уже развёрнутого** релиза; он не публикует код и не применяет
миграции. На этапе локальной подготовки commit/push/PR, GitHub CI и production
deployment ещё не выполнялись. Выпуск описан в разделе 13; pipeline checks
и backup gate сохраняются.

Итоговые проверки после исправления замечаний:

- `make ci` — **PASS: 425 tests, 3540 assertions**; composer validate/audit,
  assets freshness, YAML/Twig/container lint, CS, PHPStan level 8 и оба deptrac — PASS.
- `make test CMD="--filter DocMcp"` — **15 tests, 178 assertions**, включая
  workflow, protocol и regression cases; отдельный DocMcpWorkflowTest не создавался,
  полный поток включён в DocMcpProtocolTest для переиспользования изолированной БД.
- Production build/smoke — PASS: обе ревизии MCP в CLI без dev-зависимостей,
  две FPM-реплики и существующий HTTP/auth smoke. Это локальная проверка
  production-артефактов, не deployment реального сайта.
- `make documentation-http-check` — PASS на локальном Nginx.
- Реальный `make documentation-mcp` с initialize через stdin — PASS: один
  валидный MCP frame в stdout. После изменения кода требуется очистка
  соответствующего no-debug dev DI cache командой
  `make console CMD="cache:clear --no-debug"` (обычная debug-очистка сохраняет
  отдельный скомпилированный no-debug container). README содержит этот шаг.
- Production launcher без IMAGE_TAG отказывает до Compose; dry run сверён
  с фактическим tag `sha-${github.sha}` действующего pipeline.
- Self-review: полный tracked diff и новые файлы задачи проверены, локальные
  ссылки и whitespace проверены; два посторонних документа не изменялись.

Дальше для релиза: разрешённые commit/PR, GitHub CI, backup перед production
миграцией и стандартный deployment, затем подключение рабочего клиента с
`published-reader` и расширение до `draft-editor` по его рабочему сценарию.

## 13. Выпуск MCP

21.11.2026 пользователь разрешил выполнить оставшиеся commit/push/PR, merge после
успешного CI и production deployment с резервной копией и миграцией.
В release включаются только файлы Documentation MCP; два сторонних документа
остаются вне коммита. Existing deploy pipeline делает проверенную резервную
копию до checkout/миграции, затем применяет аддитивную миграцию перед сменой FPM.

В `make php-deployed-check` добавлен тот же read-only MCP smoke, который проверяется
на production-образе локально/в CI: обе ревизии протокола, discovery шести read tools,
sections/search без выдачи контента в лог. Скрипт передаётся в CLI через stdin;
production-image smoke использует этот же способ. Это позволяет проверить уже
развёрнутый MCP на реальной production-БД без тестовых записей и нового endpoint.
