# Отчёт по Stage 1 — Website Foundation / Design System

Дата завершения: 2026-08-16
Базовый commit для diff: `6f58dee` (`HEAD` до реализации Stage 1)
Статус: **выполнено**

## 1. Что реализовано

Создан фундамент публичного сайта «Ваш Финдир» без разработки полноценной
главной страницы:

- `SITE_RULES.md` как Source of Truth для design system;
- обязательная ссылка на правила в `AGENTS.md`;
- централизованные design tokens, typography и spacing;
- архитектура Twig `layout → page → section → component`;
- Bootstrap 5.3.3 как единственный UI framework;
- production components и reusable sections;
- доступная по `GET /ui-kit` страница визуального контроля;
- source/public workflow для CSS с защитой от drift и stale-файлов;
- content-hash cache-buster для immutable static assets;
- functional и static regression tests;
- CI-проверка публичного CSS mirror;
- responsive, keyboard, accessibility и visual проверки;
- self-review и несколько независимых Claude Code review с исправлением
  обоснованных замечаний.

Границы Stage соблюдены: главная страница, CMS, React/SPA, новый JavaScript,
новые пакеты, база данных, миграции, очереди и production deployment не
изменялись.

## 2. Какие файлы созданы или изменены

Изменены:

- `.github/workflows/ci.yml` — проверка CSS mirror через единый Make target;
- `AGENTS.md` — ссылка на обязательный Source of Truth и удаление случайной
  внешней Markdown-ограды;
- `Makefile` — `assets`, `asset-version`, `assets-check`, включение проверки в
  `lint`/`ci`.

Созданы:

- `SITE_RULES.md`;
- `site/src/Controller/UiKitController.php`;
- `site/tests/Website/WebsiteFoundationTest.php`;
- 22 Twig-файла в `site/templates/website/`;
- 5 редактируемых CSS-файлов в `site/assets/styles/website/`;
- их 5 проверяемых public-копий в `site/public/assets/website/`.

Public CSS является генерируемым зеркалом source, а не вторым местом
редактирования. `make assets` полностью пересоздаёт каталог, а `make
assets-check`, PHPUnit и GitHub Actions проверяют идентичность.

## 3. Какие Design Tokens определены

В `tokens.css` определён минимальный используемый набор:

- colors: primary/hover/soft, text, muted, background, surface, borders,
  success, warning, danger, on-primary и focus;
- typography: TT Norms Pro token с безопасным fallback, веса `400/500/700`,
  H1–H4, Lead, Body, Small и Caption;
- spacing: `4/8/12/16/24/32/48/64/80/96`;
- layout: container `1200px`, content width `720px`, responsive gutters,
  section/card/grid/form spacing;
- controls: minimum target height `44px`, disabled opacity, focus width/offset;
- borders, functional icon stroke, radius и один card shadow;
- Bootstrap breakpoints `576/768/992/1200/1400`;
- Bootstrap root variables, связанные с VF tokens.

TT Norms Pro не загружается без подтверждённой лицензии; используется
задокументированный fallback.

## 4. Какие Components реализованы

Production Twig partials созданы для:

- Button, включая primary, outline-primary и CTA-only `on-primary`;
- Card;
- Badge;
- Alert;
- Form Input;
- Select;
- Textarea;
- Checkbox;
- Accordion;
- Breadcrumb;
- Navbar;
- Footer;
- CTA panel.

Для применимых элементов показаны Default, Hover, Focus, Active, Disabled,
Error и Success. Error/Success имеют текстовую обратную связь и ARIA-связи.
Принудительно показанные UI-kit состояния используют те же declaration blocks,
что реальные pseudo-classes.

## 5. Какие Sections реализованы

- общий `Section Base` с единым `.container`, spacing и `aria-labelledby`;
- Hero;
- Content Section;
- CTA Section;
- технические UI-kit sections для foundations, components и forms.

Page собирается из sections, sections используют production components.
Отдельные page-specific копии markup и визуальных правил не созданы.

## 6. Что доступно на `/ui-kit`

Маршрут `GET /ui-kit` возвращает production-rendered страницу с:

- Typography H1–H4, Lead, Body, Small, Caption и весами 400/500/700;
- палитрой Colors;
- полной Spacing scale;
- всеми обязательными Components;
- состояниями Buttons и form controls;
- Hero, Content и CTA Sections;
- рабочими Bootstrap Navbar Collapse и Accordion;
- общими website layout, footer и assets.

Страница содержит один H1 и `<meta name="robots" content="noindex,
nofollow">`. UI-kit не добавлен в default navigation будущих публичных страниц;
ссылка присутствует только в navigation block самого UI-kit.

## 7. Какие тесты и проверки выполнены

Финальный `make ci` — **PASS**:

- CSS source/public diff — PASS;
- Composer validate — PASS;
- Composer audit — уязвимостей не найдено;
- YAML — 9 файлов валидны;
- Twig — 40 файлов валидны;
- Symfony container lint — PASS;
- PHP CS Fixer — 0 замечаний;
- PHPStan — 0 ошибок;
- Deptrac — 0 violations, 0 uncovered, 0 errors;
- PHPUnit — **7 tests, 948 assertions, PASS**.

Новый test suite проверяет HTTP 200, обязательные components/sections/assets,
формы и состояния, отсутствие inline CSS/JS/event handlers, отсутствие
Bootstrap color utilities, hardcoded colors и произвольных CSS units,
динамический охват всего CSS-дерева, идентичность source/public и соответствие
cache-buster содержимому CSS.

Уровни тестирования:

| Уровень | Результат |
|---|---|
| Unit | `N/A`: изолированная бизнес-логика не добавлялась |
| Integration | `N/A`: БД, Doctrine, Messenger и внешние adapters не менялись |
| Functional | Добавлен `WebsiteFoundationTest`; существующий SmokeTest сохранён |
| E2E | Одноразовый Playwright-прогон по реальному HTTP выполнен; постоянный Node toolchain не добавлялся |

## 8. Результат responsive проверки

Одноразовый Playwright browser-check пройден на `375`, `768`, `1024` и
`1440px`:

- HTTP 200 и 0 failed resources;
- horizontal overflow отсутствует;
- все form controls входят в viewport;
- cards перестраиваются через Bootstrap grid;
- Navbar раскрывается на 375/768 и остаётся expanded на 1024/1440;
- Accordion раскрывается на всех ширинах;
- H1: `40px` на 375 и `56px` начиная с 768;
- section padding: `48/64/80/80px`;
- загружаются 5 website CSS-файлов;
- первый Tab переводит focus на skip link;
- реальный Button/Select/Checkbox/CTA focus имеет видимый `2px` outline без
  Bootstrap shadow.

Desktop и mobile screenshots просмотрены визуально после финальных CSS-правок.
Browser-check был целевой проверкой Stage и не коммитился как новый frontend
toolchain.

## 9. Результат accessibility проверки

- Lighthouse 13.4.1 Accessibility: **1.00**;
- failed accessibility audits: **нет**;
- semantic landmarks и heading hierarchy проверены;
- labels связаны с controls;
- ошибки и успех не передаются только цветом;
- keyboard navigation, skip link и видимый focus проверены в браузере;
- focus CTA использует контрастный белый outline на primary surface;
- prefers-reduced-motion учтён.

Результат Lighthouse и browser-check подтверждает baseline Stage 1, но не
является формальной сертификацией полного соответствия WCAG.

## 10. Результат AI anti-pattern review

В полном diff и финальных screenshots не обнаружены:

- Card inside Card и чрезмерное повторение карточных секций;
- шаблонное `heading + text + 3 equal cards`;
- gradients, glow, glassmorphism, blur и decorative blobs;
- бессмысленные icons, pills и декоративные variants;
- чередование цветных backgrounds ради разнообразия;
- fake metrics, charts, dashboards, testimonials или logos;
- oversized Hero и неработающие псевдоинтерактивные элементы.

Композиция использует typography, whitespace, понятную иерархию, Bootstrap
grid и существующие production patterns.

## 11. Результат self-review

Повторно проверены задача, Definition of Done и полный implementation diff.
Итог:

- scope не расширен;
- бизнес-модули, права доступа, неопубликованные данные и публичные
  бизнес-контракты не затронуты;
- DB schema и данные не менялись, migration не требуется;
- циклических зависимостей и дублирования бизнес-логики нет;
- inline CSS/JS, произвольные colors/units и второй framework отсутствуют;
- CSS mirror и cache hash детерминированы и проверяются CI;
- secrets и чувствительные данные не добавлены;
- `git diff --check` проходит;
- все обоснованные blocker/high/medium замечания исправлены и проверки
  повторены.

## 12. Результат external review

Claude Code использовался как независимый reviewer по переданным полным и
targeted diff. Его tool-доступ был отключён, поэтому фактические тесты запускал
основной агент; reviewer оценивал требования и код из diff.

В ходе проходов были найдены и исправлены:

- 3 High первого полного review: parity реального focus, Card-in-Card-like
  showcase surface и неполные form states;
- Medium по Bootstrap variables/Alert, UI-kit-only CSS, palette leakage,
  navigation exposure, CSS mirror, immutable cache hash, dynamic test coverage
  и CTA variant;
- 1 High targeted review: variant-specific Hover simulation расходилась с
  реальными hover variants;
- дополнительные Medium по CI target, deterministic sort, inline handlers,
  Bootstrap color utilities, CSS units/breakpoints и icon stroke token.

Финальный targeted verdict после исправления последнего High:

- blocker: **0**;
- high: **0**;
- H1 variant-specific Hover parity: **FIXED**.

Два финальных замечания классифицированы как non-actionable:

- Navbar и Accordion не требуют `vf-is-*`: это живые интерактивные components,
  их реальные Hover/Focus проверяются непосредственно на UI-kit;
- предполагаемые duplicate CSS declarations были артефактом склеенного review
  excerpt и отсутствуют в исходных файлах.

Остальные относящиеся Medium исправлены; после них `make ci`, Playwright и
Lighthouse повторно прошли.

## 13. Известные ограничения

- Лицензированные TT Norms Pro files не предоставлены: активен fallback Arial.
- Bootstrap 5.3.3 пока доставляется существующим versioned jsDelivr URL с SRI;
  для offline/CSP/privacy requirement потребуется отдельная задача на local
  vendoring.
- `/ui-kit` имеет `noindex`, но не закрыт auth/env guard: Stage требует
  доступную визуальную страницу, ограничение доступа не было частью ТЗ.
- Playwright и Lighthouse запускались как одноразовые Docker checks и не
  добавлены в постоянный Node-based CI, чтобы не вводить новый toolchain.
- Legacy templates вне `site/templates/website/` не мигрировались и могут иметь
  старые inline/page-specific решения; это явно исключено из Stage 1.
- Public CSS mirror необходим текущему Nginx public root; source остаётся
  единственным местом ручного изменения.
- Полноценная главная страница и остальные задачи раздела «Что НЕ входит в
  Stage 1» не реализовывались.

## Приложение A. Полный implementation diff

Ниже приведён полный diff реализации относительно `HEAD 6f58dee`, включая все
tracked и новые implementation-файлы. Сам файл отчёта исключён из собственного
приложения: включение отчёта в содержащийся в нём diff создало бы бесконечную
самоссылку. Путь отчёта и его содержание находятся непосредственно перед этим
приложением.

`````diff
diff --git a/.github/workflows/ci.yml b/.github/workflows/ci.yml
index 8be087f..60cca2a 100644
--- a/.github/workflows/ci.yml
+++ b/.github/workflows/ci.yml
@@ -48,6 +48,10 @@ jobs:
             - name: Composer audit
               run: composer audit
 
+            - name: Check website asset mirror
+              working-directory: .
+              run: make assets-check
+
             - name: Lint YAML
               run: php bin/console lint:yaml config
 
diff --git a/AGENTS.md b/AGENTS.md
index 4d165fc..15f9799 100644
--- a/AGENTS.md
+++ b/AGENTS.md
@@ -1,4 +1,3 @@
-````
 # AGENTS.md
 
 ## 1. Назначение проекта
@@ -169,6 +168,19 @@ site/tests/
 └── Shared/
 ```
 
+## Public Website
+
+Для всех изменений публичного сайта агент обязан полностью прочитать и соблюдать:
+
+- `SITE_RULES.md`.
+
+`SITE_RULES.md` — Source of Truth для design system, design tokens, typography,
+spacing, Twig-структуры, UI components, section architecture, CSS, responsive,
+accessibility baseline и AI-generated UI anti-patterns публичного сайта.
+
+Не создавать новые UI patterns, произвольные стили, дубли компонентов или
+декоративные варианты, противоречащие `SITE_RULES.md`.
+
 ## 6. Правила модульности
 
 ### 6.1. Владелец данных
@@ -689,5 +701,3 @@ make deptrac
 Сначала использовать простое решение внутри Symfony-монолита.
 
 Новый слой, очередь, сервис, язык или frontend-приложение добавляются только тогда, когда существующее решение уже не удовлетворяет конкретной подтверждённой задаче.
-
-````
\ No newline at end of file
diff --git a/Makefile b/Makefile
index 22fd2ff..b7c9616 100644
--- a/Makefile
+++ b/Makefile
@@ -9,7 +9,7 @@ UID := $(shell id -u)
 GID := $(shell id -g)
 
 .PHONY: init prepare build rebuild install update up down restart check console migrate diff shell logs cache-clear clean-cache clean-local ps deptrac \
-        lint cs cs-fix phpstan test ci \
+        assets asset-version assets-check lint cs cs-fix phpstan test ci \
         traefik-config traefik-network traefik-up traefik-logs traefik-ps
 
 # Первый запуск Symfony dev после clone
@@ -78,9 +78,21 @@ cache-clear:
 deptrac:
 	$(CLI) vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress
 
+# Публичное зеркало редактируемого CSS source. Nginx раздаёт только site/public.
+assets:
+	rm -rf -- site/public/assets/website
+	mkdir -p site/public/assets/website
+	cp -R site/assets/styles/website/. site/public/assets/website/
+
+asset-version:
+	@find site/assets/styles/website -type f -name '*.css' -print0 | LC_ALL=C sort -z | xargs -0 cat | sha256sum | cut -c1-12
+
+assets-check:
+	diff -ru site/assets/styles/website site/public/assets/website
+
 # --- Проверки. Порядок тот же, что в .github/workflows/ci.yml: от дешёвых к дорогим ---
 
-lint:
+lint: assets-check
 	$(CLI) composer validate --strict
 	$(CLI) composer audit
 	$(CLI) php bin/console lint:yaml config
diff --git a/SITE_RULES.md b/SITE_RULES.md
new file mode 100644
index 0000000..dcab7f7
--- /dev/null
+++ b/SITE_RULES.md
@@ -0,0 +1,383 @@
+# Правила публичного сайта «Ваш Финдир»
+
+## 1. Статус документа
+
+`SITE_RULES.md` — Source of Truth для публичного сайта. Все новые и изменяемые
+публичные страницы, секции, Twig-компоненты и website assets обязаны
+соответствовать этому документу.
+
+Если нужного решения здесь нет, разработчик не создаёт новый визуальный
+паттерн молча. Сначала он проходит алгоритм расширения из раздела 12 и при
+обоснованной необходимости обновляет этот документ вместе с реализацией.
+
+Legacy-шаблоны вне `site/templates/website/` мигрируют по отдельным задачам.
+Новый код не должен копировать их page-specific решения.
+
+## 2. Технологическая база
+
+- Symfony;
+- Twig;
+- Bootstrap 5.3.3 — единственный базовый UI framework;
+- CSS;
+- JavaScript — только для необходимой функциональной интерактивности.
+
+Правила:
+
+- не подключать второй UI или CSS framework;
+- использовать Bootstrap-компонент, если он корректно решает задачу;
+- не писать собственный JavaScript-аналог Bootstrap-компонента;
+- не добавлять CSS/JS-зависимость ради одного простого элемента;
+- не добавлять JS только для декоративной анимации;
+- не помещать CSS или JavaScript inline в Twig;
+- подключать website assets централизованно в website layout.
+
+Stage 1 использует существующий проектный способ доставки Bootstrap через
+versioned jsDelivr URL с SRI. Если появится требование автономной доступности,
+CSP или отдельное решение по передаче данных CDN, Bootstrap необходимо
+завендорить локально без смены UI framework.
+
+## 3. Структура
+
+Twig:
+
+```text
+site/templates/website/
+├── layouts/       # Общий каркас страницы, metadata и assets
+├── pages/         # Композиция конкретных страниц
+├── sections/      # Крупные переиспользуемые блоки
+└── components/    # Малые UI-компоненты
+```
+
+CSS source:
+
+```text
+site/assets/styles/website/
+├── tokens.css
+├── base.css
+├── components/
+└── sections/
+```
+
+Статические файлы текущего Nginx публикуются из
+`site/public/assets/website/`. Это проверяемое зеркало CSS source, а не второй
+источник правил. Редактировать можно только source. После изменения выполнить
+`make assets`, затем `make assets-check`. `make assets` сначала полностью
+пересоздаёт зеркало, поэтому удалённый или переименованный source-файл не
+остаётся в public.
+
+Release query определяется один раз в website layout и равен первым 12 знакам
+SHA-256 конкатенации всех CSS source в сортированном порядке. Значение выводит
+`make asset-version`, а functional test проверяет соответствие. Это обязательно,
+потому что Nginx отдаёт static assets с immutable cache.
+
+Page преимущественно собирает sections. Section использует общий section
+pattern и готовые components. Layout не содержит page-specific разметку.
+Бизнес-логика, запросы к БД и вычисления не размещаются в Twig.
+
+Page-specific CSS допускается только для уникального поведения, которое нельзя
+выразить существующими tokens, components или sections. Причина фиксируется в
+этом документе до добавления CSS.
+
+## 4. Design Tokens
+
+Единственный источник базовых визуальных значений —
+`site/assets/styles/website/tokens.css`.
+
+### 4.1. Colors
+
+| Назначение | Token |
+|---|---|
+| Brand/action | `--vf-color-primary` |
+| Brand hover/active | `--vf-color-primary-hover` |
+| Основной текст | `--vf-color-text` |
+| Вторичный текст | `--vf-color-muted` |
+| Фон страницы | `--vf-color-background` |
+| Поверхность | `--vf-color-surface` |
+| Граница | `--vf-color-border` |
+| Граница form control | `--vf-color-border-strong` |
+| Успех | `--vf-color-success` |
+| Предупреждение | `--vf-color-warning` |
+| Ошибка | `--vf-color-danger` |
+
+Нельзя задавать произвольный цвет в component, section или page. Новый цвет
+добавляется только как семантический token с подтверждённым назначением.
+
+### 4.2. Typography
+
+Primary font token:
+
+```text
+"TT Norms Pro", "TT Norms", Arial, sans-serif
+```
+
+Разрешённые веса: `400`, `500`, `700`.
+
+| Style | Desktop | Mobile | Weight |
+|---|---|---|---|
+| H1 | 56px / 60px | 40px / 44px | 700 |
+| H2 | 40px / 44px | 32px / 36px | 700 |
+| H3 | 32px / 36px | 24px / 30px | 700 |
+| H4 | 24px / 30px | 20px / 26px | 700 |
+| Lead | 20px / 30px | 18px / 28px | 400 |
+| Body | 16px / 24px | 16px / 24px | 400 |
+| Small | 14px / 20px | 14px / 20px | 400 |
+| Caption | 12px / 16px | 12px / 16px | 500 |
+
+Font family, size, line-height и weight задаются tokens. Страница не создаёт
+новый размер текста. На обычной публичной странице один H1; визуальная и
+семантическая иерархия заголовков совпадают. Long-form text использует
+`--vf-content-max`.
+
+Файлы TT Norms Pro не подключены, пока право webfont-использования не
+подтверждено. Запрещено копировать шрифт с `tochka.com`, hotlink-ить его или
+иной чужой CDN. До легального подключения работает указанный fallback.
+
+### 4.3. Spacing
+
+Базовая единица — `4px`. Разрешённая шкала:
+
+```text
+4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 80 / 96
+```
+
+Правила:
+
+- icon ↔ text: `8px`;
+- button ↔ button: `12px`;
+- form field ↔ form field: `16px`;
+- title ↔ description: `16px`;
+- обычные content blocks: `24px` или `32px`;
+- cards grid gap: `24px`;
+- card padding: desktop `24px`, mobile `16px`;
+- section vertical padding: desktop `80px`, tablet `64px`, mobile `48px`.
+
+Использовать только `--vf-space-*` и семантические tokens. Значения вроде
+`37px`, `53px` и `71px` ради визуальной подгонки запрещены.
+
+`--vf-control-min-height: 44px` — не spacing, а минимальная touch target height
+для интерактивного control. `--vf-showcase-item-min` применяется только в
+техническом UI-kit для читаемой раскладки token previews.
+
+### 4.4. Containers, borders, radius, shadows
+
+- основной container: Bootstrap `.container`, максимум
+  `--vf-container-max: 1200px`;
+- ширина чтения: `--vf-content-max: 720px`;
+- горизонтальный gutter: `16px` mobile, `24px` начиная с tablet;
+- borders используют `--vf-border-width` и `--vf-color-border`;
+- линии функциональных CSS-иконок используют `--vf-icon-stroke-width`;
+- radius: `--vf-radius-sm`, `--vf-radius-md`, `--vf-radius-lg`;
+- shadow допускается только через `--vf-shadow-card` и только когда помогает
+  отделить интерактивную или приподнятую поверхность.
+
+Не создавать `.container-home`, `.container-hero` или аналогичные контейнеры.
+Не увеличивать radius и shadow ради декоративного разнообразия.
+
+### 4.5. Breakpoints
+
+Используются Bootstrap breakpoints:
+
+```text
+sm 576px
+md 768px
+lg 992px
+xl 1200px
+xxl 1400px
+```
+
+Их tokens документируют общую шкалу. CSS custom properties нельзя применять
+в media query, поэтому media query повторяет только эти зафиксированные
+значения. Новый breakpoint без отдельного адаптивного сценария запрещён.
+
+## 5. Базовые Components
+
+Stage 1 определяет:
+
+- Button;
+- Card;
+- Badge;
+- Alert;
+- Form Input;
+- Select;
+- Textarea;
+- Checkbox;
+- Accordion;
+- Breadcrumb;
+- Navbar;
+- Footer;
+- CTA panel.
+
+Production Twig partial является единственной реализацией компонента. UI-kit и
+будущие страницы подключают тот же partial. Bootstrap отвечает за базовую
+семантику и интерактивность; VF CSS только применяет tokens и согласованные
+состояния.
+
+CTA использует токенизированный Button variant `on-primary`, предназначенный
+только для действия на primary surface и показанный внутри CTA на `/ui-kit`.
+
+Для применимых компонентов обязательны Default, Hover, Focus, Active,
+Disabled, Error и Success. Focus должен быть виден с клавиатуры. Disabled
+элемент не должен выглядеть доступным. Error/Success передаются не только
+цветом, но и текстом состояния.
+
+## 6. Section Architecture
+
+Общий pattern:
+
+```text
+section.vf-section
+└── .container
+    └── content
+```
+
+`sections/_base.html.twig` владеет каркасом. Stage 1 содержит Hero, Content
+Section и CTA Section. Секция не задаёт собственные container width,
+typography scale, button system, card system или breakpoints.
+
+Hero не раздувается декоративным пространством. CTA Section использует общий
+CTA component. Новая секция сначала комбинирует существующие components и
+общий section pattern; global CSS для каждой новой секции не меняется.
+
+## 7. Grid и responsive
+
+Bootstrap container/grid используется последовательно. Mobile — состояние того
+же компонента, не отдельный template или отдельная версия сайта.
+
+Обязательные контрольные ширины: `375px`, `768px`, `1024px`, `1440px`.
+На каждой ширине проверяются:
+
+- отсутствие horizontal overflow;
+- работа Navbar и Accordion;
+- попадание forms и text в viewport;
+- доступность кнопок;
+- перестроение cards;
+- иерархия Hero;
+- адаптивные typography и section spacing;
+- доступность интерактивных элементов с клавиатуры.
+
+Не скрывать дефект layout глобальным `overflow-x: hidden`.
+
+## 8. Accessibility baseline
+
+- использовать semantic HTML landmarks;
+- соблюдать последовательность H1 → H2 → H3;
+- связывать каждый form control с `label`;
+- использовать `button` для действия и `a` для навигации;
+- давать интерактивному элементу понятное accessible name;
+- обеспечивать keyboard navigation и видимый `:focus-visible`;
+- задавать содержательный `alt` meaningful image;
+- декоративное изображение задавать как `alt=""` и исключать из
+  accessibility tree при необходимости;
+- не передавать критическое состояние только цветом;
+- сохранять достаточный contrast;
+- учитывать `prefers-reduced-motion`, если motion когда-либо появится.
+
+Stage 1 не добавляет изображения и декоративную анимацию.
+
+## 9. UI-kit
+
+`/ui-kit` — визуальный Source of Truth Design System. Он использует website
+layout и production components/sections, а не демонстрационные копии.
+
+На одной странице показаны Typography, Colors, Spacing, Buttons, Badges,
+Cards, Alerts, Forms, Accordion, Breadcrumbs, Navigation, Hero, Content
+Section и CTA. Основной H1 только один. Состояния компонентов должны быть
+доступны для визуальной и клавиатурной проверки.
+
+## 10. CSS и JavaScript
+
+- NO inline CSS (`style=""` и `<style>`);
+- NO inline JavaScript;
+- external `<script src>` допускается только для согласованной функциональности;
+- NO arbitrary colors, typography, spacing или border-radius;
+- NO page-specific copies of reusable components;
+- NO duplicated Twig components;
+- NO desktop-only components;
+- NO second UI framework;
+- NO new component, если существующий решает задачу.
+
+CSS components/sections использует tokens. Hardcoded base values допустимы
+только внутри `tokens.css`; технические media queries используют только
+зафиксированные Bootstrap breakpoints.
+
+UI-kit-only styles находятся в `components/showcase.css` и подключаются только
+страницей `/ui-kit`, а не общим production layout. Исключение — классы
+симуляции состояния `vf-is-*`: они задаются production partial только по
+явному параметру UI-kit и находятся в одном declaration block с реальным
+pseudo-class. Такое co-location гарантирует, что демонстрация Focus/Hover не
+расходится с production-состоянием.
+
+## 11. AI-generated UI anti-patterns
+
+**The agent MUST NOT introduce visual variety only for decorative purposes.**
+
+Новый визуальный pattern обязан решать функциональную, информационную,
+иерархическую, адаптивную или accessibility-задачу. «Чтобы выглядело
+интереснее» — недостаточная причина.
+
+Без явной смысловой причины запрещены:
+
+- чрезмерное использование Cards и Card inside Card;
+- повторение `heading → text → 3 equal cards` в большинстве секций;
+- badge/pill или decorative icon возле каждого заголовка и пункта;
+- gradients, glow, glassmorphism, decorative blur и blobs;
+- случайные abstract background shapes и floating elements;
+- чрезмерные radius и shadows;
+- центрирование всей страницы или длинного текста;
+- отдельный цветной фон почти для каждой секции;
+- автоматическое чередование фонов ради разнообразия;
+- oversized Hero с малым количеством полезной информации;
+- новый layout/variant только для внешнего отличия соседних секций;
+- fake dashboard, chart, product screenshot, customer metric, testimonial,
+  company logo или декоративная статистика;
+- элементы, выглядящие интерактивными, но ничего не делающие;
+- fake data used as visual decoration.
+
+Предпочтительный порядок: content and hierarchy first, decoration second.
+Основные инструменты — typography, whitespace, content hierarchy, clear grid,
+consistent spacing, reusable components, реальные данные и изображения.
+
+## 12. Алгоритм расширения
+
+До создания UI pattern ответить по порядку:
+
+1. Есть ли подходящее решение в Bootstrap 5?
+2. Есть ли уже VF Component?
+3. Есть ли уже VF Section Pattern?
+4. Можно ли расширить существующий component без изменения его смысла?
+5. Можно ли решить задачу комбинацией существующих components?
+6. Какую конкретную функциональную, информационную, адаптивную или
+   accessibility-проблему решает новый pattern?
+7. Только после этого создать минимальный pattern и добавить его правило в
+   `SITE_RULES.md`.
+
+Новая страница не является причиной для «немного другого дизайна» уже
+существующего компонента.
+
+## 13. Проверки изменения
+
+Минимум:
+
+```bash
+make assets
+make asset-version  # перенести результат в vf_asset_version в website layout
+make assets-check
+make lint
+make cs
+make phpstan
+make deptrac
+make test
+```
+
+Дополнительно для website UI:
+
+- functional test `GET /ui-kit → 200`;
+- рендер production sections/components;
+- static scan `site/templates/website` на inline CSS/JS;
+- static scan component/section CSS на hardcoded colors;
+- browser checks на `375/768/1024/1440`;
+- keyboard/focus и accessibility audit;
+- visual review по AI anti-patterns.
+
+Не утверждать, что browser, Lighthouse или external review пройдены, если они
+фактически не запускались.
diff --git a/site/src/Controller/UiKitController.php b/site/src/Controller/UiKitController.php
new file mode 100644
index 0000000..0f3be90
--- /dev/null
+++ b/site/src/Controller/UiKitController.php
@@ -0,0 +1,18 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Controller;
+
+use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
+use Symfony\Component\HttpFoundation\Response;
+use Symfony\Component\Routing\Attribute\Route;
+
+final class UiKitController extends AbstractController
+{
+    #[Route('/ui-kit', name: 'ui_kit', methods: ['GET'])]
+    public function __invoke(): Response
+    {
+        return $this->render('website/pages/ui_kit.html.twig');
+    }
+}
diff --git a/site/tests/Website/WebsiteFoundationTest.php b/site/tests/Website/WebsiteFoundationTest.php
new file mode 100644
index 0000000..050750a
--- /dev/null
+++ b/site/tests/Website/WebsiteFoundationTest.php
@@ -0,0 +1,202 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Tests\Website;
+
+use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
+
+final class WebsiteFoundationTest extends WebTestCase
+{
+    public function testUiKitRendersProductionComponentsAndSections(): void
+    {
+        $client = static::createClient();
+        $client->request('GET', '/ui-kit');
+
+        self::assertResponseIsSuccessful();
+        self::assertSelectorCount(1, 'h1');
+        self::assertSelectorTextContains('h1', 'UI-kit «Ваш Финдир»');
+        foreach (['tokens.css', 'base.css', 'components/index.css', 'sections/index.css', 'components/showcase.css'] as $stylesheet) {
+            self::assertSelectorExists(sprintf('link[href^="/assets/website/%s?v="]', $stylesheet));
+        }
+
+        foreach ([
+            'button',
+            'card',
+            'badge',
+            'alert',
+            'form-input',
+            'select',
+            'textarea',
+            'checkbox',
+            'accordion',
+            'breadcrumb',
+            'navbar',
+            'footer',
+            'cta',
+        ] as $component) {
+            self::assertSelectorExists(sprintf('[data-vf-component="%s"]', $component));
+        }
+
+        foreach (['hero', 'content', 'cta'] as $section) {
+            self::assertSelectorExists(sprintf('[data-vf-section="%s"]', $section));
+        }
+
+        foreach (['typography', 'colors', 'spacing'] as $showcase) {
+            self::assertSelectorExists(sprintf('[data-vf-showcase="%s"]', $showcase));
+        }
+
+        self::assertSelectorExists('label[for="demo-name"]');
+        self::assertSelectorExists('[data-vf-state="error"] [aria-invalid="true"]');
+        self::assertSelectorExists('[data-vf-state="success"] .valid-feedback');
+
+        foreach (['select', 'textarea', 'checkbox'] as $component) {
+            foreach (['default', 'focus', 'error', 'success'] as $state) {
+                self::assertSelectorExists(sprintf(
+                    '[data-vf-component="%s"][data-vf-state="%s"]',
+                    $component,
+                    $state,
+                ));
+            }
+
+            self::assertSelectorExists(sprintf('[data-vf-component="%s"] :disabled', $component));
+        }
+    }
+
+    public function testWebsiteTwigDoesNotContainInlineCssOrJavascript(): void
+    {
+        $templates = $this->projectPath('templates/website');
+        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templates));
+        $checked = 0;
+
+        /** @var \SplFileInfo $file */
+        foreach ($files as $file) {
+            if (!$file->isFile() || 'twig' !== $file->getExtension()) {
+                continue;
+            }
+
+            $contents = $this->read($file->getPathname());
+
+            self::assertDoesNotMatchRegularExpression('/<style\b/i', $contents, $file->getPathname());
+            self::assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $contents, $file->getPathname());
+            self::assertDoesNotMatchRegularExpression('/<script\b(?![^>]*\bsrc\s*=)[^>]*>/i', $contents, $file->getPathname());
+            self::assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $contents, $file->getPathname());
+            self::assertDoesNotMatchRegularExpression(
+                '/(?<!vf-)\b(?:text|bg|border)-(?:primary|secondary|success|danger|warning|info|light|dark|white|black|body|muted)\b/i',
+                $contents,
+                $file->getPathname(),
+            );
+            ++$checked;
+        }
+
+        self::assertGreaterThan(0, $checked);
+    }
+
+    public function testComponentAndSectionCssUseTokensForColors(): void
+    {
+        foreach ($this->websiteCssFiles('assets/styles/website') as $relativePath => $path) {
+            if ('tokens.css' === $relativePath) {
+                continue;
+            }
+
+            self::assertDoesNotMatchRegularExpression(
+                '/#[0-9a-f]{3,8}\b|\b(?:rgb|hsl|oklch)a?\s*\(|\b(?:color-mix|light-dark)\s*\(|\b(?:white|black|red|green|blue|gray|grey|pink|orange|yellow|purple)\b|var\(--bs-/i',
+                $this->read($path),
+                $relativePath,
+            );
+            self::assertDoesNotMatchRegularExpression(
+                '/^\s*(?:color|background(?:-color)?|border(?:-[a-z-]+)?-color|outline-color|fill|stroke)\s*:\s*(?!var\(|currentcolor\b|transparent\b|none\b|inherit\b|initial\b|unset\b)[a-z-]+\b/im',
+                $this->read($path),
+                $relativePath,
+            );
+        }
+    }
+
+    public function testComponentAndSectionCssDoNotDefineArbitraryPixelValues(): void
+    {
+        foreach ($this->websiteCssFiles('assets/styles/website') as $relativePath => $path) {
+            if ('tokens.css' === $relativePath) {
+                continue;
+            }
+
+            foreach (explode("\n", $this->read($path)) as $line) {
+                if (1 === preg_match('/^\s*@media\s+\((?:min|max)-width:\s*(?:575\.98|767\.98|991\.98|1199\.98|1399\.98|576|768|992|1200|1400)px\)(?:\s+and\s+\((?:min|max)-width:\s*(?:575\.98|767\.98|991\.98|1199\.98|1399\.98|576|768|992|1200|1400)px\))?\s*\{\s*$/', $line)) {
+                    continue;
+                }
+
+                self::assertDoesNotMatchRegularExpression(
+                    '/\b\d+(?:\.\d+)?(?:px|rem|em|vh|vw|vmin|vmax|ch|ex)\b/',
+                    $line,
+                    $relativePath,
+                );
+            }
+        }
+    }
+
+    public function testPublishedAssetsMatchTheirSource(): void
+    {
+        $sourceFiles = $this->websiteCssFiles('assets/styles/website');
+        $publishedFiles = $this->websiteCssFiles('public/assets/website');
+
+        self::assertSame(array_keys($sourceFiles), array_keys($publishedFiles));
+
+        foreach ($sourceFiles as $relativePath => $sourcePath) {
+            self::assertSame(
+                $this->read($sourcePath),
+                $this->read($publishedFiles[$relativePath]),
+                $relativePath,
+            );
+        }
+    }
+
+    public function testAssetVersionMatchesWebsiteCssContent(): void
+    {
+        $css = '';
+        foreach ($this->websiteCssFiles('assets/styles/website') as $path) {
+            $css .= $this->read($path);
+        }
+
+        $layout = $this->read($this->projectPath('templates/website/layouts/base.html.twig'));
+        self::assertSame(1, preg_match("/{% set vf_asset_version = '([0-9a-f]{12})' %}/", $layout, $matches));
+        $assetVersion = $matches[1] ?? '';
+        self::assertNotSame('', $assetVersion);
+        self::assertSame(substr(hash('sha256', $css), 0, 12), $assetVersion);
+    }
+
+    /** @return array<string, string> */
+    private function websiteCssFiles(string $relativeDirectory): array
+    {
+        $directory = $this->projectPath($relativeDirectory);
+        $files = new \RecursiveIteratorIterator(
+            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
+        );
+        $cssFiles = [];
+
+        /** @var \SplFileInfo $file */
+        foreach ($files as $file) {
+            if (!$file->isFile() || 'css' !== $file->getExtension()) {
+                continue;
+            }
+
+            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
+            $cssFiles[$relativePath] = $file->getPathname();
+        }
+
+        ksort($cssFiles, SORT_STRING);
+
+        return $cssFiles;
+    }
+
+    private function projectPath(string $relativePath): string
+    {
+        return dirname(__DIR__, 2).'/'.$relativePath;
+    }
+
+    private function read(string $path): string
+    {
+        $contents = file_get_contents($path);
+        self::assertNotFalse($contents, $path);
+
+        return $contents;
+    }
+}
diff --git a/site/assets/styles/website/base.css b/site/assets/styles/website/base.css
new file mode 100644
index 0000000..aeac8e4
--- /dev/null
+++ b/site/assets/styles/website/base.css
@@ -0,0 +1,144 @@
+*,
+*::before,
+*::after {
+    box-sizing: border-box;
+}
+
+html {
+    scroll-behavior: smooth;
+}
+
+body {
+    margin: 0;
+    background: var(--vf-color-background);
+    color: var(--vf-color-text);
+    font-family: var(--vf-font-primary);
+    font-size: var(--vf-font-size-body);
+    font-weight: var(--vf-font-weight-regular);
+    line-height: var(--vf-line-height-body);
+    overflow-wrap: break-word;
+}
+
+a {
+    color: var(--vf-color-primary);
+    text-underline-offset: var(--vf-space-1);
+}
+
+a:hover {
+    color: var(--vf-color-primary-hover);
+}
+
+code {
+    color: var(--vf-color-primary);
+}
+
+:focus-visible {
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+}
+
+h1,
+.vf-h1,
+h2,
+.vf-h2,
+h3,
+.vf-h3,
+h4,
+.vf-h4 {
+    margin-block: 0 var(--vf-space-4);
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+h1,
+.vf-h1 {
+    font-size: var(--vf-font-size-h1);
+    line-height: var(--vf-line-height-h1);
+}
+
+h2,
+.vf-h2 {
+    font-size: var(--vf-font-size-h2);
+    line-height: var(--vf-line-height-h2);
+}
+
+h3,
+.vf-h3 {
+    font-size: var(--vf-font-size-h3);
+    line-height: var(--vf-line-height-h3);
+}
+
+h4,
+.vf-h4 {
+    font-size: var(--vf-font-size-h4);
+    line-height: var(--vf-line-height-h4);
+}
+
+p {
+    margin-block: 0 var(--vf-space-4);
+}
+
+.vf-lead {
+    font-size: var(--vf-font-size-lead);
+    line-height: var(--vf-line-height-lead);
+}
+
+.vf-small {
+    font-size: var(--vf-font-size-small);
+    line-height: var(--vf-line-height-small);
+}
+
+.vf-caption {
+    font-size: var(--vf-font-size-caption);
+    font-weight: var(--vf-font-weight-medium);
+    line-height: var(--vf-line-height-caption);
+    letter-spacing: var(--vf-letter-spacing-caption);
+    text-transform: uppercase;
+}
+
+.vf-font-weight-regular {
+    font-weight: var(--vf-font-weight-regular);
+}
+
+.vf-font-weight-medium {
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.vf-font-weight-bold {
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-text-muted {
+    color: var(--vf-color-muted);
+}
+
+.vf-content-width {
+    max-width: var(--vf-content-max);
+}
+
+.container {
+    width: 100%;
+    max-width: var(--vf-container-max);
+    padding-inline: var(--vf-container-gutter);
+}
+
+.vf-skip-link {
+    position: fixed;
+    z-index: 1100;
+    top: var(--vf-space-2);
+    left: var(--vf-space-2);
+    padding: var(--vf-space-2) var(--vf-space-4);
+    border-radius: var(--vf-radius-sm);
+    background: var(--vf-color-surface);
+    transform: translateY(-200%);
+}
+
+.vf-skip-link:focus {
+    transform: translateY(0);
+}
+
+@media (prefers-reduced-motion: reduce) {
+    html {
+        scroll-behavior: auto;
+    }
+}
diff --git a/site/assets/styles/website/components/index.css b/site/assets/styles/website/components/index.css
new file mode 100644
index 0000000..08845d8
--- /dev/null
+++ b/site/assets/styles/website/components/index.css
@@ -0,0 +1,410 @@
+.btn {
+    --bs-btn-font-weight: var(--vf-font-weight-medium);
+
+    transition-duration: var(--vf-transition-duration);
+}
+
+.btn:not(.btn-sm):not(.btn-lg) {
+    --bs-btn-padding-y: var(--vf-space-2);
+    --bs-btn-padding-x: var(--vf-space-4);
+    --bs-btn-border-radius: var(--vf-radius-md);
+
+    min-height: var(--vf-control-min-height);
+}
+
+.btn-primary {
+    --bs-btn-bg: var(--vf-color-primary);
+    --bs-btn-border-color: var(--vf-color-primary);
+    --bs-btn-hover-bg: var(--vf-color-primary-hover);
+    --bs-btn-hover-border-color: var(--vf-color-primary-hover);
+    --bs-btn-active-bg: var(--vf-color-primary-hover);
+    --bs-btn-active-border-color: var(--vf-color-primary-hover);
+    --bs-btn-disabled-bg: var(--vf-color-primary);
+    --bs-btn-disabled-border-color: var(--vf-color-primary);
+    --bs-btn-focus-shadow-rgb: var(--vf-color-primary-rgb);
+}
+
+.btn-outline-primary {
+    --bs-btn-color: var(--vf-color-primary);
+    --bs-btn-border-color: var(--vf-color-primary);
+    --bs-btn-hover-color: var(--vf-color-on-primary);
+    --bs-btn-hover-bg: var(--vf-color-primary);
+    --bs-btn-hover-border-color: var(--vf-color-primary);
+    --bs-btn-active-color: var(--vf-color-on-primary);
+    --bs-btn-active-bg: var(--vf-color-primary-hover);
+    --bs-btn-active-border-color: var(--vf-color-primary-hover);
+    --bs-btn-disabled-color: var(--vf-color-primary);
+    --bs-btn-disabled-border-color: var(--vf-color-primary);
+    --bs-btn-focus-shadow-rgb: var(--vf-color-primary-rgb);
+}
+
+.vf-btn-on-primary {
+    --bs-btn-color: var(--vf-color-primary);
+    --bs-btn-bg: var(--vf-color-on-primary);
+    --bs-btn-border-color: var(--vf-color-on-primary);
+    --bs-btn-hover-color: var(--vf-color-primary-hover);
+    --bs-btn-hover-bg: var(--vf-color-primary-soft);
+    --bs-btn-hover-border-color: var(--vf-color-on-primary);
+    --bs-btn-active-color: var(--vf-color-primary-hover);
+    --bs-btn-active-bg: var(--vf-color-primary-soft);
+    --bs-btn-active-border-color: var(--vf-color-on-primary);
+    --bs-btn-disabled-color: var(--vf-color-primary);
+    --bs-btn-disabled-bg: var(--vf-color-on-primary);
+    --bs-btn-disabled-border-color: var(--vf-color-on-primary);
+}
+
+.btn-primary:hover,
+.btn-primary.vf-is-hover {
+    background: var(--vf-color-primary-hover);
+    border-color: var(--vf-color-primary-hover);
+    color: var(--vf-color-on-primary);
+}
+
+.btn-outline-primary:hover,
+.btn-outline-primary.vf-is-hover {
+    background: var(--vf-color-primary);
+    border-color: var(--vf-color-primary);
+    color: var(--vf-color-on-primary);
+}
+
+.vf-btn-on-primary:hover,
+.vf-btn-on-primary.vf-is-hover {
+    background: var(--vf-color-primary-soft);
+    border-color: var(--vf-color-on-primary);
+    color: var(--vf-color-primary-hover);
+}
+
+.btn:focus {
+    box-shadow: none;
+}
+
+.btn:focus-visible,
+.btn.vf-is-focus {
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+    box-shadow: none;
+}
+
+.btn:disabled,
+.btn.disabled {
+    opacity: var(--vf-disabled-opacity);
+}
+
+.vf-card {
+    height: 100%;
+    border: var(--vf-border-width) solid var(--vf-color-border);
+    border-radius: var(--vf-radius-lg);
+    background: var(--vf-color-surface);
+    box-shadow: var(--vf-shadow-card);
+}
+
+.vf-card .card-body {
+    padding: var(--vf-card-padding);
+}
+
+.vf-card .card-title {
+    margin-bottom: var(--vf-space-4);
+}
+
+.vf-card .card-text:last-child {
+    margin-bottom: 0;
+}
+
+.vf-badge {
+    padding: var(--vf-space-1) var(--vf-space-2);
+    border: var(--vf-border-width) solid currentcolor;
+    border-radius: var(--vf-radius-sm);
+    background: var(--vf-color-surface);
+    color: var(--vf-color-text);
+    font-size: var(--vf-font-size-caption);
+    font-weight: var(--vf-font-weight-medium);
+    line-height: var(--vf-line-height-caption);
+}
+
+.vf-badge--success {
+    background: var(--vf-color-success-soft);
+    color: var(--vf-color-success);
+}
+
+.vf-badge--warning {
+    background: var(--vf-color-warning-soft);
+    color: var(--vf-color-warning);
+}
+
+.vf-badge--danger {
+    background: var(--vf-color-danger-soft);
+    color: var(--vf-color-danger);
+}
+
+.vf-alert {
+    --bs-alert-padding-x: var(--vf-space-4);
+    --bs-alert-padding-y: var(--vf-space-4);
+    --bs-alert-color: var(--vf-color-text);
+    --bs-alert-bg: var(--vf-color-surface);
+    --bs-alert-border-color: var(--vf-color-border);
+    --bs-alert-border-radius: var(--vf-radius-md);
+
+    margin-bottom: 0;
+}
+
+.vf-alert--success {
+    --bs-alert-bg: var(--vf-color-success-soft);
+    --bs-alert-border-color: var(--vf-color-success);
+}
+
+.vf-alert--warning {
+    --bs-alert-bg: var(--vf-color-warning-soft);
+    --bs-alert-border-color: var(--vf-color-warning);
+}
+
+.vf-alert--danger {
+    --bs-alert-bg: var(--vf-color-danger-soft);
+    --bs-alert-border-color: var(--vf-color-danger);
+}
+
+.vf-form {
+    display: grid;
+    gap: var(--vf-form-gap);
+}
+
+.form-label,
+.form-check-label {
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.form-control,
+.form-select {
+    border-color: var(--vf-color-border-strong);
+    color: var(--vf-color-text);
+}
+
+.form-select {
+    appearance: auto;
+    padding-inline-end: var(--vf-space-8);
+    background-image: none;
+}
+
+.form-control:not(.form-control-sm):not(.form-control-lg),
+.form-select:not(.form-select-sm):not(.form-select-lg) {
+    min-height: var(--vf-control-min-height);
+    border-radius: var(--vf-radius-md);
+}
+
+textarea.form-control:not(.form-control-sm):not(.form-control-lg) {
+    min-height: calc(var(--vf-control-min-height) * 3);
+}
+
+.form-control:focus,
+.form-select:focus,
+.form-check-input:focus {
+    border-color: var(--vf-color-focus);
+    box-shadow: none;
+}
+
+.form-control:focus-visible,
+.form-select:focus-visible,
+.form-check-input:focus-visible,
+.form-control.vf-is-focus,
+.form-select.vf-is-focus,
+.form-check-input.vf-is-focus {
+    border-color: var(--vf-color-focus);
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+    box-shadow: none;
+}
+
+.form-control.is-valid,
+.form-select.is-valid,
+.form-check-input.is-valid {
+    border-color: var(--vf-color-success);
+}
+
+.form-control.is-valid,
+.form-control.is-invalid,
+.form-select.is-valid,
+.form-select.is-invalid {
+    background-image: none;
+}
+
+.form-control.is-invalid,
+.form-select.is-invalid,
+.form-check-input.is-invalid {
+    border-color: var(--vf-color-danger);
+}
+
+.form-check-input.is-valid ~ .form-check-label {
+    color: var(--vf-color-success);
+}
+
+.form-check-input.is-invalid ~ .form-check-label {
+    color: var(--vf-color-danger);
+}
+
+.valid-feedback {
+    color: var(--vf-color-success);
+}
+
+.invalid-feedback {
+    color: var(--vf-color-danger);
+}
+
+.form-check-input:checked {
+    border-color: var(--vf-color-primary);
+    background-color: var(--vf-color-primary);
+}
+
+.form-check-input.is-valid:checked {
+    border-color: var(--vf-color-success);
+    background-color: var(--vf-color-success);
+}
+
+.form-check-input.is-invalid:checked {
+    border-color: var(--vf-color-danger);
+    background-color: var(--vf-color-danger);
+}
+
+.accordion {
+    --bs-accordion-border-color: var(--vf-color-border);
+    --bs-accordion-border-radius: var(--vf-radius-md);
+    --bs-accordion-inner-border-radius: var(--vf-radius-md);
+    --bs-accordion-color: var(--vf-color-text);
+    --bs-accordion-bg: var(--vf-color-surface);
+    --bs-accordion-btn-color: var(--vf-color-text);
+    --bs-accordion-btn-bg: var(--vf-color-surface);
+    --bs-accordion-active-color: var(--vf-color-text);
+    --bs-accordion-active-bg: var(--vf-color-primary-soft);
+    --bs-accordion-btn-focus-border-color: var(--vf-color-focus);
+    --bs-accordion-btn-focus-box-shadow: none;
+    --bs-accordion-btn-icon: none;
+    --bs-accordion-btn-active-icon: none;
+}
+
+.accordion-button {
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.accordion-button::after {
+    width: var(--vf-space-3);
+    height: var(--vf-space-3);
+    border-inline-end: var(--vf-icon-stroke-width) solid currentcolor;
+    border-block-end: var(--vf-icon-stroke-width) solid currentcolor;
+    background-image: none;
+    transform: rotate(45deg);
+}
+
+.accordion-button:not(.collapsed)::after {
+    background-image: none;
+    transform: rotate(-135deg);
+}
+
+.accordion-button:focus,
+.vf-navbar .navbar-toggler:focus {
+    box-shadow: none;
+}
+
+.accordion-button:focus-visible,
+.vf-navbar .navbar-toggler:focus-visible {
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+    box-shadow: none;
+}
+
+.breadcrumb {
+    --bs-breadcrumb-divider-color: var(--vf-color-muted);
+    --bs-breadcrumb-item-active-color: var(--vf-color-muted);
+    margin-bottom: 0;
+}
+
+.vf-navbar {
+    border-bottom: var(--vf-border-width) solid var(--vf-color-border);
+    background: var(--vf-color-surface);
+}
+
+.vf-navbar .navbar-brand {
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-navbar .nav-link {
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.vf-navbar .nav-link:hover,
+.vf-navbar .nav-link:focus,
+.vf-navbar .nav-link.active {
+    color: var(--vf-color-primary);
+}
+
+.vf-navbar .navbar-toggler {
+    border-color: var(--vf-color-border-strong);
+    color: var(--vf-color-text);
+}
+
+.vf-navbar-toggler-icon {
+    display: grid;
+    gap: var(--vf-space-1);
+}
+
+.vf-navbar-toggler-icon span {
+    width: var(--vf-space-6);
+    border-top: var(--vf-icon-stroke-width) solid currentcolor;
+}
+
+.vf-footer {
+    padding-block: var(--vf-space-8);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+    background: var(--vf-color-surface);
+}
+
+.vf-footer__inner {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-4) var(--vf-space-6);
+    align-items: center;
+    justify-content: space-between;
+}
+
+.vf-footer p {
+    margin: 0;
+}
+
+.vf-cta {
+    padding: var(--vf-card-padding);
+    border-radius: var(--vf-radius-lg);
+    background: var(--vf-color-primary);
+    color: var(--vf-color-on-primary);
+}
+
+.vf-cta h2,
+.vf-cta h3 {
+    color: var(--vf-color-on-primary);
+}
+
+.vf-cta__inner {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-6);
+    align-items: center;
+    justify-content: space-between;
+}
+
+.vf-cta__copy {
+    max-width: var(--vf-content-max);
+}
+
+.vf-cta__copy p:last-child {
+    margin-bottom: 0;
+}
+
+.vf-cta :focus-visible,
+.vf-cta .vf-is-focus {
+    outline-color: var(--vf-color-on-primary);
+}
+
+@media (prefers-reduced-motion: reduce) {
+    .btn {
+        transition-duration: 0s;
+    }
+}
diff --git a/site/assets/styles/website/components/showcase.css b/site/assets/styles/website/components/showcase.css
new file mode 100644
index 0000000..3facb02
--- /dev/null
+++ b/site/assets/styles/website/components/showcase.css
@@ -0,0 +1,111 @@
+.vf-showcase {
+    display: grid;
+    gap: var(--vf-space-8);
+}
+
+.vf-showcase-group {
+    padding-block: var(--vf-space-6);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+}
+
+.vf-showcase-group > :last-child {
+    margin-bottom: 0;
+}
+
+.vf-component-row {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-3);
+    align-items: center;
+}
+
+.vf-component-state {
+    display: grid;
+    gap: var(--vf-space-2);
+}
+
+.vf-swatch-grid {
+    display: grid;
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-showcase-item-min)), 1fr));
+    gap: var(--vf-grid-gap);
+}
+
+.vf-swatch {
+    display: grid;
+    grid-template-columns: var(--vf-space-12) 1fr;
+    gap: var(--vf-space-3);
+    align-items: center;
+}
+
+.vf-swatch__color {
+    width: var(--vf-space-12);
+    height: var(--vf-space-12);
+    border: var(--vf-border-width) solid var(--vf-color-border-strong);
+    border-radius: var(--vf-radius-md);
+}
+
+.vf-swatch__color--primary {
+    background: var(--vf-color-primary);
+}
+
+.vf-swatch__color--text {
+    background: var(--vf-color-text);
+}
+
+.vf-swatch__color--muted {
+    background: var(--vf-color-muted);
+}
+
+.vf-swatch__color--background {
+    background: var(--vf-color-background);
+}
+
+.vf-swatch__color--surface {
+    background: var(--vf-color-surface);
+}
+
+.vf-swatch__color--border {
+    background: var(--vf-color-border);
+}
+
+.vf-swatch__color--success {
+    background: var(--vf-color-success);
+}
+
+.vf-swatch__color--warning {
+    background: var(--vf-color-warning);
+}
+
+.vf-swatch__color--danger {
+    background: var(--vf-color-danger);
+}
+
+.vf-spacing-scale {
+    display: grid;
+    gap: var(--vf-space-3);
+}
+
+.vf-spacing-scale__item {
+    display: grid;
+    grid-template-columns: var(--vf-space-12) 1fr;
+    gap: var(--vf-space-3);
+    align-items: center;
+}
+
+.vf-spacing-scale__bar {
+    min-width: var(--vf-space-1);
+    height: var(--vf-space-4);
+    border-radius: var(--vf-radius-sm);
+    background: var(--vf-color-primary);
+}
+
+.vf-spacing-scale__bar--1 { width: var(--vf-space-1); }
+.vf-spacing-scale__bar--2 { width: var(--vf-space-2); }
+.vf-spacing-scale__bar--3 { width: var(--vf-space-3); }
+.vf-spacing-scale__bar--4 { width: var(--vf-space-4); }
+.vf-spacing-scale__bar--6 { width: var(--vf-space-6); }
+.vf-spacing-scale__bar--8 { width: var(--vf-space-8); }
+.vf-spacing-scale__bar--12 { width: var(--vf-space-12); }
+.vf-spacing-scale__bar--16 { width: var(--vf-space-16); }
+.vf-spacing-scale__bar--20 { width: var(--vf-space-20); }
+.vf-spacing-scale__bar--24 { width: var(--vf-space-24); }
diff --git a/site/assets/styles/website/sections/index.css b/site/assets/styles/website/sections/index.css
new file mode 100644
index 0000000..c82e178
--- /dev/null
+++ b/site/assets/styles/website/sections/index.css
@@ -0,0 +1,43 @@
+.vf-section {
+    padding-block: var(--vf-section-space);
+}
+
+.vf-section--surface {
+    background: var(--vf-color-surface);
+}
+
+.vf-section--muted {
+    background: var(--vf-color-background);
+}
+
+.vf-section__heading {
+    max-width: var(--vf-content-max);
+    margin-bottom: var(--vf-space-8);
+}
+
+.vf-section__heading p:last-child {
+    margin-bottom: 0;
+}
+
+.vf-hero__content {
+    max-width: var(--vf-content-max);
+}
+
+.vf-hero__actions {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-3);
+    margin-top: var(--vf-space-8);
+}
+
+.vf-card-grid {
+    --bs-gutter-x: var(--vf-grid-gap);
+    --bs-gutter-y: var(--vf-grid-gap);
+}
+
+@media (max-width: 575.98px) {
+    .vf-hero__actions .btn,
+    .vf-cta__inner .btn {
+        width: 100%;
+    }
+}
diff --git a/site/assets/styles/website/tokens.css b/site/assets/styles/website/tokens.css
new file mode 100644
index 0000000..71eda4e
--- /dev/null
+++ b/site/assets/styles/website/tokens.css
@@ -0,0 +1,118 @@
+:root {
+    color-scheme: light;
+
+    --vf-color-primary: #b00020;
+    --vf-color-primary-hover: #8a0019;
+    --vf-color-primary-soft: #f7e6e9;
+    /* Bootstrap требует отдельный RGB triplet; держать в синхроне с primary. */
+    --vf-color-primary-rgb: 176, 0, 32;
+    --vf-color-text: #0b1020;
+    --vf-color-muted: #5c677d;
+    --vf-color-background: #f5f6f8;
+    --vf-color-surface: #ffffff;
+    --vf-color-border: #d9dce4;
+    --vf-color-border-strong: #767b85;
+    --vf-color-success: #146c43;
+    --vf-color-success-soft: #e9f5ee;
+    --vf-color-warning: #805b00;
+    --vf-color-warning-soft: #fff3cd;
+    --vf-color-danger: #b02a37;
+    --vf-color-danger-soft: #f8d7da;
+    --vf-color-on-primary: #ffffff;
+    --vf-color-focus: #b00020;
+
+    --vf-font-primary: "TT Norms Pro", "TT Norms", Arial, sans-serif;
+    --vf-font-weight-regular: 400;
+    --vf-font-weight-medium: 500;
+    --vf-font-weight-bold: 700;
+    --vf-font-size-h1: 40px;
+    --vf-line-height-h1: 44px;
+    --vf-font-size-h2: 32px;
+    --vf-line-height-h2: 36px;
+    --vf-font-size-h3: 24px;
+    --vf-line-height-h3: 30px;
+    --vf-font-size-h4: 20px;
+    --vf-line-height-h4: 26px;
+    --vf-font-size-lead: 18px;
+    --vf-line-height-lead: 28px;
+    --vf-font-size-body: 16px;
+    --vf-line-height-body: 24px;
+    --vf-font-size-small: 14px;
+    --vf-line-height-small: 20px;
+    --vf-font-size-caption: 12px;
+    --vf-line-height-caption: 16px;
+    --vf-letter-spacing-caption: 0.04em;
+
+    --vf-space-1: 4px;
+    --vf-space-2: 8px;
+    --vf-space-3: 12px;
+    --vf-space-4: 16px;
+    --vf-space-6: 24px;
+    --vf-space-8: 32px;
+    --vf-space-12: 48px;
+    --vf-space-16: 64px;
+    --vf-space-20: 80px;
+    --vf-space-24: 96px;
+
+    --vf-container-max: 1200px;
+    --vf-content-max: 720px;
+    --vf-container-gutter: var(--vf-space-4);
+    --vf-section-space: var(--vf-space-12);
+    --vf-card-padding: var(--vf-space-4);
+    --vf-grid-gap: var(--vf-space-6);
+    --vf-form-gap: var(--vf-space-4);
+    --vf-control-min-height: 44px;
+    --vf-showcase-item-min: 240px;
+
+    --vf-radius-sm: 4px;
+    --vf-radius-md: 8px;
+    --vf-radius-lg: 16px;
+    --vf-border-width: 1px;
+    --vf-icon-stroke-width: 2px;
+    --vf-focus-width: 2px;
+    --vf-focus-offset: 2px;
+    /* Shadow использует RGB представление vf-color-text. */
+    --vf-shadow-card: 0 8px 24px rgb(11 16 32 / 8%);
+    --vf-disabled-opacity: 55%;
+    --vf-transition-duration: 150ms;
+
+    --vf-breakpoint-sm: 576px;
+    --vf-breakpoint-md: 768px;
+    --vf-breakpoint-lg: 992px;
+    --vf-breakpoint-xl: 1200px;
+    --vf-breakpoint-xxl: 1400px;
+
+    --bs-body-font-family: var(--vf-font-primary);
+    --bs-body-font-size: var(--vf-font-size-body);
+    --bs-body-line-height: var(--vf-line-height-body);
+    --bs-body-color: var(--vf-color-text);
+    --bs-body-bg: var(--vf-color-background);
+    --bs-primary: var(--vf-color-primary);
+    --bs-primary-rgb: var(--vf-color-primary-rgb);
+    --bs-border-color: var(--vf-color-border);
+    --bs-border-radius: var(--vf-radius-md);
+}
+
+@media (min-width: 768px) {
+    :root {
+        --vf-font-size-h1: 56px;
+        --vf-line-height-h1: 60px;
+        --vf-font-size-h2: 40px;
+        --vf-line-height-h2: 44px;
+        --vf-font-size-h3: 32px;
+        --vf-line-height-h3: 36px;
+        --vf-font-size-h4: 24px;
+        --vf-line-height-h4: 30px;
+        --vf-font-size-lead: 20px;
+        --vf-line-height-lead: 30px;
+        --vf-container-gutter: var(--vf-space-6);
+        --vf-section-space: var(--vf-space-16);
+        --vf-card-padding: var(--vf-space-6);
+    }
+}
+
+@media (min-width: 992px) {
+    :root {
+        --vf-section-space: var(--vf-space-20);
+    }
+}
diff --git a/site/public/assets/website/base.css b/site/public/assets/website/base.css
new file mode 100644
index 0000000..aeac8e4
--- /dev/null
+++ b/site/public/assets/website/base.css
@@ -0,0 +1,144 @@
+*,
+*::before,
+*::after {
+    box-sizing: border-box;
+}
+
+html {
+    scroll-behavior: smooth;
+}
+
+body {
+    margin: 0;
+    background: var(--vf-color-background);
+    color: var(--vf-color-text);
+    font-family: var(--vf-font-primary);
+    font-size: var(--vf-font-size-body);
+    font-weight: var(--vf-font-weight-regular);
+    line-height: var(--vf-line-height-body);
+    overflow-wrap: break-word;
+}
+
+a {
+    color: var(--vf-color-primary);
+    text-underline-offset: var(--vf-space-1);
+}
+
+a:hover {
+    color: var(--vf-color-primary-hover);
+}
+
+code {
+    color: var(--vf-color-primary);
+}
+
+:focus-visible {
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+}
+
+h1,
+.vf-h1,
+h2,
+.vf-h2,
+h3,
+.vf-h3,
+h4,
+.vf-h4 {
+    margin-block: 0 var(--vf-space-4);
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+h1,
+.vf-h1 {
+    font-size: var(--vf-font-size-h1);
+    line-height: var(--vf-line-height-h1);
+}
+
+h2,
+.vf-h2 {
+    font-size: var(--vf-font-size-h2);
+    line-height: var(--vf-line-height-h2);
+}
+
+h3,
+.vf-h3 {
+    font-size: var(--vf-font-size-h3);
+    line-height: var(--vf-line-height-h3);
+}
+
+h4,
+.vf-h4 {
+    font-size: var(--vf-font-size-h4);
+    line-height: var(--vf-line-height-h4);
+}
+
+p {
+    margin-block: 0 var(--vf-space-4);
+}
+
+.vf-lead {
+    font-size: var(--vf-font-size-lead);
+    line-height: var(--vf-line-height-lead);
+}
+
+.vf-small {
+    font-size: var(--vf-font-size-small);
+    line-height: var(--vf-line-height-small);
+}
+
+.vf-caption {
+    font-size: var(--vf-font-size-caption);
+    font-weight: var(--vf-font-weight-medium);
+    line-height: var(--vf-line-height-caption);
+    letter-spacing: var(--vf-letter-spacing-caption);
+    text-transform: uppercase;
+}
+
+.vf-font-weight-regular {
+    font-weight: var(--vf-font-weight-regular);
+}
+
+.vf-font-weight-medium {
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.vf-font-weight-bold {
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-text-muted {
+    color: var(--vf-color-muted);
+}
+
+.vf-content-width {
+    max-width: var(--vf-content-max);
+}
+
+.container {
+    width: 100%;
+    max-width: var(--vf-container-max);
+    padding-inline: var(--vf-container-gutter);
+}
+
+.vf-skip-link {
+    position: fixed;
+    z-index: 1100;
+    top: var(--vf-space-2);
+    left: var(--vf-space-2);
+    padding: var(--vf-space-2) var(--vf-space-4);
+    border-radius: var(--vf-radius-sm);
+    background: var(--vf-color-surface);
+    transform: translateY(-200%);
+}
+
+.vf-skip-link:focus {
+    transform: translateY(0);
+}
+
+@media (prefers-reduced-motion: reduce) {
+    html {
+        scroll-behavior: auto;
+    }
+}
diff --git a/site/public/assets/website/components/index.css b/site/public/assets/website/components/index.css
new file mode 100644
index 0000000..08845d8
--- /dev/null
+++ b/site/public/assets/website/components/index.css
@@ -0,0 +1,410 @@
+.btn {
+    --bs-btn-font-weight: var(--vf-font-weight-medium);
+
+    transition-duration: var(--vf-transition-duration);
+}
+
+.btn:not(.btn-sm):not(.btn-lg) {
+    --bs-btn-padding-y: var(--vf-space-2);
+    --bs-btn-padding-x: var(--vf-space-4);
+    --bs-btn-border-radius: var(--vf-radius-md);
+
+    min-height: var(--vf-control-min-height);
+}
+
+.btn-primary {
+    --bs-btn-bg: var(--vf-color-primary);
+    --bs-btn-border-color: var(--vf-color-primary);
+    --bs-btn-hover-bg: var(--vf-color-primary-hover);
+    --bs-btn-hover-border-color: var(--vf-color-primary-hover);
+    --bs-btn-active-bg: var(--vf-color-primary-hover);
+    --bs-btn-active-border-color: var(--vf-color-primary-hover);
+    --bs-btn-disabled-bg: var(--vf-color-primary);
+    --bs-btn-disabled-border-color: var(--vf-color-primary);
+    --bs-btn-focus-shadow-rgb: var(--vf-color-primary-rgb);
+}
+
+.btn-outline-primary {
+    --bs-btn-color: var(--vf-color-primary);
+    --bs-btn-border-color: var(--vf-color-primary);
+    --bs-btn-hover-color: var(--vf-color-on-primary);
+    --bs-btn-hover-bg: var(--vf-color-primary);
+    --bs-btn-hover-border-color: var(--vf-color-primary);
+    --bs-btn-active-color: var(--vf-color-on-primary);
+    --bs-btn-active-bg: var(--vf-color-primary-hover);
+    --bs-btn-active-border-color: var(--vf-color-primary-hover);
+    --bs-btn-disabled-color: var(--vf-color-primary);
+    --bs-btn-disabled-border-color: var(--vf-color-primary);
+    --bs-btn-focus-shadow-rgb: var(--vf-color-primary-rgb);
+}
+
+.vf-btn-on-primary {
+    --bs-btn-color: var(--vf-color-primary);
+    --bs-btn-bg: var(--vf-color-on-primary);
+    --bs-btn-border-color: var(--vf-color-on-primary);
+    --bs-btn-hover-color: var(--vf-color-primary-hover);
+    --bs-btn-hover-bg: var(--vf-color-primary-soft);
+    --bs-btn-hover-border-color: var(--vf-color-on-primary);
+    --bs-btn-active-color: var(--vf-color-primary-hover);
+    --bs-btn-active-bg: var(--vf-color-primary-soft);
+    --bs-btn-active-border-color: var(--vf-color-on-primary);
+    --bs-btn-disabled-color: var(--vf-color-primary);
+    --bs-btn-disabled-bg: var(--vf-color-on-primary);
+    --bs-btn-disabled-border-color: var(--vf-color-on-primary);
+}
+
+.btn-primary:hover,
+.btn-primary.vf-is-hover {
+    background: var(--vf-color-primary-hover);
+    border-color: var(--vf-color-primary-hover);
+    color: var(--vf-color-on-primary);
+}
+
+.btn-outline-primary:hover,
+.btn-outline-primary.vf-is-hover {
+    background: var(--vf-color-primary);
+    border-color: var(--vf-color-primary);
+    color: var(--vf-color-on-primary);
+}
+
+.vf-btn-on-primary:hover,
+.vf-btn-on-primary.vf-is-hover {
+    background: var(--vf-color-primary-soft);
+    border-color: var(--vf-color-on-primary);
+    color: var(--vf-color-primary-hover);
+}
+
+.btn:focus {
+    box-shadow: none;
+}
+
+.btn:focus-visible,
+.btn.vf-is-focus {
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+    box-shadow: none;
+}
+
+.btn:disabled,
+.btn.disabled {
+    opacity: var(--vf-disabled-opacity);
+}
+
+.vf-card {
+    height: 100%;
+    border: var(--vf-border-width) solid var(--vf-color-border);
+    border-radius: var(--vf-radius-lg);
+    background: var(--vf-color-surface);
+    box-shadow: var(--vf-shadow-card);
+}
+
+.vf-card .card-body {
+    padding: var(--vf-card-padding);
+}
+
+.vf-card .card-title {
+    margin-bottom: var(--vf-space-4);
+}
+
+.vf-card .card-text:last-child {
+    margin-bottom: 0;
+}
+
+.vf-badge {
+    padding: var(--vf-space-1) var(--vf-space-2);
+    border: var(--vf-border-width) solid currentcolor;
+    border-radius: var(--vf-radius-sm);
+    background: var(--vf-color-surface);
+    color: var(--vf-color-text);
+    font-size: var(--vf-font-size-caption);
+    font-weight: var(--vf-font-weight-medium);
+    line-height: var(--vf-line-height-caption);
+}
+
+.vf-badge--success {
+    background: var(--vf-color-success-soft);
+    color: var(--vf-color-success);
+}
+
+.vf-badge--warning {
+    background: var(--vf-color-warning-soft);
+    color: var(--vf-color-warning);
+}
+
+.vf-badge--danger {
+    background: var(--vf-color-danger-soft);
+    color: var(--vf-color-danger);
+}
+
+.vf-alert {
+    --bs-alert-padding-x: var(--vf-space-4);
+    --bs-alert-padding-y: var(--vf-space-4);
+    --bs-alert-color: var(--vf-color-text);
+    --bs-alert-bg: var(--vf-color-surface);
+    --bs-alert-border-color: var(--vf-color-border);
+    --bs-alert-border-radius: var(--vf-radius-md);
+
+    margin-bottom: 0;
+}
+
+.vf-alert--success {
+    --bs-alert-bg: var(--vf-color-success-soft);
+    --bs-alert-border-color: var(--vf-color-success);
+}
+
+.vf-alert--warning {
+    --bs-alert-bg: var(--vf-color-warning-soft);
+    --bs-alert-border-color: var(--vf-color-warning);
+}
+
+.vf-alert--danger {
+    --bs-alert-bg: var(--vf-color-danger-soft);
+    --bs-alert-border-color: var(--vf-color-danger);
+}
+
+.vf-form {
+    display: grid;
+    gap: var(--vf-form-gap);
+}
+
+.form-label,
+.form-check-label {
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.form-control,
+.form-select {
+    border-color: var(--vf-color-border-strong);
+    color: var(--vf-color-text);
+}
+
+.form-select {
+    appearance: auto;
+    padding-inline-end: var(--vf-space-8);
+    background-image: none;
+}
+
+.form-control:not(.form-control-sm):not(.form-control-lg),
+.form-select:not(.form-select-sm):not(.form-select-lg) {
+    min-height: var(--vf-control-min-height);
+    border-radius: var(--vf-radius-md);
+}
+
+textarea.form-control:not(.form-control-sm):not(.form-control-lg) {
+    min-height: calc(var(--vf-control-min-height) * 3);
+}
+
+.form-control:focus,
+.form-select:focus,
+.form-check-input:focus {
+    border-color: var(--vf-color-focus);
+    box-shadow: none;
+}
+
+.form-control:focus-visible,
+.form-select:focus-visible,
+.form-check-input:focus-visible,
+.form-control.vf-is-focus,
+.form-select.vf-is-focus,
+.form-check-input.vf-is-focus {
+    border-color: var(--vf-color-focus);
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+    box-shadow: none;
+}
+
+.form-control.is-valid,
+.form-select.is-valid,
+.form-check-input.is-valid {
+    border-color: var(--vf-color-success);
+}
+
+.form-control.is-valid,
+.form-control.is-invalid,
+.form-select.is-valid,
+.form-select.is-invalid {
+    background-image: none;
+}
+
+.form-control.is-invalid,
+.form-select.is-invalid,
+.form-check-input.is-invalid {
+    border-color: var(--vf-color-danger);
+}
+
+.form-check-input.is-valid ~ .form-check-label {
+    color: var(--vf-color-success);
+}
+
+.form-check-input.is-invalid ~ .form-check-label {
+    color: var(--vf-color-danger);
+}
+
+.valid-feedback {
+    color: var(--vf-color-success);
+}
+
+.invalid-feedback {
+    color: var(--vf-color-danger);
+}
+
+.form-check-input:checked {
+    border-color: var(--vf-color-primary);
+    background-color: var(--vf-color-primary);
+}
+
+.form-check-input.is-valid:checked {
+    border-color: var(--vf-color-success);
+    background-color: var(--vf-color-success);
+}
+
+.form-check-input.is-invalid:checked {
+    border-color: var(--vf-color-danger);
+    background-color: var(--vf-color-danger);
+}
+
+.accordion {
+    --bs-accordion-border-color: var(--vf-color-border);
+    --bs-accordion-border-radius: var(--vf-radius-md);
+    --bs-accordion-inner-border-radius: var(--vf-radius-md);
+    --bs-accordion-color: var(--vf-color-text);
+    --bs-accordion-bg: var(--vf-color-surface);
+    --bs-accordion-btn-color: var(--vf-color-text);
+    --bs-accordion-btn-bg: var(--vf-color-surface);
+    --bs-accordion-active-color: var(--vf-color-text);
+    --bs-accordion-active-bg: var(--vf-color-primary-soft);
+    --bs-accordion-btn-focus-border-color: var(--vf-color-focus);
+    --bs-accordion-btn-focus-box-shadow: none;
+    --bs-accordion-btn-icon: none;
+    --bs-accordion-btn-active-icon: none;
+}
+
+.accordion-button {
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.accordion-button::after {
+    width: var(--vf-space-3);
+    height: var(--vf-space-3);
+    border-inline-end: var(--vf-icon-stroke-width) solid currentcolor;
+    border-block-end: var(--vf-icon-stroke-width) solid currentcolor;
+    background-image: none;
+    transform: rotate(45deg);
+}
+
+.accordion-button:not(.collapsed)::after {
+    background-image: none;
+    transform: rotate(-135deg);
+}
+
+.accordion-button:focus,
+.vf-navbar .navbar-toggler:focus {
+    box-shadow: none;
+}
+
+.accordion-button:focus-visible,
+.vf-navbar .navbar-toggler:focus-visible {
+    outline: var(--vf-focus-width) solid var(--vf-color-focus);
+    outline-offset: var(--vf-focus-offset);
+    box-shadow: none;
+}
+
+.breadcrumb {
+    --bs-breadcrumb-divider-color: var(--vf-color-muted);
+    --bs-breadcrumb-item-active-color: var(--vf-color-muted);
+    margin-bottom: 0;
+}
+
+.vf-navbar {
+    border-bottom: var(--vf-border-width) solid var(--vf-color-border);
+    background: var(--vf-color-surface);
+}
+
+.vf-navbar .navbar-brand {
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-navbar .nav-link {
+    color: var(--vf-color-text);
+    font-weight: var(--vf-font-weight-medium);
+}
+
+.vf-navbar .nav-link:hover,
+.vf-navbar .nav-link:focus,
+.vf-navbar .nav-link.active {
+    color: var(--vf-color-primary);
+}
+
+.vf-navbar .navbar-toggler {
+    border-color: var(--vf-color-border-strong);
+    color: var(--vf-color-text);
+}
+
+.vf-navbar-toggler-icon {
+    display: grid;
+    gap: var(--vf-space-1);
+}
+
+.vf-navbar-toggler-icon span {
+    width: var(--vf-space-6);
+    border-top: var(--vf-icon-stroke-width) solid currentcolor;
+}
+
+.vf-footer {
+    padding-block: var(--vf-space-8);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+    background: var(--vf-color-surface);
+}
+
+.vf-footer__inner {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-4) var(--vf-space-6);
+    align-items: center;
+    justify-content: space-between;
+}
+
+.vf-footer p {
+    margin: 0;
+}
+
+.vf-cta {
+    padding: var(--vf-card-padding);
+    border-radius: var(--vf-radius-lg);
+    background: var(--vf-color-primary);
+    color: var(--vf-color-on-primary);
+}
+
+.vf-cta h2,
+.vf-cta h3 {
+    color: var(--vf-color-on-primary);
+}
+
+.vf-cta__inner {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-6);
+    align-items: center;
+    justify-content: space-between;
+}
+
+.vf-cta__copy {
+    max-width: var(--vf-content-max);
+}
+
+.vf-cta__copy p:last-child {
+    margin-bottom: 0;
+}
+
+.vf-cta :focus-visible,
+.vf-cta .vf-is-focus {
+    outline-color: var(--vf-color-on-primary);
+}
+
+@media (prefers-reduced-motion: reduce) {
+    .btn {
+        transition-duration: 0s;
+    }
+}
diff --git a/site/public/assets/website/components/showcase.css b/site/public/assets/website/components/showcase.css
new file mode 100644
index 0000000..3facb02
--- /dev/null
+++ b/site/public/assets/website/components/showcase.css
@@ -0,0 +1,111 @@
+.vf-showcase {
+    display: grid;
+    gap: var(--vf-space-8);
+}
+
+.vf-showcase-group {
+    padding-block: var(--vf-space-6);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+}
+
+.vf-showcase-group > :last-child {
+    margin-bottom: 0;
+}
+
+.vf-component-row {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-3);
+    align-items: center;
+}
+
+.vf-component-state {
+    display: grid;
+    gap: var(--vf-space-2);
+}
+
+.vf-swatch-grid {
+    display: grid;
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-showcase-item-min)), 1fr));
+    gap: var(--vf-grid-gap);
+}
+
+.vf-swatch {
+    display: grid;
+    grid-template-columns: var(--vf-space-12) 1fr;
+    gap: var(--vf-space-3);
+    align-items: center;
+}
+
+.vf-swatch__color {
+    width: var(--vf-space-12);
+    height: var(--vf-space-12);
+    border: var(--vf-border-width) solid var(--vf-color-border-strong);
+    border-radius: var(--vf-radius-md);
+}
+
+.vf-swatch__color--primary {
+    background: var(--vf-color-primary);
+}
+
+.vf-swatch__color--text {
+    background: var(--vf-color-text);
+}
+
+.vf-swatch__color--muted {
+    background: var(--vf-color-muted);
+}
+
+.vf-swatch__color--background {
+    background: var(--vf-color-background);
+}
+
+.vf-swatch__color--surface {
+    background: var(--vf-color-surface);
+}
+
+.vf-swatch__color--border {
+    background: var(--vf-color-border);
+}
+
+.vf-swatch__color--success {
+    background: var(--vf-color-success);
+}
+
+.vf-swatch__color--warning {
+    background: var(--vf-color-warning);
+}
+
+.vf-swatch__color--danger {
+    background: var(--vf-color-danger);
+}
+
+.vf-spacing-scale {
+    display: grid;
+    gap: var(--vf-space-3);
+}
+
+.vf-spacing-scale__item {
+    display: grid;
+    grid-template-columns: var(--vf-space-12) 1fr;
+    gap: var(--vf-space-3);
+    align-items: center;
+}
+
+.vf-spacing-scale__bar {
+    min-width: var(--vf-space-1);
+    height: var(--vf-space-4);
+    border-radius: var(--vf-radius-sm);
+    background: var(--vf-color-primary);
+}
+
+.vf-spacing-scale__bar--1 { width: var(--vf-space-1); }
+.vf-spacing-scale__bar--2 { width: var(--vf-space-2); }
+.vf-spacing-scale__bar--3 { width: var(--vf-space-3); }
+.vf-spacing-scale__bar--4 { width: var(--vf-space-4); }
+.vf-spacing-scale__bar--6 { width: var(--vf-space-6); }
+.vf-spacing-scale__bar--8 { width: var(--vf-space-8); }
+.vf-spacing-scale__bar--12 { width: var(--vf-space-12); }
+.vf-spacing-scale__bar--16 { width: var(--vf-space-16); }
+.vf-spacing-scale__bar--20 { width: var(--vf-space-20); }
+.vf-spacing-scale__bar--24 { width: var(--vf-space-24); }
diff --git a/site/public/assets/website/sections/index.css b/site/public/assets/website/sections/index.css
new file mode 100644
index 0000000..c82e178
--- /dev/null
+++ b/site/public/assets/website/sections/index.css
@@ -0,0 +1,43 @@
+.vf-section {
+    padding-block: var(--vf-section-space);
+}
+
+.vf-section--surface {
+    background: var(--vf-color-surface);
+}
+
+.vf-section--muted {
+    background: var(--vf-color-background);
+}
+
+.vf-section__heading {
+    max-width: var(--vf-content-max);
+    margin-bottom: var(--vf-space-8);
+}
+
+.vf-section__heading p:last-child {
+    margin-bottom: 0;
+}
+
+.vf-hero__content {
+    max-width: var(--vf-content-max);
+}
+
+.vf-hero__actions {
+    display: flex;
+    flex-wrap: wrap;
+    gap: var(--vf-space-3);
+    margin-top: var(--vf-space-8);
+}
+
+.vf-card-grid {
+    --bs-gutter-x: var(--vf-grid-gap);
+    --bs-gutter-y: var(--vf-grid-gap);
+}
+
+@media (max-width: 575.98px) {
+    .vf-hero__actions .btn,
+    .vf-cta__inner .btn {
+        width: 100%;
+    }
+}
diff --git a/site/public/assets/website/tokens.css b/site/public/assets/website/tokens.css
new file mode 100644
index 0000000..71eda4e
--- /dev/null
+++ b/site/public/assets/website/tokens.css
@@ -0,0 +1,118 @@
+:root {
+    color-scheme: light;
+
+    --vf-color-primary: #b00020;
+    --vf-color-primary-hover: #8a0019;
+    --vf-color-primary-soft: #f7e6e9;
+    /* Bootstrap требует отдельный RGB triplet; держать в синхроне с primary. */
+    --vf-color-primary-rgb: 176, 0, 32;
+    --vf-color-text: #0b1020;
+    --vf-color-muted: #5c677d;
+    --vf-color-background: #f5f6f8;
+    --vf-color-surface: #ffffff;
+    --vf-color-border: #d9dce4;
+    --vf-color-border-strong: #767b85;
+    --vf-color-success: #146c43;
+    --vf-color-success-soft: #e9f5ee;
+    --vf-color-warning: #805b00;
+    --vf-color-warning-soft: #fff3cd;
+    --vf-color-danger: #b02a37;
+    --vf-color-danger-soft: #f8d7da;
+    --vf-color-on-primary: #ffffff;
+    --vf-color-focus: #b00020;
+
+    --vf-font-primary: "TT Norms Pro", "TT Norms", Arial, sans-serif;
+    --vf-font-weight-regular: 400;
+    --vf-font-weight-medium: 500;
+    --vf-font-weight-bold: 700;
+    --vf-font-size-h1: 40px;
+    --vf-line-height-h1: 44px;
+    --vf-font-size-h2: 32px;
+    --vf-line-height-h2: 36px;
+    --vf-font-size-h3: 24px;
+    --vf-line-height-h3: 30px;
+    --vf-font-size-h4: 20px;
+    --vf-line-height-h4: 26px;
+    --vf-font-size-lead: 18px;
+    --vf-line-height-lead: 28px;
+    --vf-font-size-body: 16px;
+    --vf-line-height-body: 24px;
+    --vf-font-size-small: 14px;
+    --vf-line-height-small: 20px;
+    --vf-font-size-caption: 12px;
+    --vf-line-height-caption: 16px;
+    --vf-letter-spacing-caption: 0.04em;
+
+    --vf-space-1: 4px;
+    --vf-space-2: 8px;
+    --vf-space-3: 12px;
+    --vf-space-4: 16px;
+    --vf-space-6: 24px;
+    --vf-space-8: 32px;
+    --vf-space-12: 48px;
+    --vf-space-16: 64px;
+    --vf-space-20: 80px;
+    --vf-space-24: 96px;
+
+    --vf-container-max: 1200px;
+    --vf-content-max: 720px;
+    --vf-container-gutter: var(--vf-space-4);
+    --vf-section-space: var(--vf-space-12);
+    --vf-card-padding: var(--vf-space-4);
+    --vf-grid-gap: var(--vf-space-6);
+    --vf-form-gap: var(--vf-space-4);
+    --vf-control-min-height: 44px;
+    --vf-showcase-item-min: 240px;
+
+    --vf-radius-sm: 4px;
+    --vf-radius-md: 8px;
+    --vf-radius-lg: 16px;
+    --vf-border-width: 1px;
+    --vf-icon-stroke-width: 2px;
+    --vf-focus-width: 2px;
+    --vf-focus-offset: 2px;
+    /* Shadow использует RGB представление vf-color-text. */
+    --vf-shadow-card: 0 8px 24px rgb(11 16 32 / 8%);
+    --vf-disabled-opacity: 55%;
+    --vf-transition-duration: 150ms;
+
+    --vf-breakpoint-sm: 576px;
+    --vf-breakpoint-md: 768px;
+    --vf-breakpoint-lg: 992px;
+    --vf-breakpoint-xl: 1200px;
+    --vf-breakpoint-xxl: 1400px;
+
+    --bs-body-font-family: var(--vf-font-primary);
+    --bs-body-font-size: var(--vf-font-size-body);
+    --bs-body-line-height: var(--vf-line-height-body);
+    --bs-body-color: var(--vf-color-text);
+    --bs-body-bg: var(--vf-color-background);
+    --bs-primary: var(--vf-color-primary);
+    --bs-primary-rgb: var(--vf-color-primary-rgb);
+    --bs-border-color: var(--vf-color-border);
+    --bs-border-radius: var(--vf-radius-md);
+}
+
+@media (min-width: 768px) {
+    :root {
+        --vf-font-size-h1: 56px;
+        --vf-line-height-h1: 60px;
+        --vf-font-size-h2: 40px;
+        --vf-line-height-h2: 44px;
+        --vf-font-size-h3: 32px;
+        --vf-line-height-h3: 36px;
+        --vf-font-size-h4: 24px;
+        --vf-line-height-h4: 30px;
+        --vf-font-size-lead: 20px;
+        --vf-line-height-lead: 30px;
+        --vf-container-gutter: var(--vf-space-6);
+        --vf-section-space: var(--vf-space-16);
+        --vf-card-padding: var(--vf-space-6);
+    }
+}
+
+@media (min-width: 992px) {
+    :root {
+        --vf-section-space: var(--vf-space-20);
+    }
+}
diff --git a/site/templates/website/components/_accordion.html.twig b/site/templates/website/components/_accordion.html.twig
new file mode 100644
index 0000000..fdfda4b
--- /dev/null
+++ b/site/templates/website/components/_accordion.html.twig
@@ -0,0 +1,15 @@
+<div class="accordion" id="{{ accordion_id }}" data-vf-component="accordion">
+    {% for item in items %}
+        {% set item_id = accordion_id ~ '-item-' ~ loop.index %}
+        <div class="accordion-item">
+            <h3 class="accordion-header">
+                <button class="accordion-button{% if not loop.first %} collapsed{% endif %}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ item_id }}" aria-expanded="{{ loop.first ? 'true' : 'false' }}" aria-controls="{{ item_id }}">
+                    {{ item.title }}
+                </button>
+            </h3>
+            <div class="accordion-collapse collapse{% if loop.first %} show{% endif %}" id="{{ item_id }}" data-bs-parent="#{{ accordion_id }}">
+                <div class="accordion-body">{{ item.text }}</div>
+            </div>
+        </div>
+    {% endfor %}
+</div>
diff --git a/site/templates/website/components/_alert.html.twig b/site/templates/website/components/_alert.html.twig
new file mode 100644
index 0000000..c62af26
--- /dev/null
+++ b/site/templates/website/components/_alert.html.twig
@@ -0,0 +1,5 @@
+{% set tone = tone|default('default') %}
+<div class="alert vf-alert{% if tone != 'default' %} vf-alert--{{ tone }}{% endif %}"{% if role|default(null) %} role="{{ role }}"{% endif %} data-vf-component="alert">
+    <strong>{{ title }}</strong>
+    <div>{{ text }}</div>
+</div>
diff --git a/site/templates/website/components/_badge.html.twig b/site/templates/website/components/_badge.html.twig
new file mode 100644
index 0000000..c647e04
--- /dev/null
+++ b/site/templates/website/components/_badge.html.twig
@@ -0,0 +1,2 @@
+{% set tone = tone|default('default') %}
+<span class="badge vf-badge{% if tone != 'default' %} vf-badge--{{ tone }}{% endif %}" data-vf-component="badge">{{ label }}</span>
diff --git a/site/templates/website/components/_breadcrumb.html.twig b/site/templates/website/components/_breadcrumb.html.twig
new file mode 100644
index 0000000..63136bf
--- /dev/null
+++ b/site/templates/website/components/_breadcrumb.html.twig
@@ -0,0 +1,9 @@
+<nav aria-label="Хлебные крошки" data-vf-component="breadcrumb">
+    <ol class="breadcrumb">
+        {% for item in items %}
+            <li class="breadcrumb-item{% if loop.last %} active{% endif %}"{% if loop.last %} aria-current="page"{% endif %}>
+                {% if loop.last %}{{ item.label }}{% else %}<a href="{{ item.href }}">{{ item.label }}</a>{% endif %}
+            </li>
+        {% endfor %}
+    </ol>
+</nav>
diff --git a/site/templates/website/components/_button.html.twig b/site/templates/website/components/_button.html.twig
new file mode 100644
index 0000000..32a139f
--- /dev/null
+++ b/site/templates/website/components/_button.html.twig
@@ -0,0 +1,14 @@
+{% set variant = variant|default('primary') %}
+{% set state = state|default('default') %}
+{% set disabled = disabled|default(false) %}
+{% set classes = variant == 'on-primary' ? 'btn vf-btn-on-primary' : 'btn btn-' ~ variant %}
+{% if state == 'hover' %}{% set classes = classes ~ ' vf-is-hover' %}{% endif %}
+{% if state == 'focus' %}{% set classes = classes ~ ' vf-is-focus' %}{% endif %}
+{% if state == 'active' %}{% set classes = classes ~ ' active' %}{% endif %}
+{% if disabled %}{% set classes = classes ~ ' disabled' %}{% endif %}
+
+{% if href|default(null) %}
+    <a class="{{ classes }}"{% if not disabled %} href="{{ href }}"{% else %} aria-disabled="true" tabindex="-1"{% endif %} data-vf-component="button" data-vf-state="{{ state }}">{{ label }}</a>
+{% else %}
+    <button class="{{ classes }}" type="{{ type|default('button') }}"{% if disabled %} disabled{% endif %} data-vf-component="button" data-vf-state="{{ state }}">{{ label }}</button>
+{% endif %}
diff --git a/site/templates/website/components/_card.html.twig b/site/templates/website/components/_card.html.twig
new file mode 100644
index 0000000..f7eb6d8
--- /dev/null
+++ b/site/templates/website/components/_card.html.twig
@@ -0,0 +1,7 @@
+<article class="card vf-card" data-vf-component="card">
+    <div class="card-body">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h3 class="card-title vf-h4">{{ title }}</h3>
+        <p class="card-text vf-text-muted">{{ text }}</p>
+    </div>
+</article>
diff --git a/site/templates/website/components/_cta.html.twig b/site/templates/website/components/_cta.html.twig
new file mode 100644
index 0000000..c59b0e0
--- /dev/null
+++ b/site/templates/website/components/_cta.html.twig
@@ -0,0 +1,13 @@
+<div class="vf-cta" data-vf-component="cta">
+    <div class="vf-cta__inner">
+        <div class="vf-cta__copy">
+            <h2 id="{{ heading_id }}">{{ title }}</h2>
+            <p>{{ text }}</p>
+        </div>
+        {% include 'website/components/_button.html.twig' with {
+            label: action_label,
+            href: action_href,
+            variant: 'on-primary',
+        } only %}
+    </div>
+</div>
diff --git a/site/templates/website/components/_footer.html.twig b/site/templates/website/components/_footer.html.twig
new file mode 100644
index 0000000..03786a3
--- /dev/null
+++ b/site/templates/website/components/_footer.html.twig
@@ -0,0 +1,6 @@
+<footer class="vf-footer" data-vf-component="footer">
+    <div class="container vf-footer__inner">
+        <p><strong>{{ brand }}</strong> <span class="vf-text-muted">— финансовое управление на основе данных.</span></p>
+        <a href="{{ privacy_href }}">Политика конфиденциальности</a>
+    </div>
+</footer>
diff --git a/site/templates/website/components/_form_checkbox.html.twig b/site/templates/website/components/_form_checkbox.html.twig
new file mode 100644
index 0000000..a931f97
--- /dev/null
+++ b/site/templates/website/components/_form_checkbox.html.twig
@@ -0,0 +1,9 @@
+{% set state = state|default('default') %}
+{% set state_class = state == 'error' ? ' is-invalid' : (state == 'success' ? ' is-valid' : (state == 'focus' ? ' vf-is-focus' : '')) %}
+{% set feedback_id = id ~ '-feedback' %}
+<div class="form-check" data-vf-component="checkbox" data-vf-state="{{ state }}">
+    <input class="form-check-input{{ state_class }}" id="{{ id }}" name="{{ name|default(id) }}" type="checkbox"{% if checked|default(false) %} checked{% endif %}{% if required|default(false) %} required{% endif %}{% if disabled|default(false) %} disabled{% endif %}{% if state in ['error', 'success'] %} aria-describedby="{{ feedback_id }}"{% endif %}{% if state == 'error' %} aria-invalid="true"{% endif %}>
+    <label class="form-check-label" for="{{ id }}">{{ label }}</label>
+    {% if state == 'error' %}<div class="invalid-feedback" id="{{ feedback_id }}">{{ feedback|default('Подтвердите выбор.') }}</div>{% endif %}
+    {% if state == 'success' %}<div class="valid-feedback" id="{{ feedback_id }}">{{ feedback|default('Выбор подтверждён.') }}</div>{% endif %}
+</div>
diff --git a/site/templates/website/components/_form_input.html.twig b/site/templates/website/components/_form_input.html.twig
new file mode 100644
index 0000000..c23e3bd
--- /dev/null
+++ b/site/templates/website/components/_form_input.html.twig
@@ -0,0 +1,9 @@
+{% set state = state|default('default') %}
+{% set state_class = state == 'error' ? ' is-invalid' : (state == 'success' ? ' is-valid' : (state == 'focus' ? ' vf-is-focus' : '')) %}
+{% set feedback_id = id ~ '-feedback' %}
+<div data-vf-component="form-input" data-vf-state="{{ state }}">
+    <label class="form-label" for="{{ id }}">{{ label }}</label>
+    <input class="form-control{{ state_class }}" id="{{ id }}" name="{{ name|default(id) }}" type="{{ type|default('text') }}"{% if placeholder|default(null) %} placeholder="{{ placeholder }}"{% endif %}{% if value is defined %} value="{{ value }}"{% endif %}{% if required|default(false) %} required{% endif %}{% if disabled|default(false) %} disabled{% endif %}{% if state in ['error', 'success'] %} aria-describedby="{{ feedback_id }}"{% endif %}{% if state == 'error' %} aria-invalid="true"{% endif %}>
+    {% if state == 'error' %}<div class="invalid-feedback" id="{{ feedback_id }}">{{ feedback|default('Проверьте значение поля.') }}</div>{% endif %}
+    {% if state == 'success' %}<div class="valid-feedback" id="{{ feedback_id }}">{{ feedback|default('Значение принято.') }}</div>{% endif %}
+</div>
diff --git a/site/templates/website/components/_form_select.html.twig b/site/templates/website/components/_form_select.html.twig
new file mode 100644
index 0000000..93ac798
--- /dev/null
+++ b/site/templates/website/components/_form_select.html.twig
@@ -0,0 +1,13 @@
+{% set state = state|default('default') %}
+{% set state_class = state == 'error' ? ' is-invalid' : (state == 'success' ? ' is-valid' : (state == 'focus' ? ' vf-is-focus' : '')) %}
+{% set feedback_id = id ~ '-feedback' %}
+<div data-vf-component="select" data-vf-state="{{ state }}">
+    <label class="form-label" for="{{ id }}">{{ label }}</label>
+    <select class="form-select{{ state_class }}" id="{{ id }}" name="{{ name|default(id) }}"{% if required|default(false) %} required{% endif %}{% if disabled|default(false) %} disabled{% endif %}{% if state in ['error', 'success'] %} aria-describedby="{{ feedback_id }}"{% endif %}{% if state == 'error' %} aria-invalid="true"{% endif %}>
+        {% for option in options %}
+            <option value="{{ option.value }}"{% if option.selected|default(false) %} selected{% endif %}>{{ option.label }}</option>
+        {% endfor %}
+    </select>
+    {% if state == 'error' %}<div class="invalid-feedback" id="{{ feedback_id }}">{{ feedback|default('Выберите значение.') }}</div>{% endif %}
+    {% if state == 'success' %}<div class="valid-feedback" id="{{ feedback_id }}">{{ feedback|default('Значение принято.') }}</div>{% endif %}
+</div>
diff --git a/site/templates/website/components/_form_textarea.html.twig b/site/templates/website/components/_form_textarea.html.twig
new file mode 100644
index 0000000..41d685a
--- /dev/null
+++ b/site/templates/website/components/_form_textarea.html.twig
@@ -0,0 +1,9 @@
+{% set state = state|default('default') %}
+{% set state_class = state == 'error' ? ' is-invalid' : (state == 'success' ? ' is-valid' : (state == 'focus' ? ' vf-is-focus' : '')) %}
+{% set feedback_id = id ~ '-feedback' %}
+<div data-vf-component="textarea" data-vf-state="{{ state }}">
+    <label class="form-label" for="{{ id }}">{{ label }}</label>
+    <textarea class="form-control{{ state_class }}" id="{{ id }}" name="{{ name|default(id) }}"{% if placeholder|default(null) %} placeholder="{{ placeholder }}"{% endif %}{% if required|default(false) %} required{% endif %}{% if disabled|default(false) %} disabled{% endif %}{% if state in ['error', 'success'] %} aria-describedby="{{ feedback_id }}"{% endif %}{% if state == 'error' %} aria-invalid="true"{% endif %}>{{ value is defined ? value : '' }}</textarea>
+    {% if state == 'error' %}<div class="invalid-feedback" id="{{ feedback_id }}">{{ feedback|default('Добавьте описание.') }}</div>{% endif %}
+    {% if state == 'success' %}<div class="valid-feedback" id="{{ feedback_id }}">{{ feedback|default('Текст принят.') }}</div>{% endif %}
+</div>
diff --git a/site/templates/website/components/_navbar.html.twig b/site/templates/website/components/_navbar.html.twig
new file mode 100644
index 0000000..9f2d452
--- /dev/null
+++ b/site/templates/website/components/_navbar.html.twig
@@ -0,0 +1,18 @@
+{% set navigation_id = navigation_id|default('vf-main-navigation') %}
+<nav class="navbar navbar-expand-lg vf-navbar" aria-label="Основная навигация" data-vf-component="navbar">
+    <div class="container">
+        <a class="navbar-brand" href="{{ brand_href }}">{{ brand }}</a>
+        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#{{ navigation_id }}" aria-controls="{{ navigation_id }}" aria-expanded="false" aria-label="Открыть меню">
+            <span class="vf-navbar-toggler-icon" aria-hidden="true"><span></span><span></span><span></span></span>
+        </button>
+        <div class="collapse navbar-collapse" id="{{ navigation_id }}">
+            <ul class="navbar-nav ms-auto">
+                {% for item in items %}
+                    <li class="nav-item">
+                        <a class="nav-link{% if item.active|default(false) %} active{% endif %}" href="{{ item.href }}"{% if item.active|default(false) %} aria-current="page"{% endif %}>{{ item.label }}</a>
+                    </li>
+                {% endfor %}
+            </ul>
+        </div>
+    </div>
+</nav>
diff --git a/site/templates/website/layouts/base.html.twig b/site/templates/website/layouts/base.html.twig
new file mode 100644
index 0000000..11a1612
--- /dev/null
+++ b/site/templates/website/layouts/base.html.twig
@@ -0,0 +1,45 @@
+{% set vf_asset_version = '5e47f02a3f8d' %}
+<!doctype html>
+<html lang="ru">
+<head>
+    <meta charset="utf-8">
+    <meta name="viewport" content="width=device-width, initial-scale=1">
+    <title>{% block title %}Ваш Финдир{% endblock %}</title>
+    <meta name="description" content="{% block description %}Система компонентов публичного сайта «Ваш Финдир».{% endblock %}">
+
+    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
+    <link href="/assets/website/tokens.css?v={{ vf_asset_version }}" rel="stylesheet">
+    <link href="/assets/website/base.css?v={{ vf_asset_version }}" rel="stylesheet">
+    <link href="/assets/website/components/index.css?v={{ vf_asset_version }}" rel="stylesheet">
+    <link href="/assets/website/sections/index.css?v={{ vf_asset_version }}" rel="stylesheet">
+    {% block head %}{% endblock %}
+</head>
+<body>
+    <a class="vf-skip-link" href="#main-content">Перейти к содержанию</a>
+
+    {% block navigation %}
+        {% include 'website/components/_navbar.html.twig' with {
+            brand: 'Ваш Финдир',
+            brand_href: path('home'),
+            items: [
+                {label: 'Главная', href: path('home')},
+            ],
+        } only %}
+    {% endblock %}
+
+    <main id="main-content">
+        {% block body %}{% endblock %}
+    </main>
+
+    {% block footer %}
+        {% include 'website/components/_footer.html.twig' with {
+            brand: 'Ваш Финдир',
+            privacy_href: path('privacy'),
+        } only %}
+    {% endblock %}
+
+    {% block javascripts %}
+        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
+    {% endblock %}
+</body>
+</html>
diff --git a/site/templates/website/pages/ui_kit.html.twig b/site/templates/website/pages/ui_kit.html.twig
new file mode 100644
index 0000000..c5af979
--- /dev/null
+++ b/site/templates/website/pages/ui_kit.html.twig
@@ -0,0 +1,53 @@
+{% extends 'website/layouts/base.html.twig' %}
+
+{% block title %}UI-kit — Ваш Финдир{% endblock %}
+{% block description %}Design tokens, production components и sections публичного сайта «Ваш Финдир».{% endblock %}
+{% block head %}
+    <meta name="robots" content="noindex, nofollow">
+    <link href="/assets/website/components/showcase.css?v={{ vf_asset_version }}" rel="stylesheet">
+{% endblock %}
+
+{% block navigation %}
+    {% include 'website/components/_navbar.html.twig' with {
+        brand: 'Ваш Финдир',
+        brand_href: path('home'),
+        navigation_id: 'vf-ui-kit-navigation',
+        items: [
+            {label: 'Главная', href: path('home')},
+            {label: 'UI-kit', href: path('ui_kit'), active: true},
+        ],
+    } only %}
+{% endblock %}
+
+{% block body %}
+    {% include 'website/sections/_hero.html.twig' with {
+        id: 'ui-kit-hero',
+        eyebrow: 'Website foundation',
+        title: 'UI-kit «Ваш Финдир»',
+        text: 'Визуальный Source of Truth: typography, spacing, production components и reusable sections на Bootstrap 5.',
+        primary_action: {label: 'Смотреть компоненты', href: '#components'},
+        secondary_action: {label: 'Правила форм', href: '#forms'},
+    } only %}
+
+    {% include 'website/sections/_foundations_showcase.html.twig' only %}
+    {% include 'website/sections/_components_showcase.html.twig' only %}
+    {% include 'website/sections/_forms_showcase.html.twig' only %}
+
+    {% include 'website/sections/_content.html.twig' with {
+        id: 'content-section',
+        title: 'Content Section',
+        text: 'Секция использует общий container, spacing, typography и production Card component.',
+        cards: [
+            {title: 'Page собирает sections', text: 'Страница не копирует markup компонентов и секций.'},
+            {title: 'Section собирает components', text: 'Новый экран использует существующие patterns до создания новых.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_cta.html.twig' with {
+        id: 'cta-section',
+        title: 'CTA Section',
+        text: 'Общий CTA component использует те же container, spacing и Button rules.',
+        action_label: 'Вернуться к началу',
+        action_href: '#main-content',
+    } only %}
+{% endblock %}
diff --git a/site/templates/website/sections/_base.html.twig b/site/templates/website/sections/_base.html.twig
new file mode 100644
index 0000000..84563df
--- /dev/null
+++ b/site/templates/website/sections/_base.html.twig
@@ -0,0 +1,9 @@
+{% set section_class = section_class|default('') %}
+{% set section_id = section_id|default(null) %}
+{% set labelled_by = labelled_by|default(null) %}
+{% set section_name = section_name|default('base') %}
+<section class="vf-section {{ section_class }}"{% if section_id %} id="{{ section_id }}"{% endif %}{% if labelled_by %} aria-labelledby="{{ labelled_by }}"{% endif %} data-vf-section="{{ section_name }}">
+    <div class="container">
+        {% block section_content %}{% endblock %}
+    </div>
+</section>
diff --git a/site/templates/website/sections/_components_showcase.html.twig b/site/templates/website/sections/_components_showcase.html.twig
new file mode 100644
index 0000000..a0472c2
--- /dev/null
+++ b/site/templates/website/sections/_components_showcase.html.twig
@@ -0,0 +1,105 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = 'components' %}
+{% set labelled_by = 'components-title' %}
+{% set section_name = 'components-showcase' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        <h2 id="components-title">Components</h2>
+        <p class="vf-lead vf-text-muted">Состояния ниже рендерятся production partials. Hover и Focus принудительно показаны рядом с интерактивными состояниями.</p>
+    </div>
+
+    <div class="vf-showcase">
+        <div class="vf-showcase-group" id="buttons">
+            <h3>Buttons</h3>
+            <div class="vf-component-row">
+                {% for button in [
+                    {state: 'default', label: 'Default'},
+                    {state: 'hover', label: 'Hover'},
+                    {state: 'focus', label: 'Focus'},
+                    {state: 'active', label: 'Active'},
+                ] %}
+                    <div class="vf-component-state">
+                        <span class="vf-caption">{{ button.label }}</span>
+                        {% include 'website/components/_button.html.twig' with {label: button.label, state: button.state} only %}
+                    </div>
+                {% endfor %}
+                <div class="vf-component-state">
+                    <span class="vf-caption">Disabled</span>
+                    {% include 'website/components/_button.html.twig' with {label: 'Disabled', disabled: true} only %}
+                </div>
+                <div class="vf-component-state">
+                    <span class="vf-caption">Secondary</span>
+                    {% include 'website/components/_button.html.twig' with {label: 'Подробнее', variant: 'outline-primary'} only %}
+                </div>
+            </div>
+        </div>
+
+        <div class="vf-showcase-group" id="cards">
+            <h3>Cards</h3>
+            <div class="row vf-card-grid">
+                <div class="col-md-6">
+                    {% include 'website/components/_card.html.twig' with {
+                        eyebrow: 'Design tokens',
+                        title: 'Единые значения',
+                        text: 'Цвет, типографика и spacing не определяются на уровне страницы.',
+                    } only %}
+                </div>
+                <div class="col-md-6">
+                    {% include 'website/components/_card.html.twig' with {
+                        eyebrow: 'Accessibility',
+                        title: 'Наблюдаемые состояния',
+                        text: 'Фокус, ошибка и успех различимы и сопровождаются текстом.',
+                    } only %}
+                </div>
+            </div>
+        </div>
+
+        <div class="vf-showcase-group" id="badges">
+            <h3>Badges</h3>
+            <div class="vf-component-row">
+                {% include 'website/components/_badge.html.twig' with {label: 'Default'} only %}
+                {% include 'website/components/_badge.html.twig' with {label: 'Success', tone: 'success'} only %}
+                {% include 'website/components/_badge.html.twig' with {label: 'Warning', tone: 'warning'} only %}
+                {% include 'website/components/_badge.html.twig' with {label: 'Error', tone: 'danger'} only %}
+            </div>
+        </div>
+
+        <div class="vf-showcase-group" id="alerts">
+            <h3>Alerts</h3>
+            <div class="vf-showcase">
+                {% include 'website/components/_alert.html.twig' with {title: 'Информация', text: 'Нейтральное сообщение без отдельного цветового варианта.'} only %}
+                {% include 'website/components/_alert.html.twig' with {title: 'Готово', text: 'Операция завершена успешно.', tone: 'success'} only %}
+                {% include 'website/components/_alert.html.twig' with {title: 'Требуется внимание', text: 'Проверьте данные перед продолжением.', tone: 'warning'} only %}
+                {% include 'website/components/_alert.html.twig' with {title: 'Ошибка', text: 'Исправьте отмеченные поля.', tone: 'danger', role: 'alert'} only %}
+            </div>
+        </div>
+
+        <div class="vf-showcase-group" id="breadcrumbs">
+            <h3>Breadcrumbs</h3>
+            {% include 'website/components/_breadcrumb.html.twig' with {
+                items: [
+                    {label: 'Главная', href: '/'},
+                    {label: 'UI-kit'},
+                ],
+            } only %}
+        </div>
+
+        <div class="vf-showcase-group" id="accordion">
+            <h3>Accordion</h3>
+            {% include 'website/components/_accordion.html.twig' with {
+                accordion_id: 'ui-kit-accordion',
+                items: [
+                    {title: 'Когда использовать Accordion?', text: 'Когда пользователю полезно раскрывать независимые части дополнительной информации.'},
+                    {title: 'Когда не использовать?', text: 'Когда короткий текст проще прочитать целиком без дополнительного действия.'},
+                ],
+            } only %}
+        </div>
+
+        <div class="vf-showcase-group" id="navigation">
+            <h3>Navigation</h3>
+            <p>Production Navbar находится в начале страницы, Footer — после содержимого. На узком viewport Navbar использует Bootstrap collapse.</p>
+        </div>
+    </div>
+{% endblock %}
diff --git a/site/templates/website/sections/_content.html.twig b/site/templates/website/sections/_content.html.twig
new file mode 100644
index 0000000..d067e28
--- /dev/null
+++ b/site/templates/website/sections/_content.html.twig
@@ -0,0 +1,21 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface vf-content-section' %}
+{% set section_id = id|default('vf-content') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'content' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        <p class="vf-lead vf-text-muted">{{ text }}</p>
+    </div>
+    {% if cards|default([]) %}
+        <div class="row vf-card-grid">
+            {% for card in cards %}
+                <div class="col-md-6">
+                    {% include 'website/components/_card.html.twig' with card only %}
+                </div>
+            {% endfor %}
+        </div>
+    {% endif %}
+{% endblock %}
diff --git a/site/templates/website/sections/_cta.html.twig b/site/templates/website/sections/_cta.html.twig
new file mode 100644
index 0000000..fa4f8d2
--- /dev/null
+++ b/site/templates/website/sections/_cta.html.twig
@@ -0,0 +1,15 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-cta') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'cta' %}
+
+{% block section_content %}
+    {% include 'website/components/_cta.html.twig' with {
+        heading_id: labelled_by,
+        title: title,
+        text: text,
+        action_label: action_label,
+        action_href: action_href,
+    } only %}
+{% endblock %}
diff --git a/site/templates/website/sections/_forms_showcase.html.twig b/site/templates/website/sections/_forms_showcase.html.twig
new file mode 100644
index 0000000..5eeeadc
--- /dev/null
+++ b/site/templates/website/sections/_forms_showcase.html.twig
@@ -0,0 +1,139 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = 'forms' %}
+{% set labelled_by = 'forms-title' %}
+{% set section_name = 'forms-showcase' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        <h2 id="forms-title">Forms</h2>
+        <p class="vf-lead vf-text-muted">Каждый control связан с label; Error и Success имеют текстовую обратную связь.</p>
+    </div>
+
+    <form class="vf-form vf-content-width" aria-label="Пример формы" novalidate>
+        {% include 'website/components/_form_input.html.twig' with {
+            id: 'demo-name',
+            label: 'Default input',
+            placeholder: 'Ваше имя',
+        } only %}
+        {% include 'website/components/_form_input.html.twig' with {
+            id: 'demo-focus',
+            label: 'Focus input',
+            value: 'Фокус виден',
+            state: 'focus',
+        } only %}
+        {% include 'website/components/_form_input.html.twig' with {
+            id: 'demo-disabled',
+            label: 'Disabled input',
+            value: 'Недоступно',
+            disabled: true,
+        } only %}
+        {% include 'website/components/_form_input.html.twig' with {
+            id: 'demo-error',
+            label: 'Error input',
+            value: 'Некорректное значение',
+            state: 'error',
+            feedback: 'Укажите корректное значение.',
+        } only %}
+        {% include 'website/components/_form_input.html.twig' with {
+            id: 'demo-success',
+            label: 'Success input',
+            value: 'Корректное значение',
+            state: 'success',
+            feedback: 'Значение принято.',
+        } only %}
+        {% include 'website/components/_form_select.html.twig' with {
+            id: 'demo-select',
+            label: 'Default select',
+            options: [
+                {value: '', label: 'Выберите вариант'},
+                {value: 'one', label: 'Первый вариант'},
+                {value: 'two', label: 'Второй вариант'},
+            ],
+        } only %}
+        {% include 'website/components/_form_select.html.twig' with {
+            id: 'demo-select-focus',
+            label: 'Focus select',
+            state: 'focus',
+            options: [{value: 'one', label: 'Первый вариант'}],
+        } only %}
+        {% include 'website/components/_form_select.html.twig' with {
+            id: 'demo-select-disabled',
+            label: 'Disabled select',
+            disabled: true,
+            options: [{value: 'one', label: 'Недоступно'}],
+        } only %}
+        {% include 'website/components/_form_select.html.twig' with {
+            id: 'demo-select-error',
+            label: 'Error select',
+            state: 'error',
+            feedback: 'Выберите доступный вариант.',
+            options: [{value: '', label: 'Не выбрано'}],
+        } only %}
+        {% include 'website/components/_form_select.html.twig' with {
+            id: 'demo-select-success',
+            label: 'Success select',
+            state: 'success',
+            feedback: 'Вариант выбран.',
+            options: [{value: 'one', label: 'Первый вариант'}],
+        } only %}
+        {% include 'website/components/_form_textarea.html.twig' with {
+            id: 'demo-textarea',
+            label: 'Default textarea',
+            placeholder: 'Краткое описание',
+        } only %}
+        {% include 'website/components/_form_textarea.html.twig' with {
+            id: 'demo-textarea-focus',
+            label: 'Focus textarea',
+            value: 'Фокус виден',
+            state: 'focus',
+        } only %}
+        {% include 'website/components/_form_textarea.html.twig' with {
+            id: 'demo-textarea-disabled',
+            label: 'Disabled textarea',
+            value: 'Недоступно',
+            disabled: true,
+        } only %}
+        {% include 'website/components/_form_textarea.html.twig' with {
+            id: 'demo-textarea-error',
+            label: 'Error textarea',
+            value: 'Недостаточно данных',
+            state: 'error',
+            feedback: 'Добавьте понятное описание.',
+        } only %}
+        {% include 'website/components/_form_textarea.html.twig' with {
+            id: 'demo-textarea-success',
+            label: 'Success textarea',
+            value: 'Понятное описание',
+            state: 'success',
+            feedback: 'Текст принят.',
+        } only %}
+        {% include 'website/components/_form_checkbox.html.twig' with {
+            id: 'demo-checkbox',
+            label: 'Default checkbox',
+        } only %}
+        {% include 'website/components/_form_checkbox.html.twig' with {
+            id: 'demo-checkbox-focus',
+            label: 'Focus checkbox',
+            state: 'focus',
+        } only %}
+        {% include 'website/components/_form_checkbox.html.twig' with {
+            id: 'demo-checkbox-disabled',
+            label: 'Disabled checkbox',
+            disabled: true,
+        } only %}
+        {% include 'website/components/_form_checkbox.html.twig' with {
+            id: 'demo-checkbox-error',
+            label: 'Error checkbox',
+            state: 'error',
+            feedback: 'Подтвердите выбор.',
+        } only %}
+        {% include 'website/components/_form_checkbox.html.twig' with {
+            id: 'demo-checkbox-success',
+            label: 'Success checkbox',
+            checked: true,
+            state: 'success',
+            feedback: 'Выбор подтверждён.',
+        } only %}
+    </form>
+{% endblock %}
diff --git a/site/templates/website/sections/_foundations_showcase.html.twig b/site/templates/website/sections/_foundations_showcase.html.twig
new file mode 100644
index 0000000..e452db8
--- /dev/null
+++ b/site/templates/website/sections/_foundations_showcase.html.twig
@@ -0,0 +1,78 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = 'foundations' %}
+{% set labelled_by = 'foundations-title' %}
+{% set section_name = 'foundations-showcase' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        <h2 id="foundations-title">Основы</h2>
+        <p class="vf-lead vf-text-muted">Typography, colors и spacing используют реальные design tokens.</p>
+    </div>
+
+    <div class="vf-showcase">
+        <div class="vf-showcase-group" id="typography" data-vf-showcase="typography">
+            <h3>Typography</h3>
+            <p class="vf-caption vf-text-muted">H1 — 40/44 mobile, 56/60 desktop</p>
+            <div class="vf-h1">Финансы без догадок</div>
+            <p class="vf-caption vf-text-muted">H2</p>
+            <div class="vf-h2">Понятная система управления</div>
+            <p class="vf-caption vf-text-muted">H3</p>
+            <div class="vf-h3">Данные для решения</div>
+            <p class="vf-caption vf-text-muted">H4</p>
+            <div class="vf-h4">Единый источник правил</div>
+            <p class="vf-caption vf-text-muted">Lead</p>
+            <p class="vf-lead">Крупный вводный текст для краткого объяснения раздела.</p>
+            <p class="vf-caption vf-text-muted">Body</p>
+            <p>Основной текст предназначен для чтения и использует шкалу 16/24.</p>
+            <p class="vf-caption vf-text-muted">Small</p>
+            <p class="vf-small">Дополнительное пояснение без потери читаемости.</p>
+            <p class="vf-caption vf-text-muted">Caption</p>
+            <p class="vf-caption">Краткая подпись</p>
+            <h4>Font weights</h4>
+            <p class="vf-font-weight-regular">Regular 400</p>
+            <p class="vf-font-weight-medium">Medium 500</p>
+            <p class="vf-font-weight-bold">Bold 700</p>
+        </div>
+
+        <div class="vf-showcase-group" id="colors" data-vf-showcase="colors">
+            <h3>Colors</h3>
+            <div class="vf-swatch-grid">
+                {% for color in [
+                    {key: 'primary', label: 'Primary'},
+                    {key: 'text', label: 'Text'},
+                    {key: 'muted', label: 'Muted'},
+                    {key: 'background', label: 'Background'},
+                    {key: 'surface', label: 'Surface'},
+                    {key: 'border', label: 'Border'},
+                    {key: 'success', label: 'Success'},
+                    {key: 'warning', label: 'Warning'},
+                    {key: 'danger', label: 'Danger'},
+                ] %}
+                    <div class="vf-swatch">
+                        <span class="vf-swatch__color vf-swatch__color--{{ color.key }}" aria-hidden="true"></span>
+                        <span>{{ color.label }}<br><code>--vf-color-{{ color.key }}</code></span>
+                    </div>
+                {% endfor %}
+            </div>
+        </div>
+
+        <div class="vf-showcase-group" id="spacing" data-vf-showcase="spacing">
+            <h3>Spacing</h3>
+            <div class="vf-spacing-scale">
+                {% for space in [
+                    {key: 1, label: 4}, {key: 2, label: 8}, {key: 3, label: 12},
+                    {key: 4, label: 16}, {key: 6, label: 24}, {key: 8, label: 32},
+                    {key: 12, label: 48}, {key: 16, label: 64},
+                    {key: 20, label: 80}, {key: 24, label: 96},
+                ] %}
+                    <div class="vf-spacing-scale__item">
+                        <span>{{ space.label }}px</span>
+                        <span class="vf-spacing-scale__bar vf-spacing-scale__bar--{{ space.key }}" aria-hidden="true"></span>
+                    </div>
+                {% endfor %}
+            </div>
+            <p class="vf-small vf-text-muted">Card padding: 16/24px · Form gap: 16px · Grid gap: 24px · Section: 48/64/80px.</p>
+        </div>
+    </div>
+{% endblock %}
diff --git a/site/templates/website/sections/_hero.html.twig b/site/templates/website/sections/_hero.html.twig
new file mode 100644
index 0000000..2d28035
--- /dev/null
+++ b/site/templates/website/sections/_hero.html.twig
@@ -0,0 +1,30 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface vf-hero' %}
+{% set section_id = id|default('vf-hero') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'hero' %}
+
+{% block section_content %}
+    <div class="vf-hero__content">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h1 id="{{ labelled_by }}">{{ title }}</h1>
+        <p class="vf-lead vf-text-muted">{{ text }}</p>
+        {% if primary_action|default(null) or secondary_action|default(null) %}
+            <div class="vf-hero__actions">
+                {% if primary_action|default(null) %}
+                    {% include 'website/components/_button.html.twig' with {
+                        label: primary_action.label,
+                        href: primary_action.href,
+                    } only %}
+                {% endif %}
+                {% if secondary_action|default(null) %}
+                    {% include 'website/components/_button.html.twig' with {
+                        label: secondary_action.label,
+                        href: secondary_action.href,
+                        variant: 'outline-primary',
+                    } only %}
+                {% endif %}
+            </div>
+        {% endif %}
+    </div>
+{% endblock %}
`````
