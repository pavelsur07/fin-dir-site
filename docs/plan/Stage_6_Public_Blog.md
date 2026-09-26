# Stage 6 — Публичный блог «Газета» из базы

## Контекст

Stage 5 дал админку публикаций, но сайт статьи из базы не показывает:
`site/src/Controller/PostController.php` отдаёт заглушку `/gazeta` («Раздел в разработке»)
и захардкоженную `/gazeta/post-1`, а `site/public/sitemap.xml` написан руками.
Stage 6 выводит опубликованные статьи на сайт и переносит `post-1` в базу.

**Решения пользователя:**
- `post-1` переезжает на `/gazeta/marketpleys-ili-internet-magazin`, со старого URL `/gazeta/post-1` ставится **301**;
- подпись всех статей — **«Редакция Ваш Финдир»**, поля автора в модели нет;
- из `post-1` переносится **только текст**. Удаляются: фейковые просмотры «3 428», «Channel dashboard», битые рубрики `/blog/*`, «Поделиться» с неверным URL, ссылки «Пересказ в ИИ», заглушки «Похожие» с `href="#"`, картинки с Unsplash. Форма, которая шлёт данные на несуществующий `/landing/contact` и всё равно показывает «Спасибо», заменяется **CTA на Telegram**, как на главной.

**Ограничения публичного сайта:**
- представление статьи использует стандартные классы Tailwind; HTML из Markdown остаётся семантическим;
- JSON-LD для поста: `BlogPosting` + `BreadcrumbList`, только через `_json_ld.html.twig`, данные — из PHP (§15.2);
- никаких фейковых данных (§7.4, §12), новый UI-паттерн — только через алгоритм §13 с обновлением `SITE_RULES.md` и `/ui-kit`;
- после правки `app.css` нужны `make assets` и новый `vf_asset_version`.

## Архитектура

### Чтение (модуль `Publication`)

| Класс | Что возвращает |
|---|---|
| `Query/PublicPostList/PublicPostListQuery::paginate(page)` | `Pagerfanta<PublicPostListItem>` (slug, title, excerpt, publishedAt). Только `PUBLISHED`, порядок `publishedAt DESC, id DESC`, 12 на страницу |
| `PublicPostListQuery::latestExcept(?int id, int limit)` | `list<PublicPostListItem>` для блока «Читайте также» |
| `Query/PublicPost/PublicPostQuery::getBySlug(slug)` | `PublicPostView`; не опубликована или нет → `PostNotFound` → 404 |
| `Query/PublicPost/PostStructuredData::build(view, url, siteUrl)` | массив `BlogPosting` + `BreadcrumbList`, чистая функция |
| `Query/PublicPostSitemap/PublicPostSitemapQuery::all()` | `list<{slug, updatedAt}>` опубликованных |

- Публичные и админские Query — разные классы, как требует PATTERNS §9.
- `PostNotFound` получает ссылку на статью строкой (id или slug).

### Markdown (`Adapter/MarkdownRenderer`)

`renderArticle(markdown): RenderedArticle(html, toc)`:
- `HeadingPermalink` (`insert: none`, `apply_id_to_heading: true`) проставляет id заголовкам;
- оглавление `toc` собирается из h2 по AST;
- `#` в тексте понижается до h2, чтобы на странице был один H1;
- таблица оборачивается в `div` со стандартными классами Tailwind, чтобы на мобильных она прокручивалась по горизонтали;
- `html_input: strip` и `allow_unsafe_links: false` остаются.

Этот же рендер использует админский предпросмотр, поэтому превью совпадает с сайтом.

### Публичные маршруты

| Маршрут | Контроллер | Поведение |
|---|---|---|
| `GET /gazeta` (`gazeta_index`, имя сохраняется) | `Publication/Controller/PostIndexController` | список, `?page=N`, вне диапазона → 404, пусто → честный empty state |
| `GET /gazeta/{slug}` (`gazeta_post`) | `Publication/Controller/PostShowController` | статья; черновик, архив или неизвестный slug → 404 |
| `GET /gazeta/post-1` | `RedirectController` в `routes.yaml`, **до** импорта контроллеров | 301 → `/gazeta/marketpleys-ili-internet-magazin` |
| `GET /sitemap.xml` | `Website/Controller/SitemapController` | статические страницы + опубликованные статьи с `lastmod` |

- **Кеш:** `Cache-Control: public, max-age=300`, у статьи `Last-Modified = updatedAt` и 304 по `If-Modified-Since`. Сессия не стартует.
- **Удаляются:** `src/Controller/PostController.php` и статический `public/sitemap.xml` (nginx отдавал бы файл раньше маршрута).

### Шаблоны (website, по SITE_RULES)

- **`pages/blog.html.twig`:** H1 «Газета», lead, список через `sections/_grid.html.twig` (`marker: article-list`, у элементов title, excerpt, дата и action «Читать»), пагинация.
- **`pages/blog_post.html.twig`:**
  - breadcrumb «Главная / Газета / заголовок»;
  - H1, анонс, строка `<time datetime>` · «Редакция Ваш Финдир»;
  - aside «Содержание», если h2 больше одного;
  - тело статьи в контейнере ограниченной ширины;
  - «Читайте также» через `_grid` (`marker: article-preview`), если есть 2–4 другие статьи;
  - `sections/_cta.html.twig` с Telegram.
- **SEO:**
  - title = metaTitle или title + « — Ваш Финдир»;
  - description = metaDescription, иначе excerpt;
  - canonical, `og_type: article`;
  - JSON-LD из `PostStructuredData`.
- **Навигация:** обе страницы переопределяют блок `navigation` с активной «Газетой», как сейчас.
- **Расширения представления:**
  1. Grid: необязательное поле `item.meta` (мелкий muted-текст для даты). Смысл Grid не меняется;
  2. новый компонент `components/_pagination.html.twig`: «← Новее / Старее →» и «Страница N из M» на production Button `outline-primary`, `nav aria-label`;
  3. образцы представления размещаются на `/ui-kit`.

### Перенос `post-1`

**Data-миграция** `INSERT ... ON CONFLICT (slug) DO NOTHING`:
- статус published, `published_at = 2026-05-27`;
- текст статьи переведён в Markdown: заголовки, таблица, списки, врезки через `>`, FAQ как `###` с абзацами.

Миграция применяется при деплое, ручных шагов нет. Тесты, которым нужна пустая таблица, очищают её в `setUp` (внутри транзакции DAMA, откатывается).

### Админка (небольшие правки)

- Предпросмотр через `renderArticle` через тот же шаблон статьи.
- У опубликованной статьи ссылка «Открыть на сайте» в списке и на странице редактирования.

## Шаги

1. `docs/plan/Stage_6_Public_Blog.md`.
2. Adapter `renderArticle` + unit-тесты.
3. Public Query и DTO, `PostStructuredData` + integration- и unit-тесты.
4. Контроллеры `PostIndexController`, `PostShowController`, `SitemapController`; redirect в `routes.yaml`; удалить `PostController` и `public/sitemap.xml`.
5. Шаблоны страниц, `_pagination`, `item.meta` в Grid; `make assets`, `make asset-version`, обновить `vf_asset_version`.
6. `SITE_RULES.md` и `/ui-kit` (пагинация, образец тела статьи).
7. Data-миграция `post-1`; `make migrate`.
8. Админка: предпросмотр и ссылка на сайт.
9. Тесты (ниже), `make ci`, E2E в dev.
10. Self-review, Claude Code review, отчёт `Stage_6_Public_Blog_Report.md`.

## Тесты

| Уровень | Что |
|---|---|
| Unit | `MarkdownRendererTest`: id заголовков, оглавление из h2, понижение `#`, обёртка таблицы, вырезание HTML и `javascript:`. `PostStructuredDataTest`: поля BlogPosting и BreadcrumbList, ISO-даты, fallback description |
| Integration | `PublicPostListQueryTest`: только опубликованные, порядок, пагинация, `latestExcept`. `PublicPostQueryTest`: draft и archived → `PostNotFound`. `PublicPostSitemapQueryTest` |
| Functional | `PublicBlogTest`: список без черновиков; статья 200 с H1, оглавлением, JSON-LD `BlogPosting` и `BreadcrumbList`, canonical, `og:type=article`; draft, archived и неизвестный slug → 404; `/gazeta/post-1` → 301 на новый адрес; `?page` вне диапазона → 404; `Cache-Control: public`, без `Set-Cookie`, 304 по `If-Modified-Since`; пустой список → empty state; `<script>` из Markdown не попадает на страницу. `SitemapTest`: статические URL и опубликованная статья есть, черновика нет. `BlogPostTest` и `WebsiteSeoTest` (sitemap) переписываются. `SmokeTest` пропускает redirect-маршруты. Проверки SITE_RULES проходят на новых шаблонах |
| E2E | в dev: опубликовать статью в админке → она в `/gazeta` → открывается → в sitemap; снять с публикации → 404; `/gazeta/post-1` → 301. Браузерная проверка на 320/375/768/1024/1440 — если на сервере будет доступен браузер; иначе в отчёте честно пометить как не выполненную |

## Проверка

`make ci`; `doctrine:migrations:diff` → «No changes»; `make assets-check`; вручную в dev через curl: заголовки, 301, 304, sitemap.

## Вне объёма

Картинки и обложки, рубрики и теги, поле автора, кнопки «Поделиться», рабочая лид-форма с backend, RSS, поиск.
