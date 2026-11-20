# Documentation MVP — план реализации

> Для исполнителя: выполнять этапы последовательно, отмечая выполненные пункты.
> Процесс — по AGENTS.md §14: проверки по риску, без обязательного TDD и ревью
> каждого шага. Субагенты для реализации не требуются; перед релизом необходим
> один независимый read-only review полного изменения высокого риска.

**Дата:** 20.11.2026.
**Статус:** MVP реализован и проверен локально; production deployment не выполнялся.
**Цель:** редактор создаёт и публикует инструкции через существующую админку,
а посетитель читает их на `docs.vashfindir.ru`, находит поиском и переходит
между связанными материалами.
**Архитектура:** бизнес-модуль `Documentation` внутри Symfony; собственные
таблицы в существующей PostgreSQL. Public Website — Twig, управление —
существующий временный Twig Admin на основном домене.
**Стек:** PHP 8.4+, Symfony 7.x, Doctrine, PostgreSQL, League CommonMark,
Pagerfanta, Twig, Tailwind CSS 4.3.3; без новых runtime и внешних сервисов.
**Спецификация:** согласованное пользователем предложение в переписке;
его функциональный объём и технические уточнения зафиксированы ниже.

## 1. Объём и ограничения

В MVP входят одноуровневые разделы, Markdown-страницы, черновик/публикация/
снятие с публикации, защищённый предпросмотр, ручной порядок материалов,
оглавление, хлебные крошки, поиск и три вида переходов: ссылки в тексте,
до пяти «Читайте также», предыдущая/следующая инструкция внутри раздела.

Не входят вложенные разделы, вложения и загрузка изображений, история версий,
совместное редактирование, закрытые пространства, разные редакторские роли,
AI-перелинковка и автоматический импорт старого Docmost. Начальный контент
вводится редактором; production fixtures не создаются.

Обязательные ограничения:

- Соблюдать [PATTERNS.md](../architecture/PATTERNS.md), [SITE_RULES.md](../../SITE_RULES.md),
  [ADMIN_RULES.md](../../ADMIN_RULES.md), [AGENTS.md](../../AGENTS.md).
- Symfony владеет данными, validation, authentication, authorization и CSRF.
- Публичные и административные Query раздельные; в Twig только проекции/ViewModel.
- `Documentation` не зависит от `Publication`. Общим может стать только
  технический Markdown-адаптер в `Shared`, без бизнес-состояний публикаций.
- Публичный GET не запускает сессию. Admin использует текущую session cookie.
- Использовать существующие Twig partials и токены темы, без собственного CSS
  Website и без изменения shared foundations. Bundles Website/Admin изолированы.
- Все команды приложения и проверок — через [Makefile](../../Makefile).
- Планирование не разрешает commit, push, PR, merge или production deploy.
  Production Gate по AGENTS.md §12.1 сохраняется.

## 2. Основание решения и текущее состояние

Выбран собственный небольшой модуль Symfony: он позволяет хранить устойчивые
связи по ID и управлять документацией из существующей админки.
Статические Markdown-файлы проще для разработчика, но не дают редактору
согласованного управления через Admin. Возврат отдельной wiki добавляет
вторую систему пользователей и отдельный эксплуатационный контур.

На момент подготовки плана:

- `site/src/Website/Controller/DocsStubController.php` перехватывает все пути
  docs-хоста с `priority: 100`: корень показывает заглушку, остальные пути
  перенаправляются `301` на тот же URI основного сайта.
- В `site/config/packages/security.yaml` docs-firewall имеет `security: false`.
  Он остаётся публичным; вход и редактирование на docs-хост не переносятся.
- `site/config/routes.yaml` явно импортирует контроллеры каждого модуля,
  `site/config/packages/doctrine.yaml` — Doctrine mappings: оба требуют
  регистрации `Documentation`.
- Markdown-рендерер находится в `Publication/Adapter`, выдаёт безопасный HTML
  и оглавление, вырезает входной HTML и изображения. Его используют статья и
  административный предпросмотр; поведение Газеты необходимо сохранить.
- Главный layout по умолчанию строит canonical и относительные ссылки для
  основного сайта. Для docs нужны явные URL, собственная навигация и footer.
- `site/public/robots.txt` обслуживается Nginx как статический файл раньше
  Symfony. Для robots с разным sitemap по хостам потребуется HTTP-контроллер
  вместо статического файла; текущий Nginx уже поддерживает такой fallback.
- `docker-compose.prod.yml` уже направляет docs-хост в Symfony. Изменение
  Traefik, Compose, production images и deploy workflows не планируется.

## 3. Уточнения поведения для реализации

Следующие решения предложены как технические defaults этого плана.

### Контент и адреса

| Объект | Правила |
|---|---|
| Раздел | Название 1–120 символов, описание до 300, уникальный slug до 120, порядок 0–100000; без родителя и отдельного lifecycle публикации |
| Страница | Название 1–200, описание до 300, Markdown до 100000, обязательный раздел, порядок 0–100000, уникальный slug до 180 |
| SEO | Необязательные meta title до 70 и description до 170; fallback — заголовок и описание страницы |
| Статус | `DRAFT` / `PUBLISHED`; снятие возвращает в `DRAFT`, дата первой публикации сохраняется |
| Время и версия | `createdAt`, `updatedAt`, `publishedAt`; optimistic version на страницах и разделах; время передаётся из Clock |
| Slug | `[a-z0-9]+(?:-[a-z0-9]+)*`; страница может менять slug только до первой публикации, раздел получает постоянный slug при создании |

Черновик может содержать незавершённый текст и ссылки на другие черновики.
Публикация требует непустого текста и описания. Изменения опубликованной
страницы применяются сразу после сохранения и проходят те же проверки, что
публикация. Отдельная неопубликованная редакция опубликованной страницы
не входит в MVP; UI явно сообщает о немедленном применении изменений.

Пустые разделы видны только администратору. На публичной стороне раздел
появляется при наличии хотя бы одной опубликованной страницы; пустой раздел
по прямому URL возвращает `404`. Главная без материалов возвращает `200`
с корректным пустым состоянием. Физическое удаление страниц и разделов
в MVP не добавляется; страницу можно снять с публикации.

### Перелинковка

- Формат ссылки в исходном Markdown: `[Загрузка операций](doc:123)` или
  `[Настройка](doc:123#section-nastroyka)`. Публичный HTML содержит обычный
  абсолютный HTTPS URL docs-хоста, без технического `doc:`.
- Ссылку добавляют выбором страницы и, при необходимости, её заголовка.
  Выбор работает серверной GET-формой; небольшой Admin JS лишь вставляет
  результат в textarea. Без JS доступно копирование готовой Markdown-ссылки.
- CommonMark AST, а не regex по сырому тексту, определяет ссылки и заголовки:
  примеры в code blocks не считаются ссылками. ID заголовков совпадают между
  анализом, предпросмотром и публичным рендером, включая повторяющиеся заголовки.
- Обычные относительные `/articles/{slug}` и абсолютные URL того же docs-хоста
  связываются с ID через точный AST URL в `DocPageLink.sourceUrls` (JSON).
  Исходный Markdown сохраняется без переписывания и trim. Неизвестная внутренняя цель
  или отсутствующий fragment — ошибка поля; внешние ссылки остаются обычными.
  Локальные `#fragment` проверяются по заголовкам текущей страницы.
- Для ссылок в тексте и «Читайте также» сохраняются исходящий ID, целевой ID,
  тип `INLINE`/`RELATED`, fragment (пустая строка без якоря) и порядок.
  Повторы одной inline-ссылки сворачиваются в одну запись; исходный текст
  сохраняется. «Читайте также» — максимум пять разных страниц, без самой себя.
- Публикация и редактирование опубликованного материала запрещены, если
  хотя бы одна внутренняя цель не опубликована или нужный якорь отсутствует.
- Снятие цели с публикации блокируется, пока на неё ссылаются опубликованные
  страницы. Удаление/изменение заголовка также блокируется, если оно ломает
  входящую опубликованную ссылку на его якорь. Админка показывает причины
  и ссылки на страницы-источники; сначала редактор исправляет эти связи.
- Циклические ссылки разрешены. Первую публикацию новых страниц проводят
  без ссылок на ещё не опубликованные цели, затем добавляют взаимные связи.
- Предыдущая/следующая — только опубликованные страницы текущего раздела
  по `(position ASC, id ASC)`, без перехода через границу раздела.
- Публичные Query повторно фильтруют все цели по `PUBLISHED` независимо
  от write-проверок: заголовки и URL черновиков наружу не выдаются.

### Маршруты, поиск и HTTP

| Хост | Маршрут | Поведение |
|---|---|---|
| docs | `/` | Главная с разделами |
| docs | `/sections/{slug}` | Опубликованные страницы раздела, пагинация |
| docs | `/articles/{slug}` | Страница и все виды переходов |
| docs | `/search?q=…&page=…` | Поиск только по опубликованным материалам |
| docs | `/sitemap.xml`, `/robots.txt` | Только корректные URL своего хоста |
| основной | `/admin/docs/pages`, `/admin/docs/sections` | Списки и управление |
| основной | `/admin/docs/pages/{id}/preview` | Защищённый предпросмотр сохранённого текста |
| основной | `/admin/docs/link-picker` | Выбор страницы и заголовка для ссылки |

Все docs-маршруты привязаны к host; canonical строится от настроенного
`vf.docs_url = https://docs.vashfindir.ru`, а не пользовательского Host header.
На основном домене публичные docs-маршруты не обслуживают документацию.

Поиск: PostgreSQL FTS с конфигурацией `russian`, `plainto_tsquery`, повышенный
вес заголовка; индексируются заголовок и очищенный от Markdown текст, включая
текст ссылок, без служебных `doc:ID`. Параметры связаны, SQL из ввода не строится.
`q` после trim — до 200 символов; пустой запрос показывает форму без выдачи,
запрос без лексем — нулевой результат. Сортировка `(rank DESC, id ASC)`.
Размер страницы — 20, допустимый `page >= 1`, максимум `perPage` — 100,
неизвестная сортировка отклоняется. Непустая выдача за последней страницей
и `page > 1` при нулевой выдаче дают `404`; первая пустая страница — `200`.
Главная показывает разделы с пагинацией по 20, без полной загрузки их
страниц; меню показывает первые 100 непустых разделов и ссылку «Все разделы»
на главную. Поэтому разделы сверх лимита меню остаются доступны. Порядок
разделов — `(position ASC, id ASC)`.

Новые маршруты имеют приоритет выше нынешнего host catch-all. Fallback
сохраняет существующие `301` для прежних URI вне новых пространств docs,
включая экранированный path/query; `/articles/**`, `/sections/**`, `/search/**`
с неверным адресом возвращают `404`, а не уходят на основной сайт.
Перехват `/admin/**` на docs сохраняется: он никогда не доходит до admin action,
включая POST входа и изменений. Несуществующие новые URL не получают
индексируемый soft-404. Поведение GET/HEAD и отказ методов проверяется отдельно.
Неподдерживаемые методы новых read-only endpoints возвращают `405`
с `Allow: GET, HEAD`, не попадая в перенаправление legacy URI.

Публичный HTML: `Cache-Control: public, max-age=0, must-revalidate`, без 304
и серверного result cache в MVP: снятие и изменение связанных страниц
видны на следующем запросе. Это сохраняет возможность кеширования с
ревалидацией, не требует распределённой инвалидации. Preview и ответы Admin —
`private, no-store`, preview и поиск — `noindex, follow`. Indexable страницы
имеют canonical, description, OG/Twitter и JSON-LD WebPage/BreadcrumbList.

## 4. Файлы и границы

Создаваемые группы (каждый перечисленный класс — отдельный PHP-файл;
фактическое объединение небольших административных сценариев описано в §8):

| Путь | Ответственность |
|---|---|
| `site/src/Documentation/Entity/{DocSection,DocPage,DocPageLink}.php` | Данные модуля и собственные инварианты |
| `site/src/Documentation/ValueObject/{DocPageStatus,DocLinkType}.php` | Статус и вид связи |
| `site/src/Documentation/Repository/{DocSectionRepository,DocPageRepository}.php` | Загрузка write-агрегатов, проверки связей и существования целей |
| `site/src/Documentation/Service/{DocSectionCreator,DocSectionEditor,DocPageCreator,DocPageEditor,DocPagePublisher,DocPageUnpublisher,DocWriteTransaction}.php` | Конкретные write-сценарии и общая транзакционная обвязка |
| `site/src/Documentation/DTO/{DocSectionInput,DocPageInput}.php` | Валидируемые входы Symfony Forms; ID связей и версия формы |
| `site/src/Documentation/Form/{DocSectionType,DocPageType}.php` | Формы поверх DTO, без EntityType и запросов к Doctrine |
| `site/src/Documentation/Adapter/DocMarkdownProcessor.php` | Анализ AST и безопасный рендер ссылок с переданной картой целей |
| `site/src/Documentation/Query/` | Сценарии `PublicDocHome`, `PublicDocSection`, `PublicDocPage`, `PublicDocSearch`, `PublicDocSitemap`, `AdminDocPages`, `AdminDocSections`, `DocPageEditData`, `DocSectionEditData`, `DocLinkPicker`, `DocIncomingLinks`; Query и readonly проекции по сценариям |
| `site/src/Documentation/Controller/` | `DocHomeController`, `DocSectionController`, `DocPageController`, `DocSearchController`, `DocSitemapController`, `DocsFallbackController` |
| `site/src/Documentation/Controller/Admin/` | Отдельные create/edit/list/status/preview/link-picker actions для страниц и разделов |
| `site/src/Documentation/Exception/` | NotFound и Conflict с существующими Shared-маркерами, включая broken link и устаревшую версию |
| `site/src/Shared/Adapter/{MarkdownRenderer,RenderedArticle}.php` | Общий технический рендер без запросов и правил Documentation |
| `site/templates/website/layouts/docs.html.twig` | Наследник `page.html.twig`: URL, навигация и footer документации |
| `site/templates/website/pages/documentation/{index,section,page,search}.html.twig` | Публичные страницы нового назначения |
| `site/templates/website/sections/docs/` | `_navigation`, `_toc`, `_related`, `_pagination`, `_sequence` с явными аргументами |
| `site/templates/website/docs_sitemap.xml.twig` | Sitemap документации |
| `site/templates/admin/docs/{pages,sections}/` | Список/форма; preview для страниц |
| `site/templates/admin/docs/link_picker.html.twig` | Серверный выбор ссылки |
| `site/assets/scripts/admin/docs-editor.js` | Необязательная вставка ссылки в Markdown textarea |
| `site/src/Website/Controller/RobotsController.php` | Разные robots для docs и основного хоста |
| `site/migrations/Version<timestamp>.php` | Только новые таблицы Documentation и индексы связей/порядка |
| `site/tests/Documentation/{Builder,Entity,Service,Query,Adapter,Controller}/` | Детерминированные builders и проверки соответствующих границ |

Изменяемые существующие файлы:

- `site/config/routes.yaml`, `site/config/packages/doctrine.yaml`,
  `site/config/services.yaml` — регистрация модуля и `vf.docs_url`.
- `site/deptrac.yaml` и дополнительный `site/deptrac.documentation.yaml` — проверить новые зависимости и машинно запретить
  Documentation → Publication; не ослаблять ограничения слоёв.
- `site/src/Publication/Adapter/{MarkdownRenderer,RenderedArticle}.php`,
  два использующих контроллера Publication и тесты рендерера — перенести
  только общий технический код, без миграции бизнес-модуля.
- `site/assets/styles/{website,admin}/app.css` и `Makefile` (`ds-check`) —
  точечные sources/scans для Shared/Documentation adapters вместо старого пути.
- `site/templates/admin/layout.html.twig` — пункт «Документация»;
  script редактора подключается только формой документации.
- `site/templates/website/layouts/base.html.twig` — необязательный аргумент
  абсолютного URL политики для cookie-компонента; default основного сайта
  сохраняется, docs layout передаёт URL политики основного домена.
- Оба compiled bundles и их version hashes в соответствующих layouts.
- `SITE_RULES.md` — добавить `documentation/` как тематический каталог страниц.
- `README.md` — описать работу раздела вместо заглушки.
- `site/tests/Website/{DocsStubTest,WebsiteSeoTest}.php` — заменить тест
  заглушки проверками совместимости маршрутов и HTTP-контрактом robots.

Удаляются заменённые `DocsStubController.php`,
`website/pages/marketing/docs_stub.html.twig` и статический `site/public/robots.txt`.
Security-конфигурация не требует изменения: сначала подтвердить тестами,
что docs-fallback не пропускает запросы в защищённые контроллеры.

## 5. Последовательность реализации

### Этап 1. Хранилище, состояния и миграция

**Результат:** модуль сохраняет разделы, страницы и связи без публичной выдачи.

- [x] Добавить Entities, enum, repositories, DTO и Doctrine mapping из §4.
- [x] Создать таблицы `documentation_section`, `documentation_page`,
  `documentation_page_link`; FK page → section и link → source/target page,
  без каскадного удаления цели. UNIQUE slug для страниц и разделов;
  UNIQUE `(source_id,target_id,type,fragment)` для связи.
- [x] Добавить CHECK на допустимые status/type и неотрицательные позиции,
  индексы `(section_id,status,position,id)` и `(target_id,type)`.
  `searchText` хранится у страницы как обычный text. В MVP FTS вычисляет
  `to_tsvector` из title/searchText в Query; отдельные generated columns,
  DBAL types и поисковая инфраструктура не вводятся. Если замеры выявят
  неприемлемое время поиска, обосновать индекс отдельным решением до релиза.
- [x] Зафиксировать write-интерфейсы: `DocSectionCreator::create(DocSectionInput): int`,
  `DocSectionEditor::edit(int, DocSectionInput): void`,
  `DocPageCreator::create(DocPageInput): int`,
  `DocPageEditor::edit(int, DocPageInput): void`,
  `DocPagePublisher::publish(int $id, int $version): void`,
  `DocPageUnpublisher::unpublish(int $id, int $version): void`.
- [x] Реализовать доменные методы изменения текста/порядка/slug и переходов;
  optimistic version передавать также в status forms. Коллизия уникального
  slug и устаревшая версия превращаются в понятный Conflict, без SQL в ответе.
- [x] Добавить `DocSectionBuilder` и `DocPageBuilder` с фиксированным временем;
  тесты `DocPageTest`, `DocSectionTest`, `DocWriteScenariosTest` проверяют
  границы полей, slug после снятия публикации, дубли и optimistic conflict.
- [x] Сгенерировать `make diff`, проверить SQL: только добавления; применить
  на dev через `make migrate`, на тестовой БД — через `make test-db`.
  Проверить повторный diff/schema validation без паразитных изменений;
  round-trip миграции — только в одноразовой тестовой БД.

Проверки: `make test CMD="tests/Documentation/Entity tests/Documentation/Service"`,
`make console CMD="doctrine:schema:validate"`, `make deptrac`.

### Этап 2. Общий Markdown и устойчивые связи

**Результат:** ссылки хранятся по ID, проверки и HTML используют один AST-контракт.

- [x] Перенести технический Markdown-рендерер и результат в `Shared/Adapter`;
  сохранить `renderArticle(string): RenderedArticle` для Газеты. Существующие
  тесты рендерера сохраняют содержательные assertions и переезжают в Shared.
  Не менять врезки, HTML sanitization, поведение изображений и таблиц.
- [x] Добавить `DocMarkdownProcessor::analyze(string): DocMarkdownAnalysis`
  и `renderTargets(string $body, array $targets): RenderedArticle`. Analysis
  содержит точные AST URL, ID/slug/fragment, заголовки и plain text для поиска.
  Write-сервис разрешает цели по ID, существующей sourceUrls-привязке либо slug.
  Связь хранит список исходных URL; при рендере они заменяются в AST на
  текущий canonical цели. Кодовые примеры и Markdown не переписываются.
  Технический readonly результат анализа лежит рядом с адаптером; адаптер
  не зависит от Query/Service/Repository и не делает запросов в БД.
- [x] Сохранение страницы атомарно обновляет body, searchText и связи INLINE;
  список RELATED заменяется в той же транзакции и сохраняет порядок.
- [x] Ввести `DocWriteTransaction::run(callable): mixed`: все изменения
  Documentation выполняются через одну внешнюю транзакцию с единственным
  flush. Для MVP редкие редакторские записи сериализуются одним постоянным
  PostgreSQL transaction advisory lock модуля; public чтение не блокируется.
  Причина — не допустить гонку «публикация новой ссылки ↔ снятие её цели»
  и изменение якорей между проверкой и commit. Optimistic version по-прежнему
  защищает от сохранения формы, открытой до предыдущей правки.
- [x] Добавить проверки §3 к publish/edit/unpublish и входящие ссылки;
  обязательные действия синхронные, Service не вызывает Query.
- [x] Тесты `DocMarkdownProcessorTest` и `DocWriteScenariosTest`: XSS/unsafe URL,
  код с псевдоссылками, повторные заголовки, нормализация URL, исчезнувший
  anchor, self-related, шестая связь, повторный target, неизвестный ID,
  rollback при одной плохой связи, опубликованная/черновая входящая ссылка.
  Двумя независимыми соединениями с тестовой БД проверить занятость
  transaction lock во время записи и освобождение после commit; переходы
  публикации и снятия проверить отдельно, без timing-зависимых sleep assertions.

Проверки: `make test CMD="tests/Shared/Adapter tests/Documentation/Adapter tests/Documentation/Service tests/Publication"`,
`make deptrac`; пересборка обеих UI-зон после изменения sources (§6).

### Этап 3. Публичные Query, навигация и поиск

**Результат:** все публичные данные получаются ограниченными проекциями.

- [x] Создать публичные Query §4. Основные интерфейсы:
  `PublicDocHomeQuery::paginate(int $page): PagerfantaInterface`,
  `PublicDocPageQuery::getBySlug(string): array`,
  `PublicDocSectionQuery::get(string $slug, int $page): array`,
  `PublicDocSearchQuery::paginate(string $q, int $page): PagerfantaInterface`.
  На HTTP-границе параметры преобразует `DocListRequest::fromRequest` с
  Symfony Validator; Serializer и дополнительный пакет не требуются.
- [x] View-модель страницы (массив ограниченных проекций) содержит текст, метаданные, раздел, breadcrumbs, карту
  inline-целей, related и соседей. Никаких lazy associations. Заголовки
  целей и наличие якорей готовить пакетно, без запроса на каждую ссылку.
- [x] FTS выполнять параметризованным DBAL Query: count + LIMIT/OFFSET,
  ранжирование и DTO; не загружать полный результат в ArrayAdapter.
  Для остальных списков используется тот же локальный DBAL CallbackAdapter
  с count и LIMIT/OFFSET; полная выборка и ArrayAdapter не используются.
  Пагинация создаётся в `Documentation/Query/DocPagination`.
- [x] Проверить `PublicDocQueriesTest`, `PublicDocumentationTest`: draft/unpublished
  исключены из всех блоков, пустой раздел, порядок при одинаковых position,
  перенос страницы в другой раздел, отсутствие соседей на краях, count без
  дублей, первая/средняя/последняя/несуществующая страницы, длинный и пустой
  запрос, русские словоформы, спецсимволы, стабильность rank при равенстве.
- [x] Проверить ограниченное число запросов на странице с несколькими
  связями; EXPLAIN и время поиска на синтетическом наборе из 200 страниц
  примерно по 5 КБ текста, без production-данных. Зафиксировать результат
  как измерение MVP, без обещания масштабирования на неизвестный объём.

Проверки: `make test CMD="tests/Documentation/Query"`, `make deptrac`.

### Этап 4. Управление документацией в Admin

**Результат:** полный сценарий создания и связывания материалов без правки кода.

- [x] Добавить административные Query §4, формы DTO и контроллеры.
  Списки — Pagerfanta, фильтры статуса/раздела, стабильный порядок;
  select-данные форм приходят из Query, не из EntityType.
- [x] Все actions защищены `ROLE_ADMIN`; изменения только POST с CSRF.
  Загруженные значения ID разделов/целей повторно проверяются сервисом.
- [x] Включить «Документация» в меню; собрать списки/формы из существующих
  Admin partials. Порядок задаётся числовыми полями, без drag-and-drop.
- [x] Добавить отдельный экран выбора ссылки с поиском, пагинацией и выбором
  заголовка; готовый фрагмент можно скопировать без JS. `docs-editor.js`
  подключить только к форме документации, не менять `admin-asset-version`
  без включения нового JS в его команду хеширования.
- [x] Предпросмотр читает только сохранённую страницу через Admin Query,
  использует тот же рендер, но Admin layout/bundle; `private, no-store`,
  `noindex`, без публичной ссылки для чтения черновика.
- [x] Показать входящие ссылки и понятные причины запрета снятия/изменения
  якоря. Не выводить сообщения SQL. Ошибка формы — `422`, конфликт текущего
  состояния — `409`, неверный CSRF — `403`, отсутствующий объект — `404`.
- [x] `AdminDocumentationTest`: login redirect для анонима, отказ без роли,
  CRUD в согласованном объёме, подменённые IDs, CSRF, GET не меняет статус,
  устаревшая version, preview headers, link-picker и входящие ссылки.

Проверки: `make test CMD="tests/Documentation/Controller/AdminDocumentationTest.php"`,
`make console CMD="lint:twig templates/admin"`; сборка и browser-check по §6.

### Этап 5. Публичный Website и совместимость хостов

**Результат:** документация доступна анонимно на docs-хосте с корректными URL.

- [x] Зарегистрировать public/admin контроллеры и заменить catch-all заглушки
  на `DocsFallbackController` по правилам §3. Docs routes имеют приоритет
  выше fallback, fallback — выше host-neutral маршрутов основного сайта.
- [x] Создать docs layout, четыре страницы и повторяемые секции §4,
  обновить `SITE_RULES.md`. Навигация docs ведёт на docs; ссылки на сайт,
  его legal-страницы и личный кабинет — явные абсолютные URL.
  Использовать доступные partials navbar/footer без дублирования разметки.
- [x] Оглавление работает обычными anchor links без JS; на узком экране
  навигация раскрывается через details. Страница имеет один H1, видимый focus,
  доступный поиск с label и корректный пустой результат.
- [x] Для index/section/page задать metadata, canonical и JSON-LD; поиск
  `noindex`, canonical без произвольных параметров. Cache headers по §3;
  не использовать conditional 304 по одному updatedAt.
- [x] Добавить собственный docs sitemap только с опубликованными страницами
  и непустыми разделами, с явным лимитом sitemap и lastmod из реальных дат.
  Не добавлять docs-страницы в sitemap основного сайта.
- [x] Заменить статический robots контроллером: для основного сайта
  сохранить существующее содержимое, для docs указать его sitemap.
  Обновить WebsiteSeoTest на HTTP-контракт, сохранив проверки favicon/OG image.
- [x] `PublicDocumentationTest`, `DocumentationHostIsolationTest` и обновлённый
  `DocsStubTest` проверяют HTTP-контракты: опубликованный материал `200`,
  черновик/снятый/неизвестный `404`, исходящие ссылки и якоря, оба sitemap,
  robots, canonical/OG, отсутствие cookies, сохранённые `301` с кодированным
  URI, отказ попадания GET/POST `/admin/**` в Admin на docs-хосте,
  методы/HEAD и неизменность главной, Газеты, legal и формы заявки на основном.

Проверки: `make test CMD="tests/Documentation/Controller tests/Website tests/Publication/Controller"`,
`make console CMD="lint:twig templates"`; далее §6.

### Этап 6. Сборка, браузер и итоговая проверка

**Результат:** проверен весь MVP и регрессии затронутых UI-зон.

- [x] Обновить Tailwind sources и `ds-check` для новых/перенесённых adapters,
  команды хеширования Admin JS; выполнить `make website-assets`,
  `make admin-assets`, `make asset-version`, `make admin-asset-version`.
  Перенести полученные значения в соответствующие layouts.
- [x] Выполнить `make assets-check`, `make ds-check`, `make ci`.
- [x] На синтетических данных проверить через настоящий Nginx с Host docs
  home/section/article/search/robots/sitemap, включая отсутствие статического
  перехвата robots. Использовать локальное разрешение docs-хоста без обращения
  к production; автоматизируемую команду оформить целью Makefile.
- [x] В браузере пройти сценарий: создать раздел → черновик → preview →
  опубликовать цель → вставить ссылку → опубликовать источник → найти поиском
  → пройти «Читайте также» и «Назад/Далее» → проверить запрет снятия цели.
- [x] Проверить обе UI-зоны на 320/375/768/1024/1440 px, клавиатуру, 200% текста,
  длинные заголовки/ссылки/таблицы, отсутствие горизонтального скролла страницы,
  режим без JS и существующие cookie/mobile menu/форму заявки/страницы ошибок.
  Запустить `make admin-browser-check`; расширить его синтетические fixtures
  или отдельную проверку для docs, если текущий скрипт не покрывает новые экраны.
- [x] Обновить README, выполнить self-review полного diff, затем один
  независимый read-only review: миграции, доступ к черновикам, целостность
  ссылок/гонки, host routing и совместимость Газеты. Исправить существенные
  замечания и повторить только затронутые проверки.

## 6. Риски и условия передачи в релиз

Риск текущей задачи планирования — низкий: меняется только этот документ.
Реализация — **высокий риск**: новая схема данных, граница видимости
черновиков, host routing, межмодульный технический перенос и целостность ссылок.

Пять обязательных фокусов проверки и владеющие ими этапы:

1. Утечка draft через карту ссылок, picker, preview, поиск или sitemap — этапы 3–5.
2. Гонка публикации ссылки и снятия цели, включая anchors — этап 2.
3. Неверный host/canonical и обход Admin через docs catch-all — этап 5.
4. Изменение HTML/безопасности Газеты при переносе рендерера — этапы 2 и 6.
5. Дубли, неверный count и нестабильный поиск/порядок при равных значениях — этап 3.

Критерии приёмки:

- Редактор без правок кода создаёт раздел и несколько инструкций, меняет
  порядок, связывает страницы, видит preview и публикует.
- Посетитель находит инструкцию поиском и проходит все три вида связей.
- Черновики недоступны по прямой ссылке и отсутствуют во всех публичных
  проекциях; анонимные GET не создают session cookie.
- Перемещение в другой раздел сохраняет URL. Нельзя опубликовать битую
  внутреннюю ссылку или сломать опубликованную связь снятием/правкой цели.
- Старые URI вне новых docs-пространств сохраняют текущие redirects;
  основной сайт, Газета, Admin и lead-сценарий проходят регрессии.
- Проверки этапа 6 пройдены, обязательное независимое review завершено;
  результаты и конкретные ограничения дописаны в этот документ.

Перед production: штатный PR/CI/release-процесс только при отдельной
авторизации; резервная копия БД, контролируемая additive-миграция, затем
развёртывание кода одного commit SHA и smoke обоих хостов. Откат приложения
не удаляет новые таблицы: destructive down-migration не выполняется.
Если потребуется менять production-конфиги или deploy workflow, сначала
подготовить конкретный diff и применить Production Gate по AGENTS.md §12.1.

## 7. Результат подготовки плана

План составлен по routes/security/Doctrine mappings, Markdown, Admin/Public
layouts, Nginx, тестам и Makefile. После отдельного указания пользователя
«Выполняй» выполнена локальная реализация; результаты ниже.


## 8. Реализация и результаты проверки — 20.11.2026

Ветка: `feat/documentation-mvp`. Изменения не закоммичены, commit/push/PR/merge
и deploy не выполнялись. Посторонние пользовательские документы
`ai-consultant-service-spec.md`, `website-ai-chat-spec.md` не изменялись.

Выполнены все шесть этапов. Реализованы каталог и разделы, публикация/снятие,
Markdown и оглавление, поиск, все три вида перелинковки, входящие ссылки,
сохранение URL, защищённая админка/preview/picker, robots и sitemap своего хоста.
Публичная область не запускает сессию; управление остаётся на основном домене.

Технические уточнения относительно первоначального плана:

- Небольшие административные чтения собраны в `AdminDocQuery`, действия —
  в трёх Admin controllers; публичные сценарии разделены. Все Query используют
  ограниченные DBAL-проекции, локальный CallbackAdapter с настоящими count и
  LIMIT/OFFSET. Разделение Query/Service/Repository и ограничения слоёв сохранены.
- `DocPageContentEditor` переиспользуется create/edit/publish-сценариями,
  не управляя транзакцией. Все записи модуля сериализуются одной transaction
  advisory lock PostgreSQL; пользовательская version проверяется отдельно.
- Serializer не добавлялся: существующих Request + Validator достаточно.
- CommonMark URL → ID хранится в `sourceUrls` связи; источник не переписывается.
  Это сохраняет отступы code blocks, reference links, autolinks, HTML entities
  и percent-encoded русские якоря. Повторное редактирование после переименования
  цели-черновика сохраняет связь по ID. Отсутствующие публичные цели подавляются
  при рендере даже при несогласованном состоянии данных.
- Ожидаемые конфликты формы показываются с HTTP409, причиной и сохранённым
  введённым текстом. Статусные конфликты имеют отдельную Admin-страницу с409.
- Добавлены три additive-миграции: таблицы, CHECK constraints, sourceUrls JSON.
  Прямой и обратный прогон всех трёх проверен в отдельной временной схеме
  исключительно тестовой БД, с последующим rollback самой схемы.
- Общий Markdown перенесён в Shared вместе с тестами. Business-зависимость
  Documentation → Publication запрещена дополнительной конфигурацией Deptrac,
  которую запускает `make deptrac`; production workflow не изменён.

Проверки:

| Проверка | Результат |
|---|---|
| Исходный `make test` до изменений | 369 тестов, 3067 assertions — успешно |
| Итоговый `make ci` | lint, audit, CS, PHPStan, обе проверки Deptrac и PHPUnit — успешно; 410 тестов, 3362 assertions |
| `make assets-check`, `make ds-check` | Обе UI-зоны собраны, хеши обновлены, нарушений дизайн-системы нет |
| `make console CMD="doctrine:schema:validate"` | Mapping корректен, схема соответствует mapping |
| `make documentation-http-check` | Реальный локальный Nginx: оба хоста, robots/sitemap, поиск,404, Admin redirect и основные публичные страницы — успешно |
| `make documentation-browser-check` | 8 экранов × 5 ширин,200% текста, клавиатура, оглавление, related/next, GET-поиск, изоляция assets и no-JS — успешно |
| `make admin-browser-check` | 62 проверки существующего Admin, включая меню, вкладки, клавиатуру, низкий экран и no-JS — успешно |
| Независимое read-only ревью | Выполнено; все подтверждённые существенные замечания исправлены и закрыты регрессионными тестами |

Ревью выявило потерю отступов через Entity/Form trim, ненадёжную перепись
экранированных URL, пустые ссылки на русские anchors, необходимость повторного
публичного scope при рендере и потерю пользовательского ввода при DocConflict.
Эти проблемы исправлены; тесты покрывают также неизвестную RELATED-цель.
Изначальное предположение reviewer об исчезновении `Publication/Adapter`
снято: в каталоге остаётся отслеживаемый `PostCoverStorage.php`.

Ограничение существующей browser-проверки основного сайта:
`make website-seo-browser-check` падает на требовании одной строки у фразы
hero на320px. Такой же сбой воспроизведён с CSS из исходного commit. В скрипте
также устарело ожидание трёх фотографий команды: текущий сайт содержит четыре.
Оригинальный скрипт и соответствующие страницы не изменялись. Отдельная
диагностическая копия без этих двух ожиданий успешно проверила остальные
layout/SEO/images/Метрику на пяти ширинах, мобильное меню, cookie, оба ответа
заявки (mock201/422 без сохранения заявки), about/services/Газету/privacy/404.
Это не считается успешным прогоном исходного `website-seo-browser-check`.

Поиск измерен на200 опубликованных страницах по5000bytes UTF-8: первая
страница вместе с count — около401ms в текущем dev-окружении; EXPLAIN ANALYZE
фильтра — около99ms. Это измерение синтетического набора, не production SLA.
Результат сохраняется тестом в `site/var/tests/documentation-search.json`.
Полноценный нагрузочный тест конкурентных редакторов не выполнялся; отдельно
проверены реальные transaction locks двумя независимыми соединениями и
сценарии целостности publish/edit/unpublish.

Production не проверялся и не изменялся. Перед релизом сохраняются штатные
CI/release-процесс, backup БД, контролируемое применение additive-миграций и
smoke обоих хостов. Существующие project/Production Gate не обходились.


## 9. Подготовка релиза после разрешения пользователя

Пользователь явно разрешил commit, push, PR, merge после успешного CI и deploy
Documentation с его Admin UI, резервной копией БД и применением миграций.
Это разрешение включает необходимые изменения CI/deploy workflow для backup;
общий Traefik, соседние сайты и секреты не изменяются.

- В штатный workflow добавлен обязательный проверенный backup до миграций:
  PostgreSQL custom dump, проверка TOC и SHA-256, права0600, хранение за пределами
  checkout. При любой ошибке migration/deploy не продолжается.
- Git checkout закреплён за exact GitHub SHA: код/статика и PHP images одного
  релиза больше не могут расходиться из-за последующего изменения ветки.
  Локальные tracked-изменения на deploy-хосте не перезаписываются.
- CI проверяет новые пути adapters и бизнес-границу Documentation/Publication,
  а также реальный dump/restore на синтетической БД без сети.
- Production image smoke расширен проверкой docs-host, поиска/robots/sitemap,
  Admin-страниц и отказа forged CSRF документации.
- Устаревшие browser-expectations главной приведены к существующим исходникам:
  на мобильном phrase может занимать две строки, с md сохраняется nowrap;
  состав команды — четыре фотографии. Проверки переполнения и остальных
  сценариев сохранены; `make website-seo-browser-check` теперь проходит полностью.
- Локальный `make ci`:410tests3362assertions; backup/реальное восстановление и
  отказ без БД — успешно. Workflow YAML и shell blocks проверены синтаксически.

Для локальной сборки в ограниченной среде Docker Buildx использует отдельный
`DOCKER_CONFIG` внутри `/tmp`, чтобы не писать в недоступный HOME. Для временного
production smoke выбран явный свободный subnet только тестовой сети; чужие
Docker networks не удаляются и production networking не меняется.

Результаты independent review, PR/CI и фактического deploy дописываются ниже.

- Production images собраны и проверены локально: PHP runtime, миграции и
  HTTP smoke Website + Documentation + Admin UI + session/CSRF/logout — успешно.
  Временный стек и его сеть/данные после проверки удалены.
- Независимое read-only release review завершено: blocker/high/medium нет;
  подтверждены backup fail-closed, права, порядок backup→migration и единый SHA.
