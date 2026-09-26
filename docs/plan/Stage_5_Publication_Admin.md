# Stage 5 — Publication: публикации блога в админке

## Контекст

Stage 4 дал защищённую админку `/admin` (Twig, один пользователь `admin`). Следующий шаг —
контент-менеджер пишет и публикует статьи блога. Сейчас блог статический:
`site/src/Controller/PostController.php` отдаёт захардкоженные `/gazeta` и `/gazeta/post-1`,
базы и сущностей нет.

Решения пользователя: текст статьи — **Markdown**, картинки **отложены**, правка **`ci.yml`
(Postgres в CI) согласована**.

Stage 5 — только домен и админка. Публичная часть (`/gazeta` из БД, перенос `post-1`,
sitemap, JSON-LD) — Stage 6. Публичные URL в этом этапе не меняются.

Факты, влияющие на дизайн:
- Расширения `intl` в образах нет, поэтому `AsciiSlugger` кириллицу не транслитерирует.
  Нужна своя таблица ru→lat, без правки Docker-образов.
- В `deptrac.yaml` слой `Service` не видит `Symfony`, а `Controller` не видит `Entity`.
  Поэтому время передаётся через `Psr\Clock\ClockInterface` (vendor вне слоёв), а статус —
  отдельный ValueObject, а не enum в `Entity`.
- CI сейчас без БД, `make test` ходит в dev-базу `site`. Для integration-тестов нужна
  отдельная `site_test`.
- `symfony/form`, `symfony/validator` и Pagerfanta не установлены.

## Модель

**`App\Publication\Entity\Post`**, таблица `publication_post`:

| Поле | Тип | Правило |
|---|---|---|
| id | int identity | — |
| title | string(200) | обязательно |
| slug | string(150), unique | `^[a-z0-9]+(-[a-z0-9]+)*$`; пусто → генерируется из title |
| excerpt | string(300), null | анонс для списка и meta description по умолчанию |
| body | text | Markdown, до 100 000 символов |
| status | string(16) | `PostStatus` |
| metaTitle / metaDescription | string(70) / string(170), null | пусто → берутся title / excerpt |
| createdAt / updatedAt | datetime_immutable | — |
| publishedAt | datetime_immutable, null | дата **первой** публикации, при снятии с публикации сохраняется |
| version | int `#[Version]` | оптимистичная блокировка |

**Переходы** — методы Entity, время приходит аргументом:
- `publish(now)`: DRAFT → PUBLISHED. Требует непустые title и body. Первая публикация ставит `publishedAt`.
- `unpublish()`: PUBLISHED → DRAFT.
- `archive()`: DRAFT или PUBLISHED → ARCHIVED.
- `restore()`: ARCHIVED → DRAFT.
- Переход в текущий статус — no-op, поэтому повторный POST безопасен. Недопустимый переход → `PostCannotBeTransitioned` (409).
- `edit(title, excerpt, body, meta…, now)`. `changeSlug(slug)` бросает `PostSlugIsLocked`, если `publishedAt !== null`: однажды опубликованный URL не меняется, таблица редиректов не нужна.

## Структура (новый код)

```
site/src/Publication/
├── Entity/Post.php
├── ValueObject/                            PostStatus (enum + canTransitionTo), PostSlug, PostTitle (форматы и лимиты)
├── Exception/                              PostNotFound, PostCannotBeTransitioned, PostSlugIsLocked,
│                                           PostSlugAlreadyTaken, PostWasModified
├── Repository/PostRepository.php           get(id), save(post) — persist без flush
├── Service/
│   ├── PostSlugger.php                     ru→lat таблица + нормализация, чистый PHP
│   ├── PostCreator.php  PostEditor.php     один flush() на сценарий
│   └── PostStatusChanger.php               publish/unpublish/archive/restore
├── Query/
│   ├── AdminPostList/                      AdminPostListQuery, AdminPostListCriteria, AdminPostListItem
│   └── PostEditData/                       PostEditDataQuery → PostEditData (для формы и превью)
├── DTO/PostInput.php                       вход формы + Validator-атрибуты
├── Form/PostType.php                       data_class PostInput
├── Adapter/MarkdownRenderer.php            adapter над league/commonmark
└── Controller/Admin/                       PostListController, PostCreateController, PostEditController,
                                            PostPreviewController, PostStatusController
site/src/Shared/
├── Exception/{NotFound,Conflict}.php       интерфейсы-маркеры доменных исключений
└── EventListener/DomainExceptionListener.php   NotFound → 404, Conflict → 409 (PATTERNS §12)
site/templates/admin/posts/{list,form,preview}.html.twig
```

- **Уникальность slug.** `PostCreator` и `PostEditor` ловят `UniqueConstraintViolationException` и бросают `PostSlugAlreadyTaken`. Последняя линия защиты — уникальный индекс в БД.
- **Одновременное редактирование.** Форма передаёт `version`, `PostEditor` вызывает `lock(OPTIMISTIC, version)`. Конфликт → `PostWasModified`.
- **Ошибки формы.** Контроллеры create и edit перехватывают только `PostSlugAlreadyTaken`, `PostSlugIsLocked` и `PostWasModified` и показывают их как ошибки формы (422). Остальные исключения уходят в общий listener.
- **Markdown.** `MarkdownRenderer`: CommonMark + GFM (таблицы), `html_input: strip`, `allow_unsafe_links: false`. Используется в превью, в Stage 6 — публично.

## Маршруты админки

| Метод | Путь | Что делает |
|---|---|---|
| GET | `/admin/posts` | список: фильтр по статусу, сортировка (whitelist `updatedAt\|publishedAt\|title`, второй ключ `id`), 20 на страницу. Страница вне диапазона → 404 через Pagerfanta |
| GET/POST | `/admin/posts/new` | создать черновик |
| GET/POST | `/admin/posts/{id}/edit` | редактировать. Для опубликованной статьи поле slug только для чтения |
| GET | `/admin/posts/{id}/preview` | рендер Markdown, `noindex` |
| POST | `/admin/posts/{id}/{publish\|unpublish\|archive\|restore}` | `#[IsCsrfTokenValid]` → 403 без токена, redirect + flash |

Все маршруты под `^/admin`, их закрывают firewall и `access_control` из Stage 4. В шапку админки добавляется ссылка «Публикации», в layout — flash-сообщения.

## Шаги

1. **План в репо.** `docs/plan/Stage_5_Publication_Admin.md`.
2. **Пакеты:**
   - `symfony/form`, `symfony/validator`, `babdev/pagerfanta-bundle`, `pagerfanta/doctrine-orm-adapter`, `pagerfanta/twig`, `league/commonmark`;
   - dev: `dama/doctrine-test-bundle`. Если он не поддерживает PHPUnit 13 — базовый `DatabaseTestCase`: транзакция в `setUp`, rollback в `tearDown`, `disableReboot()`.
3. **Конфиг:**
   - `doctrine.yaml`: mapping `Publication` (`src/Publication/Entity`); `when@test`: `dbname_suffix: '_test'`.
   - `routes.yaml`: resource `src/Publication/Controller/`.
   - `babdev_pagerfanta.yaml`: `exceptions_strategy.out_of_range_page: to_http_not_found`.
   - `framework.yaml`: `form`, `validation`.
   - `deptrac.yaml`: слои `ValueObject`, `Exception`, `DTO`, `Form`, `EventListener`, `Adapter` и vendor-слои `Pagerfanta`, `CommonMark`, `Clock` и правила, по которым `Entity`, `Service`, `Query` и `Controller` видят `ValueObject` и `Exception`, `Controller` — `Form` и `DTO`, `Form` и `DTO` — `Symfony`.
4. **Домен.** `PostStatus`, `Post`, исключения, `PostRepository`; `make diff` → миграция `publication_post` (только `CREATE`, безопасна).
5. **Сервисы.** `PostSlugger`, `PostCreator`, `PostEditor`, `PostStatusChanger`, `DomainExceptionListener`.
6. **Чтение.** `AdminPostListQuery` (DTO-проекция через `NEW`, Pagerfanta), `PostEditDataQuery`.
7. **Админка.** `PostInput` и `PostType`, контроллеры, шаблоны `admin/posts/*` с использованием существующего `admin/layout.html.twig`, простая form theme.
8. **Тестовая БД:**
   - `Makefile`: цель `test-db` (`doctrine:database:create --if-not-exists` + `migrate` в `APP_ENV=test`), `test: test-db`;
   - `ci.yml`: service `postgres:17-alpine` (site/secret/site, порт 5432) и шаг подготовки `site_test` перед Tests.
9. **Тесты** (раздел ниже), затем `make ci`.
10. **Self-review, Claude Code review, отчёт** `docs/plan/Stage_5_Publication_Admin_Report.md`.

## Тесты

Builder: `site/tests/Publication/Builder/PostBuilder.php` — `aPost()->withTitle()->withSlug()->published($at)->build()`.

| Уровень | Что проверяем |
|---|---|
| Unit | `PostTest`: все переходы, no-op повтор, недопустимые переходы, `publishedAt` сохраняется при unpublish, `changeSlug` блокируется после публикации, `publish` без body запрещён. `PostSluggerTest`: кириллица, ё/й/щ, знаки, пустой результат |
| Integration | `AdminPostListQueryTest` на `site_test`: фильтр по статусу, whitelist сортировки, стабильный порядок, первая и последняя страница, count. Уникальность slug → `PostSlugAlreadyTaken`. Устаревший `version` → `PostWasModified` |
| Functional | `AdminPostTest`: гость на всех `/admin/posts*` → redirect на логин; создание → запись в списке; невалидная форма → 422; publish, unpublish, archive, restore с CSRF; без CSRF → 403; slug опубликованной статьи не меняется; несуществующий id → 404; страница вне диапазона → 404; превью с `noindex` |
| E2E | вручную в dev: создать → превью → опубликовать → снять → в архив |

## Проверка

1. `make test` (поднимает `site_test`), затем `make ci` — зелёные.
2. `make migrate`, затем `make console CMD="doctrine:migrations:diff"` → «No changes».
3. `make up`, в браузере `/admin/posts` пройти сценарий E2E. Публичные `/gazeta` и `/gazeta/post-1` не изменились (`SmokeTest`, `BlogPostTest`).

## Вне объёма

Публичный вывод из БД, перенос `post-1`, sitemap, JSON-LD (Stage 6); картинки; категории и теги;
отложенная публикация; удаление статей (есть архив); смена slug опубликованной статьи с 301.
