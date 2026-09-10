# Stage 3 — SEO/GEO-слой Public Website + миграция главной на website/-структуру

## Контекст и решения пользователя

- Объём: SEO-слой (title/description/canonical/OG/robots/JSON-LD) в `website/layouts/base.html.twig` + миграция главной `/` на `website/`-секции. Остальные legacy-страницы — позже отдельными этапами.
- Базовый URL: `https://vashfindir.ru` (canonical, OG, JSON-LD, sitemap).
- Лид-форма на главной: демо-форма без backend (production partial `_lead_form.html.twig`, `type="button"`).
- Яндекс.Метрика (счётчик 105455340): переносится на новую главную.
- Кейс и цитата из legacy: метрики («30 дней», «18 SKU») и цитата не подтверждены → не переносятся (SITE_RULES §7.4). Case Preview переносится как текст Проблема/Действие/Результат без числовых метрик и без цитаты.

## Проблемы текущего состояния (факты)

- Все legacy-страницы отдают одинаковые title/description (`site/templates/base.html.twig:6-7`), блоков нет.
- Нет canonical, OG/Twitter Card, JSON-LD, robots.txt, sitemap.xml, favicon.
- Главная — legacy Bootstrap с inline-стилями и фейковым dashboard.
- Новый `website/layouts/base.html.twig` имеет только title/description/head-блоки.

## Архитектура решения

### SEO-контракт layout (`website/layouts/base.html.twig`)

Новые/изменённые блоки (все с безопасными default):

| Блок | Default | Назначение |
|---|---|---|
| `title` | «Ваш Финдир» | уже есть |
| `description` | общий текст сайта | уже есть |
| `robots` | `index,follow` | meta robots; ui-kit уже шлёт noindex через `head` — перевести на блок |
| `canonical` | `vf_site_url ~ app.request.pathinfo` | `<link rel="canonical">` |
| `og_type` | `website` | og:type (`article` для будущего блога) |
| `og_image` | `/assets/website/og-image.png` (абсолютный URL через `vf_site_url`) | og:image / twitter:image |
| `json_ld` | Organization+WebSite через компонент | переопределяется/расширяется страницей |
| `analytics` | подключение metrika.js | ui-kit переопределяет пустым |

og:title/og:description/og:url берутся из существующих блоков через `{{ block('title') }}` / `{{ block('description') }}` — без дублирования текстов.

### JSON-LD компонент (переиспользуемый)

`site/templates/website/components/_json_ld.html.twig`:

```twig
{# вход: schema (array) #}
<script type="application/ld+json">{{ schema|json_encode(constant('JSON_UNESCAPED_UNICODE') b-or constant('JSON_UNESCAPED_SLASHES')) }}</script>
```

Тип по типу страницы задаёт страница, не компонент. Карта типов (фиксируется в SITE_RULES.md):

| Тип страницы | JSON-LD |
|---|---|
| Все страницы (layout default) | `Organization` + `WebSite` |
| Главная | + `Service` («Финансовый директор на аутсорсинге») + `FAQPage` |
| Страница услуг (будущее) | `Service` |
| Пост блога (будущее) | `Article`/`BlogPosting` + `BreadcrumbList` |

Organization: name «Ваш Финдир», url, logo (og-image), email hello@vashfindir.ru, sameAs: https://t.me/vashfindir_ru. Placeholder YouTube/VK и фейковый телефон НЕ включаются.

FAQ на главной: массив `faq_items` задаётся один раз в шаблоне страницы и используется и для секции FAQ, и для FAQPage JSON-LD (единый источник, без дублирования).

### Конфигурация

- `site/config/services.yaml` (или `packages/twig.yaml`): parameter `vf.site_url: 'https://vashfindir.ru'` + twig global `vf_site_url`.
- PHP-классы не добавляются → deptrac не меняется.

### Метрика

- Новый project-owned `site/assets/scripts/website/metrika.js`: загрузка `mc.yandex.ru/metrika/tag.js`, счётчик 105455340, `clickmap/trackLinks/accurateTrackBounce/webvisor` как в legacy. VK Pixel не переносится (placeholder, не настроен).
- Подключение в layout блоке `analytics`: `<script src="/assets/website/metrika.js?v={{ vf_asset_version }}" defer>`.
- Asset pipeline: `Makefile` (`assets`, `assets-check`, `asset-version`) и `scripts/tailwindcss.sh` расширяются копированием второго JS; формула версии: sha256(app.css + navigation.js + metrika.js), первые 12 знаков; обновить `vf_asset_version` в layout и тест `WebsiteFoundationTest::testAssetVersionMatchesWebsiteAssetContent`.
- SITE_RULES.md: зафиксировать metrika.js как согласованный внешний скрипт (исключение из «NO external JS») и новую формулу версии.

### Статические файлы

- `site/public/robots.txt`: Allow all, `Disallow: /ui-kit`, `Sitemap: https://vashfindir.ru/sitemap.xml`.
- `site/public/sitemap.xml`: существующие публичные URL (`/`, `/services`, `/cases`, `/about`, `/partners`, `/gazeta`, `/gazeta/post-1`, `/privacy`, `/offer`, `/consent`). При появлении блога — переход на генерацию (отдельный этап).
- `site/public/favicon.svg`: брендовый знак «ВФ» (Brand Dark фон, белый текст) + `<link rel="icon" type="image/svg+xml">` в layout.
- `site/public/assets/website/og-image.png` 1200×630: простое брендовое изображение (генерируется скриптом из доступных инструментов; без скриншотов/fake-графики).

### Главная страница

`site/templates/website/pages/home.html.twig` (extends website layout), композиция production-секций без новых паттернов:

1. Hero light: H1 «Финансовый директор на аутсорсинге для селлеров маркетплейсов», текст про ДДС/ОПиУ/Баланс/unit-экономику + SaaS-платформу; primary action → `#lead-form`, secondary → Telegram.
2. Problem (`_text_list`, marker `problem`): 4 боли из legacy.
3. Benefits (`_grid`, marker `benefits`): 6 элементов (ДДС, ОПиУ, Баланс, Планирование, Unit-экономика, Интеграция с WB/Ozon).
4. Steps: 3 шага из legacy.
5. Case Preview: Проблема/Что сделали/Результат (тексты legacy, без метрик и цитаты).
6. FAQ: 4 вопроса (чем аутсорс-финдир отличается от штатного; с чего начинается работа; какие отчёты входят; сколько стоит — честно «зависит от объёма, после диагностики»).
7. Lead Form (demo, id `lead-form`): с `note` о демо-статусе? — нет: SITE_RULES требует не выдавать демо за рабочую; форма уже `type="button"`, note сформулируем честно («Сейчас быстрее всего связаться через Telegram» — с ссылкой в CTA ниже). Решение по точной формулировке note — при реализации, без обещания отправки.
8. CTA: Telegram-канал https://t.me/vashfindir_ru.

Navbar (override блока): Главная (active), Услуги, Кейсы, О компании, Газета, Партнёрам — существующие роуты. Footer: dark variant, существующий контракт (`brand`, `privacy_href`) — расширение футера не входит в этап.

`HomeController::index()` → render `website/pages/home.html.twig`. Legacy `home/index.html.twig` удаляется; `privacy/offer/consent` остаются на legacy base (отдельная миграция).

### Обновление тестов

- Новый `site/tests/Website/WebsiteSeoTest.php` (functional):
  - `GET /` → 200, корректные `<title>`, description, canonical `https://vashfindir.ru/`, og:*, twitter:*;
  - ровно один H1; есть `application/ld+json` блоки, JSON валиден, типы Organization/WebSite/Service/FAQPage;
  - FAQPage mainEntity совпадает по количеству с аккордеоном FAQ;
  - `GET /ui-kit` → meta robots noindex; нет metrika.js;
  - статические проверки: robots.txt (Disallow /ui-kit, Sitemap), sitemap.xml (валидный XML, есть `/`), favicon.svg, og-image.png существуют.
- `WebsiteFoundationTest`: обновить формулу asset version; при необходимости скорректировать static scan inline-JS, чтобы `<script type="application/ld+json">` не считался нарушением (scan должен игнорировать ld+json).
- SmokeTest уже покрывает 200 всех роутов.

### Документация

- `SITE_RULES.md`: раздел SEO (блоки layout, JSON-LD компонент и карта типов, metrika.js, canonical через vf_site_url, og-image, robots/sitemap).
- `docs/plan/Stage_3_Website_Seo_Geo_Homepage.md` — этот план (копия в репо), `..._Report.md` — отчёт.

## Границы этапа (не входит)

- Миграция остальных legacy-страниц (`/services`, `/about`, `/cases`, `/partners`, `/gazeta`, legal).
- Backend лид-формы; VK Pixel.
- Расширение footer-контракта; BreadcrumbList (нет мигрированных внутренних страниц).
- Динамический sitemap; hreflang (один язык).
- Изменения production-инфраструктуры (Production Gate не затрагивается).

## Шаги реализации

1. План в `docs/plan/Stage_3_Website_Seo_Geo_Homepage.md`.
2. `vf.site_url` parameter + twig global.
3. Layout: блоки robots/canonical/og_type/og_image/json_ld/analytics, OG/Twitter мета, favicon link.
4. `components/_json_ld.html.twig`.
5. `metrika.js` + Makefile/tailwindcss.sh pipeline + asset version + layout.
6. robots.txt, sitemap.xml, favicon.svg, og-image.png.
7. `website/pages/home.html.twig` + правка `HomeController` + удаление `home/index.html.twig`.
8. FAQ-контент + JSON-LD граф главной (Organization/WebSite/Service/FAQPage).
9. SITE_RULES.md update.
10. Тесты: `WebsiteSeoTest`, правки `WebsiteFoundationTest`.
11. `make ci` (lint, cs, phpstan, deptrac, test).
12. Self-review по полному diff → исправления.
13. Claude Code review → исправления → повторные тесты.
14. Отчёт `Stage_3_..._Report.md`.

## Тестовая стратегия

- Unit: N/A (нет PHP-классов с логикой).
- Integration: N/A (БД не затрагивается).
- Functional: добавляется `WebsiteSeoTest` (см. выше) + существующие Smoke/Website/MarketingSections.
- E2E: N/A (нет браузерного раннера; отмечаем в отчёте как непроверенное: внешняя валидация разметки — Яндекс.Вебмастер/Google Rich Results после деплоя).

## Риски

- Static scan на inline JS может флагнуть ld+json — лечится осознанным исключением в тесте.
- `{% set %}` верхнего уровня в child-шаблоне для faq_items — проверить поддержку Twig при реализации; fallback: дублирование массива в двух блоках недопустимо → тогда массив передаётся из контроллера.
- OG-изображение: если нет инструментов растеризации — простой программный PNG без текста либо минимальный текст доступным шрифтом.
- Потеря Metrika-целей `cta_click`/`telegram_click`: новые секции не содержат js-трекеров — цели не переносятся (fix: отдельная задача аналитики). Зафиксировать в отчёте.
