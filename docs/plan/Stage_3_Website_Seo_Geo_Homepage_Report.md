# Отчёт о выполнении Stage 3 — SEO/GEO-слой Public Website + миграция главной

Дата: 2026-09-10
План: `docs/plan/Stage_3_Website_Seo_Geo_Homepage.md`

## 1. Результат

Реализован переиспользуемый SEO/GEO-слой Public Website и миграция главной
страницы `/` на `website/`-структуру:

- **SEO-контракт layout** (`site/templates/website/layouts/base.html.twig`):
  блоки `title`, `description`, `robots`, `canonical`, `og_type`, `og_image`,
  `json_ld`, `analytics`. og:title/og:description/og:url берутся из тех же
  блоков через `block()` — тексты не дублируются. Twitter Card
  (`summary_large_image`), favicon, canonical по умолчанию
  `vf_site_url ~ app.request.pathinfo`.
- **JSON-LD компонент** `site/templates/website/components/_json_ld.html.twig`
  — единственная точка вывода структурированных данных. Тип схемы задаёт
  страница: layout по умолчанию выводит `Organization` + `WebSite`, главная
  добавляет `Service` + `FAQPage` (через `parent()`). Карта типов для будущих
  страниц зафиксирована в SITE_RULES.md §15.2.
- **FAQ как единый источник**: массив `faq_items` в шаблоне главной питает и
  секцию FAQ (Accordion), и `FAQPage` JSON-LD.
- **Метрика**: project-owned `site/assets/scripts/website/metrika.js`
  (счётчик 105455340), подключается блоком `analytics`; ui-kit переопределяет
  его пустым и остаётся `noindex, nofollow`. Pipeline `make assets` /
  `assets-check` / `asset-version` расширен третьим файлом; версия
  `5baf1f0c83b3` перенесена в layout.
- **Технические файлы**: `robots.txt` (Disallow /ui-kit + Sitemap),
  `sitemap.xml` (10 публичных URL), `favicon.svg`, `og-image.png` 1200×630
  (программная генерация, брендовая геометрия; вне managed-каталога
  `assets/website/`).
- **Главная** `site/templates/website/pages/home.html.twig` собрана из
  production-секций Stage 1–2: Hero → Problem → Benefits → Steps →
  Case Preview → FAQ → Lead Form (demo) → CTA (Telegram). H1 и title
  содержат целевой запрос «финансовый директор на аутсорсинге».
  `HomeController::index()` рендерит новый шаблон; legacy
  `site/templates/home/index.html.twig` удалён.
- **Конфиг**: параметр `vf.site_url: 'https://vashfindir.ru'`
  (`services.yaml`) + twig global `vf_site_url` (`twig.yaml`).
- **SITE_RULES.md**: новый раздел 15 (metadata-контракт, JSON-LD, технические
  файлы, аналитика), обновлены §3 (pipeline) и формула release query.
- Legacy-огрехи намеренно не перенесены: фейковый dashboard и метрики hero,
  неподтверждённая цитата и числовые метрики кейса, placeholder-ссылки
  YouTube/VK и телефон, VK Pixel (не настроен).

## 2. Изменённые файлы

Новые: `website/pages/home.html.twig`, `website/components/_json_ld.html.twig`,
`assets/scripts/website/metrika.js`, `public/robots.txt`, `public/sitemap.xml`,
`public/favicon.svg`, `public/assets/og-image.png`,
`tests/Website/WebsiteSeoTest.php`, план/отчёт в `docs/plan/`.

Изменены: `website/layouts/base.html.twig`, `website/pages/ui_kit*.html.twig`,
`HomeController.php`, `Makefile`, `SITE_RULES.md`, `config/services.yaml`,
`config/packages/twig.yaml`, `tests/Website/WebsiteFoundationTest.php`,
`public/assets/website/` (пересборка + metrika.js).

Удалён: `templates/home/index.html.twig` (legacy главная).

Дополнительно: нормализация CRLF→LF рабочего дерева (в git-индексе файлы уже
LF; `git diff` по содержимому затрагивает только файлы этапа).

## 3. Тестовая стратегия по уровням

- **Unit**: N/A — PHP-классы с логикой не добавлялись.
- **Integration**: N/A — БД не затрагивается.
- **Functional**: новый `tests/Website/WebsiteSeoTest.php` — метаданные
  главной, валидность JSON-LD и соответствие FAQPage видимому FAQ,
  noindex/отсутствие метрики на ui-kit, наличие и консистентность
  robots/sitemap/favicon/og-image. Обновлён `WebsiteFoundationTest`
  (новая формула asset version, metrika в pinned assets, исключение ld+json
  в scan inline-JS). SmokeTest покрывает 200 всех роутов.
- **E2E**: N/A — браузерного раннера нет; внешняя валидация разметки
  (Яндекс.Вебмастер, Rich Results) выполняется после деплоя.

## 4. Запущенные проверки

Docker в текущем окружении недоступен (WSL без Docker Desktop integration),
поэтому часть проверок выполнена на хосте:

- `make assets` + `make assets-check` — **OK** (хост, standalone Tailwind);
- `php-cs-fixer check` — **OK** (0 of 15);
- `deptrac analyse` — **OK** (0 errors);
- Twig: parse всех шаблонов + тестовый render `home`, `ui_kit`,
  `ui_kit_sections` через standalone Twig — **OK** (один H1, canonical,
  JSON-LD Organization/WebSite/Service/FAQPage, noindex на ui-kit, метрика
  только на главной);
- YAML config parse — **OK**; `php -l` изменённых PHP — **OK**;
- og-image.png — валидная сигнатура PNG 1200×630.

**Не выполнено** (требуют Docker / PHP 8.4): `lint:twig`/`lint:yaml`/
`lint:container` через Symfony console, `phpstan` (phpstan-symfony читает
container), `phpunit` — vendor содержит синтаксис PHP 8.4, хост имеет 8.3.
Эти проверки обязательно прогнать через `make ci` при доступном Docker до
релиза. Функциональные тесты SEO написаны, но фактически не запускались.

## 5. Self-review

Проведён по полному diff. Проверено: блоки layout не дублируют тексты;
`parent()` в json_ld главной работает; anchor `#lead-form` соответствует id
секции; scan-исключение ld+json точечное; glob-порядок published assets
учтён; ui-kit остался noindex без аналитики; placeholder-контакты не
попали в structured data. Блокирующих замечаний не выявлено.

## 6. Claude Code review

**Не выполнено**: Claude Code CLI в окружении не установлен (нет `claude`,
нет ANTHROPIC-креденшелов). Внешнее ревью не пройдено — требуется запуск
`claude -p` при доступном CLI согласно AGENTS.md §14.7.

## 7. Риски и действия перед релизом

1. Прогнать `make ci` при доступном Docker (phpstan, phpunit, lint).
2. Провести Claude Code review.
3. Метрика: цели `cta_click`/`telegram_click`/`lead_form_submit` не
   перенесены (новые секции без js-трекеров) — отдельная задача аналитики.
4. og-image.png — программная геометрия без текста; желательно заменить на
   дизайнерскую карточку 1200×630.
5. Лид-форма на главной — demo (`type="button"`), с честной пометкой в note;
   backend — отдельный этап.
6. После деплоя: валидация JSON-LD в Яндекс.Вебмастере / Rich Results Test,
   отправка sitemap.xml.
7. Legacy-страницы (`/services`, `/cases`, `/about`, `/partners`, `/gazeta`,
   legal) остаются на старом layout с общим title — миграция следующими
   этапами с per-page метаданными через новый контракт.
