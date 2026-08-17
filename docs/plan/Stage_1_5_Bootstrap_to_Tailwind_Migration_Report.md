# Stage 1.5 — отчёт о миграции Bootstrap → Tailwind CSS

Дата завершения: 2026-08-17.

Статус: выполнено. Public Website переведён с Bootstrap на Tailwind CSS без
редизайна, локальная библиотека Stage 2 сохранена и мигрирована вместе с
foundation UI.

## Границы и Readiness Gate

Перед реализацией полностью прочитаны задача, `AGENTS.md`, `SITE_RULES.md` и
`docs/architecture/PATTERNS.md`; изучены текущие templates, styles, tests,
Makefile, CI и незавершённые локальные изменения. До первого изменения сняты
baseline screenshots `/ui-kit` и `/ui-kit/sections` на ширинах 375, 768, 1024
и 1440 px, baseline `make ci` был зелёным.

В scope вошли только build foundation, CSS framework migration, Design Tokens,
Public Website Twig, website tests и документация. Бизнес-логика, данные,
схема БД, security, интеграции, очереди, URL и production infrastructure не
менялись.

## 1. Выбранный Tailwind build

Выбран официальный standalone Tailwind CLI. Причины:

- проекту не требуются Node, npm, PostCSS или bundler;
- один wrapper одинаково используется локально и в CI;
- production runtime получает только готовый CSS;
- решение минимально для существующего Symfony/Twig монолита.

`scripts/tailwindcss.sh` определяет поддерживаемую платформу, загружает
официальный binary в ignored `site/var/tools`, проверяет pinned SHA-256 и только
после этого запускает CLI. Поддержаны Linux x64/arm64, включая musl, и macOS
x64/arm64; неизвестная платформа завершается fail-closed.

Build contracts:

```text
source:    site/assets/styles/website/app.css
generated: site/public/assets/website/app.css
build:     make assets
watch:     make assets-watch
drift:     make assets-check
```

`make assets-check` выполняет новый production build во временный файл и
сравнивает его с опубликованным asset, поэтому stale output останавливает CI.

## 2. Exact Tailwind version

Зафиксирована версия Tailwind CSS `4.3.3`.

Для Linux x64 проверен binary SHA-256:

```text
dc61b3ac6b8c9ca874c0cc4c57b2409791a64c5540404ca5f5367360babc313a
```

Checksums остальных поддерживаемых release assets также зафиксированы в
wrapper. Использованы официальный [release v4.3.3](https://github.com/tailwindlabs/tailwindcss/releases/tag/v4.3.3)
и официальный [standalone CLI workflow](https://tailwindcss.com/docs/installation/tailwind-cli).

## 3. Удалённые Bootstrap dependencies

Из website layout удалены Bootstrap 5.3.3 CSS CDN и Bootstrap bundle JS CDN.
Удалены Bootstrap Collapse hooks и все `data-bs-*`. Browser больше не загружает
Bootstrap CSS/JS.

Удалены legacy website stylesheets и их public mirrors:

```text
site/assets/styles/website/base.css
site/assets/styles/website/tokens.css
site/assets/styles/website/components/index.css
site/assets/styles/website/components/showcase.css
site/assets/styles/website/sections/index.css

site/public/assets/website/base.css
site/public/assets/website/tokens.css
site/public/assets/website/components/index.css
site/public/assets/website/components/showcase.css
site/public/assets/website/sections/index.css
```

Обязательные grep-проверки из задачи дали `0` runtime occurrences для
Bootstrap CDN, `data-bs-` и `--bs-` в active Public Website. Упоминания слова
Bootstrap остались только в правилах и тестах как явный запрет/проверка.

## 4. Перенесённые Design Tokens

Канонический token block и Tailwind theme объединены в `app.css`; второй
независимой token system нет. Сохранены значения Stage 1/Color System:

- Brand Red и состояния: `#B00020`, `#8A0019`, `#6C0014`, soft surface;
- Brand Dark `#0B1020` и Dark Surface `#1E2331`;
- light/dark text, muted, surface, background и border tokens;
- отдельные Focus/Focus on Dark;
- Success, Warning, Danger и Info с soft surfaces;
- Onest, typography scale и разрешённые weights;
- spacing scale на базовом шаге 4 px;
- containers, control/textarea minimum size, radius, shadow и disabled opacity;
- breakpoints `576/768/992/1200/1400px`.

`@theme inline` связывает VF tokens с semantic utilities, например
`bg-brand-red`, `text-content`, `border-focus`, `max-w-site`. Default Tailwind
color palette отключена через `--color-*: initial`, чтобы production templates
не могли молча использовать `red-500` или случайный gray.

Source discovery ограничен `site/templates/website` через `source(none)` и
явный `@source`; generated/public/vendor paths не создают build loop.
Произвольные `[...]` values не используются. Оставлены только две обоснованные
custom utilities: auto-fit project Grid и минимальная ширина Comparison table.

## 5. Мигрированные Components

Production Twig partials и их входные параметры сохранены. На Tailwind
utilities и полные статические variant maps переведены:

- Button, включая primary/outline/on-primary, состояния и dark context;
- Card;
- Badge;
- Alert;
- Breadcrumb;
- Input, Select, Textarea и Checkbox;
- Accordion;
- Navbar;
- CTA;
- Footer light/dark.

UI-kit продолжает подключать те же production partials. Bootstrap-подобный
слой `.vf-btn`, `.vf-card` и т. п. заново не создавался. Dynamic Tailwind class
fragments отсутствуют; state/variant maps содержат полные class strings.

## 6. Accordion и mobile Navbar

Accordion реализован native `details/summary`, без JavaScript dependency.
Заголовок внутри `summary` — настоящий `<h3>` с production typography, поэтому
heading navigation доступна assistive technologies. Native keyboard open/close
проверен.

Mobile Navbar также использует `details/summary`. На desktop отдельный видимый
список ссылок рендерится внутри того же production Navbar partial: это нужно,
потому что содержимое закрытого `details` может быть визуально раскрыто CSS,
но остаётся исключённым из keyboard/accessibility tree. В каждый момент один
из списков скрыт через responsive display. Component API не изменён, JS и
Bootstrap Collapse не нужны.

## 7. Изменения `/ui-kit`

Сохранены структура, demo content и состояния foundations/components:

- Typography, Colors, Spacing;
- все Brand/Dark/Neutral/Semantic swatches;
- Button states и light/dark combinations;
- Cards, Badges, Alerts, Breadcrumbs;
- Accordion и production Navbar preview;
- сгруппированные Input/Select/Textarea/Checkbox states;
- Dark Hero, Content Section, red conversion CTA и Dark Footer.

Страница подключает только `/assets/website/app.css`; отдельный showcase CSS
удалён. Asset query равен первым 12 символам SHA-256 compiled CSS:
`d8610d22dc07`.

## 8. Изменения local `/ui-kit/sections`

Локальный каталог Stage 2 не удалялся и не пересоздавался. Его существующие
section contracts, content, порядок и boundary cases мигрированы in place на
Tailwind utilities:

- Text/List, Grid, Split, Steps, Paths;
- Case Preview, Quote, Comparison, FAQ;
- Lead Form и Typography Stress showcase;
- Foundation Hero/Content/CTA reuse.

Reusable sections используют общий container, spacing, tokens и production
components. Comparison сохраняет локальный horizontal scroll внутри region на
узком viewport, не создавая page-level overflow.

## 9. Сохранённые локальные Stage 2 files

До начала Stage 1.5 в worktree уже находилась незавершённая Stage 2 работа.
Сохранены:

```text
site/src/Controller/UiKitController.php
site/templates/website/pages/ui_kit_sections.html.twig
site/templates/website/sections/_text_list.html.twig
site/templates/website/sections/_grid.html.twig
site/templates/website/sections/_split.html.twig
site/templates/website/sections/_steps.html.twig
site/templates/website/sections/_paths.html.twig
site/templates/website/sections/_case_preview.html.twig
site/templates/website/sections/_quote.html.twig
site/templates/website/sections/_comparison.html.twig
site/templates/website/sections/_faq.html.twig
site/templates/website/sections/_lead_form.html.twig
site/templates/website/sections/_typography_stress_showcase.html.twig
site/tests/Website/MarketingSectionsTest.php
site/public/assets/fonts/onest/
docs/plan/Stage_2_Marketing_Sections_Library_Vash_Findir_Report.md
```

Stage 2 templates и test были только адаптированы к Tailwind contract. Route,
Onest files, report и marketing content не потеряны. Unrelated local work не
очищался и не reset-ился.

## 10. Responsive results

Обе страницы проверены реальным Chromium:

| URL | 375 | 768 | 1024 | 1440 |
|---|---:|---:|---:|---:|
| `/ui-kit` | 200 / overflow 0 | 200 / overflow 0 | 200 / overflow 0 | 200 / overflow 0 |
| `/ui-kit/sections` | 200 / overflow 0 | 200 / overflow 0 | 200 / overflow 0 | 200 / overflow 0 |

На каждом viewport подтверждены один H1, загруженный локальный Onest, отсутствие
external resources и page errors. Mobile buttons/forms укладываются в viewport,
Forms showcase и section grids перестраиваются, desktop Navbar не выходит за
container.

## 11. Accessibility results

- Lighthouse `13.4.1`: Accessibility `1.00` для `/ui-kit` и
  `/ui-kit/sections`, binary failures отсутствуют;
- keyboard focus поля: `2px solid #005FCC`, `:focus-visible` активен;
- mobile Navbar и Accordion открываются через Enter;
- desktop navigation links входят в последовательный tab order;
- skip link, labels, disabled/error/success states и comparison region
  сохранены;
- Accordion titles доступны как реальные H3;
- contrast regression tests проверяют основные light/dark, CTA, focus, border
  и semantic combinations по WCAG thresholds;
- Brand Red не используется как мелкий текст на Brand Dark.

## 12. Visual regression results

Baseline screenshots сохранены во временном каталоге
`/tmp/vashfindir-stage1-5-baseline`, финальные — в
`/tmp/vashfindir-stage1-5-verified`; в репозиторий они намеренно не добавлены.

Выполнено ручное сравнение всех восьми viewport/page combinations. Сохранены
brand colors, typography hierarchy, section order, surfaces, spacing intent,
component states и marketing content. Редизайн, новые декоративные variants и
новые sections не выполнялись.

В ходе regression review найдены и исправлены:

1. отсутствующий реальный focus outline у form controls;
2. нулевая intrinsic width первой desktop реализации native Navbar;
3. видимые, но исключённые из desktop tab order ссылки внутри закрытого
   `details`.

## 13. Tests и `make ci`

Финальные результаты после всех исправлений:

| Проверка | Результат |
|---|---|
| `make assets` | Tailwind `4.3.3`, success |
| `make asset-version` | `d8610d22dc07` |
| `make assets-check` | success, drift отсутствует |
| обязательные Bootstrap grep checks | 0 matches |
| Composer validate/audit | success, advisories отсутствуют |
| YAML/Twig/container lint | success, 52 Twig files |
| PHP CS Fixer | 0 files to fix |
| PHPStan | 0 errors |
| Deptrac | 0 violations, 0 uncovered, 0 errors |
| PHPUnit | 12 tests, 1010 assertions |
| `make ci` | success |

Стратегия по уровням:

- Unit: `N/A` — изолируемая бизнес-логика не добавлялась;
- Integration: `N/A` — БД, Doctrine, Messenger и внешние adapters не менялись;
- Functional: `WebsiteFoundationTest` и `MarketingSectionsTest` обновлены;
- E2E: Chromium responsive, resources, overflow, keyboard/focus и native
  disclosure checks выполнены на работающем Symfony/Nginx stack;
- Accessibility: Lighthouse и programmatic contrast audit выполнены.

## 14. Self-review

Повторно проверены исходная задача, полный tracked diff, новые/untracked
implementation files, удалённые legacy styles, compiled output и сохранённая
Stage 2 работа. Выполнены `git diff --check`, static scans, build drift check,
browser review и полный CI.

Blocker/high не обнаружены. Найденные focus и Navbar regressions исправлены до
external review. Не появилось бизнес-логики в Twig, новых dependencies,
динамических class fragments, arbitrary values, новых brand colors, циклических
зависимостей или изменений публичных contracts.

## 15. Claude Code review

Независимое read-only Claude Code review выполнено после зелёного self-review.
Reviewer просмотрел task/rules, tracked/staged diff, untracked Stage 2 files,
source и compiled CSS, wrapper и tests.

Классификация:

- blocker: 0;
- high: 0;
- medium: 1 — ARIA heading внутри button-like `summary` мог исчезать из
  accessibility tree; исправлено на настоящий H3, добавлены regression
  assertions;
- low: 6.

Из low дополнительно исправлены breakpoint values: Tailwind theme теперь
использует документированные px values без зависимости от root font size.
Остальные low не блокируют acceptance: font cache-busting, CI cache для CLI,
локальный macro для двух коротких Navbar loops, latent bare-link dark context и
speculative old WebKit marker CSS. Они не требуют расширения текущей миграции;
актуальные call sites и browsers проверены.

После исправлений повторно выполнены `make assets`, version refresh,
`make assets-check`, focused tests, Chromium checks, Lighthouse и полный
`make ci`.

## 16. Known limitations

- первый build на новой машине требует network access к official GitHub release;
  последующие builds используют проверенный local cache;
- standalone wrapper намеренно поддерживает только перечисленные desktop/CI
  platforms и fail-closed на остальных;
- screenshot comparison выполнен вручную, автоматический pixel-diff в проект
  не добавлялся;
- Onest font URL пока не имеет отдельного query version; файл поставляется
  внутри versioned release, но при замене font binary потребуется учесть
  immutable Nginx cache;
- native details marker визуально проверен в Chromium; отдельный старый Safari
  device pass не выполнялся;
- legacy templates вне `site/templates/website` остаются отдельным scope и не
  мигрировались этой задачей.

## Полный файловый состав изменения

Build и delivery:

```text
M  Makefile
M  .github/workflows/ci.yml
A  scripts/tailwindcss.sh
A  site/assets/styles/website/app.css
A  site/public/assets/website/app.css
M  site/templates/website/layouts/base.html.twig
```

Rules:

```text
M  SITE_RULES.md
M  AGENTS.md
```

Production components:

```text
M  site/templates/website/components/_accordion.html.twig
M  site/templates/website/components/_alert.html.twig
M  site/templates/website/components/_badge.html.twig
M  site/templates/website/components/_breadcrumb.html.twig
M  site/templates/website/components/_button.html.twig
M  site/templates/website/components/_card.html.twig
M  site/templates/website/components/_cta.html.twig
M  site/templates/website/components/_footer.html.twig
M  site/templates/website/components/_form_checkbox.html.twig
M  site/templates/website/components/_form_input.html.twig
M  site/templates/website/components/_form_select.html.twig
M  site/templates/website/components/_form_textarea.html.twig
M  site/templates/website/components/_navbar.html.twig
```

Foundation/UI-kit tracked templates:

```text
M  site/templates/website/pages/ui_kit.html.twig
M  site/templates/website/sections/_base.html.twig
M  site/templates/website/sections/_components_showcase.html.twig
M  site/templates/website/sections/_content.html.twig
M  site/templates/website/sections/_cta.html.twig
M  site/templates/website/sections/_forms_showcase.html.twig
M  site/templates/website/sections/_foundations_showcase.html.twig
M  site/templates/website/sections/_hero.html.twig
```

Preserved local Stage 2 templates/tests are listed in section 9. Tests changed
by Stage 1.5:

```text
M  site/tests/Website/WebsiteFoundationTest.php
local Stage 2, migrated in place  site/tests/Website/MarketingSectionsTest.php
```

Удалены пять legacy source stylesheets и их пять public mirrors, перечисленные
в section 3. Другие repository files не изменялись в рамках миграции.

## Definition of Done

Все применимые пункты Stage 1.5 закрыты: Bootstrap runtime равен нулю, Tailwind
является единственным website CSS framework, exact version/build воспроизводим,
Design System и production components сохранены, обе UI-kit страницы работают
адаптивно и доступны с клавиатуры, local Stage 2 work сохранён, tests/CI,
self-review и Claude Code review завершены.
