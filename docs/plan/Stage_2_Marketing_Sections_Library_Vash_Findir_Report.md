# Отчёт о выполнении Stage 2 — Marketing Sections Library

Дата выполнения: 2026-08-16
Исходная задача: `docs/plan/Stage_2_Marketing_Sections_Library_Vash_Findir.md`
Стартовая ревизия: `978d6fe`
Текущая ревизия при формировании отчёта: `31867b73a2410c0fcaaccc25d3d6b5ad9949958b`

## 1. Результат

Stage 2 реализован в рамках существующего Symfony/Twig Public Website без
редизайна Stage 1 и без введения нового frontend toolchain.

В результате:

- primary font публичного сайта переведён на локальный Onest;
- добавлена минимальная библиотека reusable marketing layout patterns;
- создан noindex-каталог `/ui-kit/sections` с production partials и boundary
  cases;
- существующие Hero, CTA, Accordion, Button и form controls Stage 1
  переиспользованы без `v2`-копий;
- `SITE_RULES.md` дополнен правилами выбора, точными Twig API и контрактами
  marketing sections;
- добавлены functional/static regression tests;
- выполнены responsive, accessibility, visual, CI, self-review и два прохода
  Claude Code review.

Production marketing page, backend lead flow, CRM/API, БД, миграции, security,
новый JavaScript, новый framework, реальные marketing claims и реальные цены
не создавались.

## 2. Readiness Gate и границы этапа

До реализации были полностью прочитаны:

- исходная задача Stage 2;
- `docs/architecture/PATTERNS.md`;
- действовавший `AGENTS.md`;
- `SITE_RULES.md`;
- текущие website Twig/CSS, UI-kit, controller и tests.

На старте проверены Git status и baseline tests. Исходный baseline:

- `make assets-check` — PASS;
- `make test` — PASS, 8 tests / 1460 assertions.

Readiness Gate закрыт со следующими выводами:

- изменение относится только к Public Website/Twig;
- бизнес-логика, данные, права доступа, публикации и integrations не
  затрагиваются;
- БД и migrations — N/A;
- публичные существующие routes сохраняются;
- новый route технический, `noindex`, без session/state mutation;
- UI строится на существующей Stage 1 architecture и Design Tokens.

Во время работы shared `HEAD` был продвинут внешним процессом с `978d6fe` до
`31867b7` двумя commits, изменившими только `AGENTS.md` и пользовательские планы.
Эти изменения не редактировались и не включены в implementation diff. Также
сохранён чужой staged-файл
`docs/plan/Stage_1_5_Bootstrap_to_Tailwind_Migration.md`.

## 3. Typography Migration — Onest

Подключён официальный Onest release 2.001:

- upstream: <https://github.com/simpals/onest>;
- release asset:
  <https://github.com/simpals/onest/releases/download/2.001/onest-2.001.zip>;
- archive SHA-256:
  `17b59e19c349e603b7d113a596b6d8e08427e97a7b5235668b69e9d8a06a4267`.

В репозиторий добавлены:

- `site/public/assets/fonts/onest/Onest-Variable.woff2` — 83 980 bytes,
  SHA-256
  `cb4d777c1b146887a2902ef01ba91cb3fb0c85e9804e95794eb289a1966c0782`;
- `site/public/assets/fonts/onest/OFL.txt` — OFL 1.1, SHA-256
  `071195d8806e226faeee60259c28ca67b458227af5195a73f5cfcab06e3003bc`.

Центральный `@font-face` использует:

- `font-family: "Onest"`;
- variable range `100 900`;
- `font-style: normal`;
- `font-display: swap`;
- только локальный WOFF2 URL.

Design Token обновлён на `"Onest", Arial, sans-serif`. Production weights
остались `400 / 500 / 700`. Активные website rules больше не содержат прежний
font fallback и runtime font CDN request.

На реальном Onest проверены H1–H4, Lead, Body, Small, Caption, 400/500/700,
кириллица, `₽`, `%`, числа и punctuation. Добавлены короткий, средний и длинный
H1, а также H2/H3 stress cases 100+ символов. Принудительные `<br>` и
page-specific typography hacks не применялись.

Существующая typography scale после visual/browser проверки сохранена без
изменений: метрики Onest не потребовали системной коррекции размеров или
line-height.

## 4. Reusable layout patterns

Реализованы минимальные production partials:

| Pattern / section | Partial | Ключевое решение |
|---|---|---|
| Text / Text + List | `_text_list.html.twig` | один partial, list optional |
| Grid | `_grid.html.twig` | auto-fit для 2/3/4/6 без count-specific CSS |
| Split | `_split.html.twig` | один partial, `left/right`, content-first DOM |
| Sequential Steps | `_steps.html.twig` | semantic ordered list, одна последовательная колонка |
| Two Paths | `_paths.html.twig` | ровно два сравнимых сценария |
| Case Preview | `_case_preview.html.twig` | semantic `dl`, Problem/Action/Result |
| Quote | `_quote.html.twig` | `figure/blockquote/figcaption` |
| Comparison | `_comparison.html.twig` | semantic table, scoped headers, scroll region |
| FAQ | `_faq.html.twig` | делегирует Accordion Stage 1 |
| Form + Context | `_lead_form.html.twig` | делегирует form controls/Button Stage 1 |

Hero и CTA не создавались заново. Semantic roles Problem, Benefits, Proof,
Pricing и Article Preview используют общие Text/List или Grid patterns через
документированный `marker`; отдельные CSS-копии отсутствуют.

CSS использует только существующие tokens и один переименованный семантический
token `--vf-grid-item-min`. Arbitrary colors, units, breakpoints, gradients,
decorative backgrounds и новый JS не добавлялись.

## 5. `/ui-kit/sections`

Добавлен route `GET /ui-kit/sections` и отдельная техническая navigation link
из `/ui-kit`. Страница имеет `noindex, nofollow` и не добавлена в обычную
public navigation.

На странице показаны production variants:

- Hero Dark;
- Typography stress test;
- Problem;
- Benefits;
- Feature Split media right;
- Feature Split media left;
- Steps;
- Two Paths;
- Case Preview;
- Proof / Expertise;
- Demo Quote;
- Pricing Preview;
- Comparison;
- FAQ;
- Lead Form;
- Article Preview;
- CTA.

Дополнительно реально отрендерены contract boundaries:

- Text без optional items;
- Problem: 3 и 6 items;
- Grid: 2, 3, 4 и 6 items;
- Steps: 2 и 5 steps;
- Comparison: 2 alternatives / 3 criteria и 3 alternatives / 8 criteria;
- Split без optional media и action.

Demo content явно технический. Не используются fake customer identity, logo,
testimonial, job title, award, real price, publication date, popularity badge,
dashboard screenshot или business result. Media placeholder имеет ratio 16:10
и `aria-hidden="true"`.

Lead Form не отправляет и не сохраняет данные: у form отсутствует `action`, а
production Button имеет `type="button"`. Backend/CRM integration отсутствует.

## 6. Accessibility и responsive

Реализовано и проверено:

- один semantic H1 на странице;
- H1 → H2 → H3 hierarchy;
- `role="list"` для visually unstyled lists, чтобы сохранить semantics в
  Safari/VoiceOver;
- видимые номера Steps доступны assistive technology;
- Steps остаются одной последовательной колонкой на всех widths;
- content-first DOM order Split на mobile;
- meaningful media contract требует `alt`, demo placeholder исключён из tree;
- Comparison использует `table`, `caption`, `th scope`, labelled focusable
  scroll region;
- form controls имеют связанные labels;
- keyboard focus видим и использует production focus token;
- Accordion открывается production Bootstrap behavior;
- page-level horizontal overflow отсутствует;
- mobile comparison прокручивается внутри собственного region.

Browser widths: `375 / 768 / 1024 / 1440`.

На каждой ширине подтверждены:

- HTTP 200;
- 0 console errors;
- 0 page errors;
- 0 failed requests;
- 0 px page overflow;
- локальный Onest HTTP 200;
- computed `font-family: Onest, Arial, sans-serif`;
- `document.fonts.check()` для 400/500/700 — true;
- Split directions и content-first order;
- media ratio 1.6;
- sequential Steps;
- boundary item counts;
- Accordion interaction;
- focus outline.

## 7. Tests по уровням

### Unit

`N/A`: изолируемая business/domain logic не добавлялась. Twig composition и
CSS contracts не требуют искусственных unit tests.

### Integration

`N/A`: Doctrine, DB, migrations, Messenger, adapters и external integrations
не менялись.

### Functional

Добавлен `site/tests/Website/MarketingSectionsTest.php`:

- route/noindex/assets/navigation;
- production sections и components;
- variants и required counts;
- boundary contracts;
- accessible list/table/form markup;
- demo form без submission;
- локальный WOFF2/OFL;
- Onest token и `@font-face`;
- допустимые weights;
- отсутствие прежнего active font и external font CDN;
- typography stress cases без `<br>`.

Существующий `WebsiteFoundationTest` продолжает проверять assets mirror,
version hash, Design Tokens, colors, inline CSS/JS запреты, arbitrary values и
Stage 1 regression.

### E2E

Выполнен one-off Playwright browser audit в реальном Chromium на четырёх
контрольных widths. Новый Node toolchain или package в repository не добавлен.

## 8. Выполненные команды и результаты

| Проверка | Результат |
|---|---|
| `make assets` | PASS; public CSS mirror пересобран |
| `make asset-version` | `ee1be40f5f4b` |
| `make assets-check` | PASS |
| `make lint` | PASS: Composer validate/audit, YAML, Twig, container |
| `make cs` | PASS: 0 files to fix |
| `make phpstan` | PASS: no errors |
| `make deptrac` | PASS: 0 violations, 0 warnings, 0 errors |
| `make test` | PASS: 13 tests, 1881 assertions |
| `make ci` | PASS после всех code/docs fixes |
| `git diff --check` | PASS |
| Onest HTTP request | 200, 83 980 bytes |
| Playwright responsive audit | PASS на 375/768/1024/1440 |
| Lighthouse 13.4.1 Accessibility | 1.00 |
| Lighthouse Performance | 0.94 |
| Lighthouse Best Practices | 0.96 |

Lighthouse Best Practices снижен единственным существовавшим до Stage 2
запросом Chromium к отсутствующему `/favicon.ico` (404). Favicon не относится
к Marketing Sections и не исправлялся несвязанным изменением.

Visual review выполнен по full-page screenshots 375 и 1440 и browser metrics
для всех четырёх widths. Обнаруженный первоначально orphan fifth Step на
desktop устранён переходом к одноколоночной последовательности.

## 9. Self-review

Просмотрен полный implementation diff, включая новые untracked-файлы через
`git diff --no-index`. Проверены task DoD, scope, reuse, responsive,
accessibility, demo honesty, отсутствие data/security changes и backward
compatibility Stage 1.

Self-review выявил и исправил:

1. недостающую явную документацию общих defaults;
2. отсутствие отдельных H2/H3 stress cases 100+ символов;
3. недостаточный системный spacing перед optional CTA после list.

После исправлений повторно выполнены assets, browser audit и полный CI.

## 10. Claude Code review

### Первый проход

Claude Code 2.1.233 получил исходную задачу, план, полный diff и фактические
test results в read-only режиме.

Verdict: `0 blocker`, `0 high`, `5 medium`, `11 low`.

Все medium исправлены:

1. восстановлены list semantics и доступность видимых step numbers;
2. Steps переведены из grid 4+1 в последовательную одну колонку;
3. contracts синхронизированы с точными partials, `marker`, параметрами и
   defaults;
4. добавлены и протестированы min/max boundary configurations;
5. добавлен Split без optional media/action и Text без items.

Из low дополнительно исправлены:

- удалён неиспользуемый `.vf-marketing-section` classname;
- business-like glyph sample заменён явно технической строкой;
- после второго review документированы Comparison label defaults и общий
  `outline-primary` action variant.

Отклонённые/non-actionable low:

- selector Split without media достижим и теперь покрыт boundary test;
- `/workspace/SITE_RULES.md` реально используется штатным Docker `make test`;
- `data-vf-layout` нужны functional/browser assertions;
- UI-kit-only styling Demo media соответствует документированному scope;
- legacy `site/templates/base.html.twig` находится вне active
  `site/templates/website` Typography System;
- `alt` является обязательным content contract вызывающей страницы, а не
  runtime validation responsibility Twig partial;
- разный визуальный размер H3 в Paths и compact Grid items использует только
  разрешённую typography scale и отражает разную hierarchy.

### Повторный проход

После исправлений Claude Code повторно проверил текущие sources и assertions.

Итоговый verdict:

> All blocker, high, and medium findings are closed.

M1–M5 подтверждены как resolved; новых blocker/high/medium и scope regression
не найдено. Оставшиеся info относятся только к сознательному размещению
showcase partial/demo styling и не блокируют Stage 2.

## 11. Backward compatibility и риски

- существующие `/`, `/privacy` и `/ui-kit` не изменили contract;
- `/ui-kit` получил только техническую ссылку на новый каталог;
- production components Stage 1 не дублировались и не меняли API;
- Typography scale, Color System, spacing scale, radius и shadows сохранены;
- schema/data migrations отсутствуют;
- secrets и sensitive data отсутствуют;
- production infrastructure не менялась;
- Bootstrap CDN delivery остаётся существующим Stage 1 решением и не связан с
  локальной доставкой Onest;
- `/ui-kit/sections` является noindex demo, а не готовой marketing page.

Неисправленный внешний риск: существующий favicon 404 снижает Lighthouse Best
Practices до 0.96. Для его устранения нужна отдельная задача с реальным brand
asset, а не случайный placeholder.

## 12. Definition of Done

- [x] Onest установлен как primary website font;
- [x] WOFF2 self-hosted и отдаётся HTTP 200;
- [x] OFL 1.1 хранится рядом;
- [x] 400/500/700 работают;
- [x] активный website fallback обновлён;
- [x] Typography scale проверена и не потребовала изменения;
- [x] кириллица, `₽`, `%`, числа и punctuation проверены;
- [x] Text, Text + List, Grid, Split, Steps, Two Paths, Quote, Comparison,
  Form + Context и CTA определены;
- [x] Hero и CTA Stage 1 переиспользованы;
- [x] required Marketing Sections показаны на `/ui-kit/sections`;
- [x] left/right Split реализованы одним partial;
- [x] min/max и optional contract cases показаны и протестированы;
- [x] fake data/customer/logo/metrics отсутствуют;
- [x] semantic HTML, labels, list/table semantics и keyboard behavior
  проверены;
- [x] responsive 375/768/1024/1440 проверен без overflow;
- [x] `SITE_RULES.md` содержит selection rules, точные contracts и New Section
  Decision;
- [x] production assets синхронизированы и version обновлён;
- [x] `make ci` проходит;
- [x] self-review выполнен;
- [x] Claude Code review выполнен дважды, medium+ закрыты;
- [x] миграции и release actions не требуются.

## 13. Полный implementation diff

Diff ниже сформирован относительно текущего `HEAD` `31867b7`. Коммиты,
продвинувшие `HEAD` во время работы, содержали только чужие `AGENTS.md` и plan
files, поэтому не меняют implementation delta. Из diff намеренно исключены:

- исходный task document;
- этот report, чтобы избежать рекурсивного self-diff;
- concurrent user plans и `AGENTS.md`;
- staged `Stage_1_5_Bootstrap_to_Tailwind_Migration.md`.

Binary WOFF2 представлен стандартной строкой Git `Binary files differ`; его
точный размер и SHA-256 приведены в разделе 3. Trailing whitespace в appendix
нормализован для корректного Markdown/Git hygiene; строки и файлы diff не
сокращены.

```diff
diff --git a/SITE_RULES.md b/SITE_RULES.md
index 1873dc7..aa90f8e 100644
--- a/SITE_RULES.md
+++ b/SITE_RULES.md
@@ -7,7 +7,7 @@
 соответствовать этому документу.

 Если нужного решения здесь нет, разработчик не создаёт новый визуальный
-паттерн молча. Сначала он проходит алгоритм расширения из раздела 12 и при
+паттерн молча. Сначала он проходит алгоритм расширения из раздела 13 и при
 обоснованной необходимости обновляет этот документ вместе с реализацией.

 Legacy-шаблоны вне `site/templates/website/` мигрируют по отдельным задачам.
@@ -230,7 +230,7 @@ tokens не выражают подтверждённую роль. Сначал
 Primary font token:

 ```text
-"TT Norms Pro", "TT Norms", Arial, sans-serif
+"Onest", Arial, sans-serif
 ```

 Разрешённые веса: `400`, `500`, `700`.
@@ -251,12 +251,17 @@ Font family, size, line-height и weight задаются tokens. Страниц
 семантическая иерархия заголовков совпадают. Long-form text использует
 `--vf-content-max`.

-Файлы TT Norms Pro не подключены, пока право webfont-использования не
-подтверждено и лицензированные файлы не предоставлены проекту. Запрещено
-копировать шрифт с `tochka.com`, hotlink-ить его или иной чужой CDN. До
-легального подключения работает указанный fallback; fallback не считается
-окончательной визуальной проверкой Typography. Typography scale при подключении
-лицензированного шрифта не меняется автоматически.
+Onest — единственный primary font Public Website. Variable WOFF2 версии 2.001
+получен из официального Open Font distribution, хранится локально в
+`site/public/assets/fonts/onest/` вместе с OFL 1.1 и подключается через общий
+`@font-face` с `font-display: swap`. Runtime-запросы шрифта к внешнему CDN
+запрещены. Используются только веса `400`, `500`, `700`; другие веса требуют
+реального сценария. Sections и pages не задают произвольный `font-family`.
+
+Typography scale проверяется на реальном Onest. Плохой перенос demo-текста не
+исправляется `<br>` или page-specific размером: сначала корректируются текст,
+width constraint или центральный typography token. Stage 2 не потребовал
+изменения существующих size и line-height tokens.

 ### 4.3. Spacing

@@ -281,8 +286,8 @@ Font family, size, line-height и weight задаются tokens. Страниц
 `37px`, `53px` и `71px` ради визуальной подгонки запрещены.

 `--vf-control-min-height: 44px` — не spacing, а минимальная touch target height
-для интерактивного control. `--vf-showcase-item-min` применяется только в
-техническом UI-kit для читаемой раскладки token previews.
+для интерактивного control. `--vf-grid-item-min: 240px` задаёт минимальную
+читаемую ширину равноправного элемента в responsive Grid и его UI-kit previews.

 ### 4.4. Containers, borders, radius, shadows

@@ -372,7 +377,144 @@ Dark Surface `#1E2331` с теми же on-dark content tokens. Dark не при
 иерархической причины. Navbar Dark не вводится, пока для него нет отдельного
 пользовательского сценария.

-## 7. Grid и responsive
+## 7. Marketing Sections
+
+### 7.1. Доступные layout patterns
+
+Marketing section всегда использует `sections/_base.html.twig`, общий
+`.container`, typography/spacing tokens и production components. Доступен
+минимальный набор структур:
+
+| Pattern | Когда применять | Ограничение |
+|---|---|---|
+| Text / Content | один связный смысловой блок | long-form ограничен `--vf-content-max` |
+| Text + List | вводный текст и зависимый перечень | не превращать каждый пункт в Card |
+| Split Text + Media | одна feature и подтверждающее media | один partial; `left` или `right` |
+| Grid | действительно равноправные элементы | 2, 3, 4 или 6 без count-specific CSS |
+| Sequential Steps | действия имеют порядок | 2–5 шагов, semantic `ol` |
+| Two Paths | пользователь выбирает один из двух сценариев | строго 2 пути |
+| Quote | одна подтверждённая цитата | не имитировать клиента demo-текстом |
+| Comparison | варианты сравниваются по одинаковым критериям | semantic table; 2–3 варианта, 3–8 строк |
+| Form + Context | объяснение и короткая lead/diagnostic form | только production form controls |
+| CTA | одно ключевое conversion action | production CTA Stage 1 |
+
+Hero не является новым pattern Stage 2: используется production Hero Stage 1
+в разрешённых `light`/`dark` variants. Left/right Split — один pattern, а не
+два шаблона. Mobile DOM order всегда `content → media`; визуальный `media-left`
+применяется только начиная с tablet. Grid не является универсальной заменой
+обычного списка, а Steps не используется для независимых элементов. Text /
+Content реализуется общим Text + List partial без `items`, поэтому отдельный
+template и CSS для него не создаются.
+
+### 7.2. Правила выбора
+
+| Need / content type | Рекомендуется | Не использовать |
+|---|---|---|
+| Узнаваемые проблемы | Problem / Text + List | Card для каждого тезиса по умолчанию |
+| Равноправные outcomes | Benefits / Grid или List | отдельный декоративный язык Benefits |
+| Одна feature + media | Feature Split | Grid независимых Cards |
+| Последовательность действий | Steps | Grid независимых Cards |
+| Два разных следующих сценария | Two Paths | Comparison table без общих критериев |
+| Одинаковые критерии нескольких вариантов | Comparison | Two Paths для трёх и более вариантов |
+| Вопросы и ответы | FAQ / production Accordion | новый custom collapse или JS |
+| Контекст перед короткой формой | Lead / Form + Context | новая реализация form controls |
+| Финальное ключевое действие | production CTA | новый CTA layout |
+
+Состав страницы определяется информационной задачей. Новая production page не
+является основанием для новой section. Не требуется использовать все patterns,
+не допускается декоративное чередование patterns или backgrounds.
+
+### 7.3. Контракты production sections
+
+Во всех контрактах `id` опционален; `eyebrow` и вводный `text` опциональны,
+если ниже явно не указано обратное. Пустой optional блок не рендерится.
+
+Общие defaults: безопасный технический `id` задаётся partial; `eyebrow`,
+actions, lists, details, links, media, source и note отсутствуют; массивы пусты;
+Hero использует `light`, Split — `media_position=right`, demo media выключен;
+link label Text + List — `Подробнее`, Lead button label — `Продолжить`.
+Comparison использует `criterion_label=Критерий`, а `table_label` по умолчанию
+равен `title`. Optional actions в Grid, Split и Two Paths используют production
+Button variant `outline-primary`.
+
+Связь semantic role с фактическим Twig API:
+
+| Role | Production partial | `marker` / section name |
+|---|---|---|
+| Hero | `sections/_hero.html.twig` | `hero`, фиксирован |
+| Problem | `sections/_text_list.html.twig` | required `marker: problem` |
+| Benefits | `sections/_grid.html.twig` | required `marker: benefits` |
+| Feature | `sections/_split.html.twig` | default `feature` |
+| Steps | `sections/_steps.html.twig` | default `steps` |
+| Two Paths | `sections/_paths.html.twig` | default `paths` |
+| Case Preview | `sections/_case_preview.html.twig` | `case-preview`, фиксирован |
+| Proof | `sections/_text_list.html.twig` | required `marker: proof` |
+| Quote | `sections/_quote.html.twig` | `quote`, фиксирован |
+| Pricing Preview | `sections/_grid.html.twig` | required `marker: pricing` |
+| Comparison | `sections/_comparison.html.twig` | `comparison`, фиксирован |
+| FAQ | `sections/_faq.html.twig` | `faq`, фиксирован |
+| Lead Form | `sections/_lead_form.html.twig` | `lead-form`, фиксирован |
+| Article Preview | `sections/_grid.html.twig` | required `marker: article-preview` |
+| CTA | `sections/_cta.html.twig` | `cta`, фиксирован |
+
+`marker` — обязательный semantic identifier при использовании общего partial в
+production role; произвольный marker на production page не создаёт новый
+section contract.
+
+| Section | Purpose | Required | Optional / defaults | Variants и count | Reuse | Не применять |
+|---|---|---|---|---|---|---|
+| Hero | открыть страницу и сформулировать предложение | `title`, `text` | `eyebrow`, actions; variant `light` | `light`, `dark`; 0–2 actions | Hero + Button Stage 1 | как декоративную пустую заставку |
+| Problem | помочь узнать релевантную проблему | `title`, `items[].text` | item title/link | 3–6 items | Text + List | для независимых benefits |
+| Benefits | описать outcomes, а не функции | `title`, `items[].title` | item text/details/action | 2–6 items | Grid или Text + List | как переименование Feature |
+| Feature Split | объяснить одну возможность и её media | `title`, `text` | `items`, `action`, `media`; position `right` | `left`, `right`; одна feature | Split + Button | для 5 независимых преимуществ |
+| Steps | показать последовательность | `title`, `steps[].title`, `steps[].text` | intro text | 2–5 steps | Sequential Steps | если порядок не имеет значения |
+| Two Paths | дать выбор двух сценариев | `title`, ровно два `paths` с title/text | path list/action | ровно 2 paths | Paths + Button | как good/bad или для 3+ вариантов |
+| Case Preview | кратко показать подтверждённый кейс | `title`, `items[].label`, `items[].text` | intro text | ровно 3 labels: Problem/Action/Result | Case flow | без реального материала или с fake metrics |
+| Proof | показать проверяемые основания доверия | `title`, `items[].text` | item title/link | 1–6 items | Text + List | для fake awards, logos или статистики |
+| Quote | показать согласованную цитату | `title`, `quote` | `source` | одна цитата | Quote | без подтверждённого источника; demo как отзыв |
+| Pricing Preview | сравнить подтверждённые предложения | `title`, `items[].title` | item text/details | 2–4 items | Grid | для fake prices или popularity badge |
+| Comparison | сравнить одинаковые критерии | `title`, `caption`, alternatives, rows | labels | 2–3 alternatives; 3–8 rows | semantic table | если нет общих критериев |
+| FAQ | ответить на частые вопросы | `title`, question/answer items | intro text | 3–6 questions | Accordion Stage 1 | для произвольного длинного контента |
+| Lead Form | дать контекст и короткую demo/lead form | `title`, `text` | context list, `action_label`, note | один form block | form controls + Button Stage 1 | без backend flow выдавать form за рабочую |
+| Article Preview | анонсировать подтверждённые материалы | `title`, `items[].title` | item text/action | 2–4 items | Grid | для fake dates, authors или metrics |
+| CTA | завершить сценарием действия | `title`, `text`, action label/href | нет | один крупный CTA по умолчанию | CTA + Button Stage 1 | как обычный section background |
+
+Semantic roles `Problem`, `Benefits`, `Proof`, `Pricing` и `Article Preview`
+передаются общим Text/List или Grid patterns через документированный `marker`:
+отдельные wrapper и CSS-копии для них запрещены. FAQ делегирует production
+Accordion; CTA и Hero переиспользуют Stage 1 без v2 templates. Lead demo
+использует `type="button"`: backend, CRM/API, сохранение и отправка данных в
+Stage 2 отсутствуют.
+
+### 7.4. Media и demo content
+
+Реальное meaningful media требует содержательный `alt`. Декоративное media
+использует пустой `alt`; технический `Demo media` placeholder существует
+только на UI-kit, имеет ratio `16:10` и скрыт от accessibility tree. Fake
+dashboard, screenshot, customer photo, logo и decorative metric запрещены.
+
+Demo content пишется по-русски, имеет реалистичную длину и явно обозначается
+как demo. Запрещены Lorem Ipsum, вымышленные клиенты, должности, компании,
+отзывы, awards, цены, даты публикаций и business metrics. `/ui-kit/sections` —
+`noindex` технический каталог production partials, не маркетинговая страница и
+не источник реальных продуктовых утверждений.
+
+### 7.5. Решение о новой section
+
+Перед созданием нового marketing section последовательно ответить:
+
+1. Есть ли существующий marketing section?
+2. Есть ли существующий layout pattern?
+3. Можно ли решить задачу новым content contract без нового CSS?
+4. Можно ли расширить существующий pattern без изменения его смысла?
+5. Какую конкретную проблему решает новый section?
+6. Почему существующие sections не подходят?
+
+Только после отрицательных ответов на вопросы о reuse и конкретного ответа на
+последние два вопроса допускается минимальный новый pattern с обновлением этого
+документа и `/ui-kit/sections`.
+
+## 8. Grid и responsive

 Bootstrap container/grid используется последовательно. Mobile — состояние того
 же компонента, не отдельный template или отдельная версия сайта.
@@ -391,7 +533,7 @@ Bootstrap container/grid используется последовательно

 Не скрывать дефект layout глобальным `overflow-x: hidden`.

-## 8. Accessibility baseline
+## 9. Accessibility baseline

 - использовать semantic HTML landmarks;
 - соблюдать последовательность H1 → H2 → H3;
@@ -406,19 +548,22 @@ Bootstrap container/grid используется последовательно
 - сохранять достаточный contrast;
 - учитывать `prefers-reduced-motion`, если motion когда-либо появится.

-Stage 1 не добавляет изображения и декоративную анимацию.
+Stage 1 не добавляет изображения и декоративную анимацию. Stage 2 добавляет
+только технический media placeholder; он скрыт от accessibility tree.

-## 9. UI-kit
+## 10. UI-kit

-`/ui-kit` — визуальный Source of Truth Design System. Он использует website
-layout и production components/sections, а не демонстрационные копии.
+`/ui-kit` — визуальный Source of Truth foundations/components. Он использует
+website layout и production components/sections, а не демонстрационные копии.

 На одной странице показаны Typography, Colors, Spacing, Buttons, Badges,
 Cards, Alerts, Forms, Accordion, Breadcrumbs, Navigation, Dark Hero, Content
-Section, CTA и Dark Footer. Основной H1 только один. Состояния компонентов
-должны быть доступны для визуальной и клавиатурной проверки.
+Section, CTA и Dark Footer. `/ui-kit/sections` — отдельный технический каталог
+Marketing Sections, typography stress cases и layout variants. На каждой
+странице основной H1 только один. Состояния компонентов должны быть доступны
+для визуальной и клавиатурной проверки.

-## 10. CSS и JavaScript
+## 11. CSS и JavaScript

 - NO inline CSS (`style=""` и `<style>`);
 - NO inline JavaScript;
@@ -435,13 +580,14 @@ CSS components/sections использует tokens. Hardcoded base values до
 зафиксированные Bootstrap breakpoints.

 UI-kit-only styles находятся в `components/showcase.css` и подключаются только
-страницей `/ui-kit`, а не общим production layout. Исключение — классы
+страницами `/ui-kit` и `/ui-kit/sections`, а не общим production layout.
+Исключение — классы
 симуляции состояния `vf-is-*`: они задаются production partial только по
 явному параметру UI-kit и находятся в одном declaration block с реальным
 pseudo-class. Такое co-location гарантирует, что демонстрация Focus/Hover не
 расходится с production-состоянием.

-## 11. AI-generated UI anti-patterns
+## 12. AI-generated UI anti-patterns

 **The agent MUST NOT introduce visual variety only for decorative purposes.**

@@ -471,7 +617,7 @@ pseudo-class. Такое co-location гарантирует, что демонс
 Основные инструменты — typography, whitespace, content hierarchy, clear grid,
 consistent spacing, reusable components, реальные данные и изображения.

-## 12. Алгоритм расширения
+## 13. Алгоритм расширения

 До создания UI pattern ответить по порядку:

@@ -488,7 +634,7 @@ consistent spacing, reusable components, реальные данные и изо
 Новая страница не является причиной для «немного другого дизайна» уже
 существующего компонента.

-## 13. Проверки изменения
+## 14. Проверки изменения

 Минимум:

@@ -505,7 +651,7 @@ make test

 Дополнительно для website UI:

-- functional test `GET /ui-kit → 200`;
+- functional tests `GET /ui-kit → 200` и `GET /ui-kit/sections → 200`;
 - рендер production sections/components;
 - static scan `site/templates/website` на inline CSS/JS;
 - static scan component/section CSS на hardcoded colors;
diff --git a/site/assets/styles/website/base.css b/site/assets/styles/website/base.css
index aeac8e4..d25d99b 100644
--- a/site/assets/styles/website/base.css
+++ b/site/assets/styles/website/base.css
@@ -1,3 +1,11 @@
+@font-face {
+    font-family: "Onest";
+    font-style: normal;
+    font-weight: 100 900;
+    font-display: swap;
+    src: url("/assets/fonts/onest/Onest-Variable.woff2") format("woff2");
+}
+
 *,
 *::before,
 *::after {
diff --git a/site/assets/styles/website/components/showcase.css b/site/assets/styles/website/components/showcase.css
index 0cf25c3..0ca8271 100644
--- a/site/assets/styles/website/components/showcase.css
+++ b/site/assets/styles/website/components/showcase.css
@@ -45,7 +45,7 @@

 .vf-swatch-grid {
     display: grid;
-    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-showcase-item-min)), 1fr));
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-grid-item-min)), 1fr));
     gap: var(--vf-grid-gap);
 }

@@ -188,7 +188,7 @@

 .vf-color-context-grid {
     display: grid;
-    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-showcase-item-min)), 1fr));
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-grid-item-min)), 1fr));
     gap: var(--vf-grid-gap);
 }

@@ -251,6 +251,28 @@
 .vf-spacing-scale__bar--20 { width: var(--vf-space-20); }
 .vf-spacing-scale__bar--24 { width: var(--vf-space-24); }

+.vf-demo-media {
+    display: grid;
+    aspect-ratio: 16 / 10;
+    place-content: center;
+    gap: var(--vf-space-2);
+    border: var(--vf-border-width) solid var(--vf-color-border-strong);
+    border-radius: var(--vf-radius-lg);
+    background: var(--vf-color-background);
+    color: var(--vf-color-muted);
+    text-align: center;
+}
+
+.vf-typography-stress {
+    display: grid;
+    max-width: var(--vf-content-max);
+    gap: var(--vf-space-4);
+}
+
+.vf-typography-stress > * {
+    margin-bottom: 0;
+}
+
 @media (min-width: 768px) {
     .vf-form-showcase__states {
         grid-template-columns: repeat(2, minmax(0, 1fr));
diff --git a/site/assets/styles/website/sections/index.css b/site/assets/styles/website/sections/index.css
index 7a55558..5c8ce8c 100644
--- a/site/assets/styles/website/sections/index.css
+++ b/site/assets/styles/website/sections/index.css
@@ -102,6 +102,219 @@
     --bs-gutter-y: var(--vf-grid-gap);
 }

+.vf-text-list,
+.vf-marketing-grid,
+.vf-item-list,
+.vf-steps,
+.vf-paths {
+    padding-left: 0;
+    margin-bottom: 0;
+    list-style: none;
+}
+
+.vf-text-list,
+.vf-item-list {
+    display: grid;
+    gap: var(--vf-space-3);
+}
+
+.vf-text-list {
+    max-width: var(--vf-content-max);
+}
+
+.vf-text-list > li,
+.vf-marketing-grid__item {
+    padding-top: var(--vf-space-4);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+}
+
+.vf-text-list p,
+.vf-marketing-grid__item > :last-child,
+.vf-paths > li > :last-child,
+.vf-steps p {
+    margin-bottom: 0;
+}
+
+.vf-marketing-grid {
+    display: grid;
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-grid-item-min)), 1fr));
+    gap: var(--vf-grid-gap);
+}
+
+.vf-steps {
+    display: grid;
+    grid-template-columns: minmax(0, 1fr);
+    max-width: var(--vf-content-max);
+    gap: var(--vf-grid-gap);
+}
+
+.vf-item-list > li {
+    position: relative;
+    padding-left: var(--vf-space-4);
+}
+
+.vf-item-list > li::before {
+    position: absolute;
+    left: 0;
+    content: "—";
+    color: var(--vf-color-muted);
+}
+
+.vf-split {
+    display: grid;
+    gap: var(--vf-space-8);
+    align-items: center;
+}
+
+.vf-split__content {
+    max-width: var(--vf-content-max);
+}
+
+.vf-split__content > :last-child,
+.vf-lead-form .vf-form > :last-child {
+    margin-bottom: 0;
+}
+
+.vf-split__content .btn,
+.vf-paths .btn,
+.vf-marketing-grid__item .btn {
+    margin-top: var(--vf-space-4);
+}
+
+.vf-split__media img {
+    display: block;
+    width: 100%;
+    aspect-ratio: 16 / 10;
+    border-radius: var(--vf-radius-lg);
+    object-fit: cover;
+}
+
+.vf-steps > li {
+    display: grid;
+    grid-template-columns: var(--vf-space-12) minmax(0, 1fr);
+    gap: var(--vf-space-3);
+}
+
+.vf-steps__number {
+    display: grid;
+    width: var(--vf-space-12);
+    height: var(--vf-space-12);
+    place-items: center;
+    border: var(--vf-border-width) solid var(--vf-color-border-strong);
+    border-radius: var(--vf-radius-md);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-paths {
+    display: grid;
+    gap: var(--vf-grid-gap);
+}
+
+.vf-paths > li {
+    padding: var(--vf-card-padding);
+    border: var(--vf-border-width) solid var(--vf-color-border);
+    border-radius: var(--vf-radius-lg);
+}
+
+.vf-case-flow {
+    display: grid;
+    gap: var(--vf-grid-gap);
+    margin-bottom: 0;
+}
+
+.vf-case-flow > div {
+    padding-top: var(--vf-space-4);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+}
+
+.vf-case-flow dt {
+    margin-bottom: var(--vf-space-2);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-case-flow dd {
+    margin-bottom: 0;
+    color: var(--vf-color-muted);
+}
+
+.vf-quote {
+    max-width: var(--vf-content-max);
+    padding-left: var(--vf-space-6);
+    margin-bottom: 0;
+    border-left: var(--vf-border-width) solid var(--vf-color-border-strong);
+}
+
+.vf-quote blockquote {
+    margin-bottom: var(--vf-space-4);
+    font-size: var(--vf-font-size-h4);
+    line-height: var(--vf-line-height-h4);
+}
+
+.vf-quote blockquote p,
+.vf-quote figcaption {
+    margin-bottom: 0;
+}
+
+.vf-comparison {
+    overflow-x: auto;
+}
+
+.vf-comparison table {
+    width: 100%;
+    min-width: calc(var(--vf-grid-item-min) * 3);
+    border-collapse: collapse;
+}
+
+.vf-comparison caption {
+    color: var(--vf-color-muted);
+}
+
+.vf-comparison :is(th, td) {
+    padding: var(--vf-space-3);
+    border-bottom: var(--vf-border-width) solid var(--vf-color-border);
+    text-align: left;
+    vertical-align: top;
+}
+
+.vf-comparison thead th {
+    border-bottom-color: var(--vf-color-border-strong);
+}
+
+.vf-lead-form .vf-form {
+    padding: var(--vf-card-padding);
+    border: var(--vf-border-width) solid var(--vf-color-border);
+    border-radius: var(--vf-radius-lg);
+}
+
+.vf-lead-form .btn {
+    justify-self: start;
+}
+
+@media (min-width: 768px) {
+    .vf-split {
+        grid-template-columns: repeat(2, minmax(0, 1fr));
+    }
+
+    .vf-split--without-media:not(.vf-split--form) {
+        grid-template-columns: minmax(0, 1fr);
+    }
+
+    .vf-split--media-left .vf-split__media {
+        order: -1;
+    }
+
+    .vf-paths,
+    .vf-case-flow {
+        grid-template-columns: repeat(2, minmax(0, 1fr));
+    }
+}
+
+@media (min-width: 992px) {
+    .vf-case-flow {
+        grid-template-columns: repeat(3, minmax(0, 1fr));
+    }
+}
+
 @media (max-width: 575.98px) {
     .vf-hero__actions .btn,
     .vf-cta__inner .btn {
diff --git a/site/assets/styles/website/tokens.css b/site/assets/styles/website/tokens.css
index 57ed00f..a379720 100644
--- a/site/assets/styles/website/tokens.css
+++ b/site/assets/styles/website/tokens.css
@@ -38,7 +38,7 @@
     --vf-color-info: #0b5cad;
     --vf-color-info-soft: #e8f1fb;

-    --vf-font-primary: "TT Norms Pro", "TT Norms", Arial, sans-serif;
+    --vf-font-primary: "Onest", Arial, sans-serif;
     --vf-font-weight-regular: 400;
     --vf-font-weight-medium: 500;
     --vf-font-weight-bold: 700;
@@ -79,7 +79,7 @@
     --vf-grid-gap: var(--vf-space-6);
     --vf-form-gap: var(--vf-space-4);
     --vf-control-min-height: 44px;
-    --vf-showcase-item-min: 240px;
+    --vf-grid-item-min: 240px;

     --vf-radius-sm: 4px;
     --vf-radius-md: 8px;
diff --git a/site/public/assets/website/base.css b/site/public/assets/website/base.css
index aeac8e4..d25d99b 100644
--- a/site/public/assets/website/base.css
+++ b/site/public/assets/website/base.css
@@ -1,3 +1,11 @@
+@font-face {
+    font-family: "Onest";
+    font-style: normal;
+    font-weight: 100 900;
+    font-display: swap;
+    src: url("/assets/fonts/onest/Onest-Variable.woff2") format("woff2");
+}
+
 *,
 *::before,
 *::after {
diff --git a/site/public/assets/website/components/showcase.css b/site/public/assets/website/components/showcase.css
index 0cf25c3..0ca8271 100644
--- a/site/public/assets/website/components/showcase.css
+++ b/site/public/assets/website/components/showcase.css
@@ -45,7 +45,7 @@

 .vf-swatch-grid {
     display: grid;
-    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-showcase-item-min)), 1fr));
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-grid-item-min)), 1fr));
     gap: var(--vf-grid-gap);
 }

@@ -188,7 +188,7 @@

 .vf-color-context-grid {
     display: grid;
-    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-showcase-item-min)), 1fr));
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-grid-item-min)), 1fr));
     gap: var(--vf-grid-gap);
 }

@@ -251,6 +251,28 @@
 .vf-spacing-scale__bar--20 { width: var(--vf-space-20); }
 .vf-spacing-scale__bar--24 { width: var(--vf-space-24); }

+.vf-demo-media {
+    display: grid;
+    aspect-ratio: 16 / 10;
+    place-content: center;
+    gap: var(--vf-space-2);
+    border: var(--vf-border-width) solid var(--vf-color-border-strong);
+    border-radius: var(--vf-radius-lg);
+    background: var(--vf-color-background);
+    color: var(--vf-color-muted);
+    text-align: center;
+}
+
+.vf-typography-stress {
+    display: grid;
+    max-width: var(--vf-content-max);
+    gap: var(--vf-space-4);
+}
+
+.vf-typography-stress > * {
+    margin-bottom: 0;
+}
+
 @media (min-width: 768px) {
     .vf-form-showcase__states {
         grid-template-columns: repeat(2, minmax(0, 1fr));
diff --git a/site/public/assets/website/sections/index.css b/site/public/assets/website/sections/index.css
index 7a55558..5c8ce8c 100644
--- a/site/public/assets/website/sections/index.css
+++ b/site/public/assets/website/sections/index.css
@@ -102,6 +102,219 @@
     --bs-gutter-y: var(--vf-grid-gap);
 }

+.vf-text-list,
+.vf-marketing-grid,
+.vf-item-list,
+.vf-steps,
+.vf-paths {
+    padding-left: 0;
+    margin-bottom: 0;
+    list-style: none;
+}
+
+.vf-text-list,
+.vf-item-list {
+    display: grid;
+    gap: var(--vf-space-3);
+}
+
+.vf-text-list {
+    max-width: var(--vf-content-max);
+}
+
+.vf-text-list > li,
+.vf-marketing-grid__item {
+    padding-top: var(--vf-space-4);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+}
+
+.vf-text-list p,
+.vf-marketing-grid__item > :last-child,
+.vf-paths > li > :last-child,
+.vf-steps p {
+    margin-bottom: 0;
+}
+
+.vf-marketing-grid {
+    display: grid;
+    grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--vf-grid-item-min)), 1fr));
+    gap: var(--vf-grid-gap);
+}
+
+.vf-steps {
+    display: grid;
+    grid-template-columns: minmax(0, 1fr);
+    max-width: var(--vf-content-max);
+    gap: var(--vf-grid-gap);
+}
+
+.vf-item-list > li {
+    position: relative;
+    padding-left: var(--vf-space-4);
+}
+
+.vf-item-list > li::before {
+    position: absolute;
+    left: 0;
+    content: "—";
+    color: var(--vf-color-muted);
+}
+
+.vf-split {
+    display: grid;
+    gap: var(--vf-space-8);
+    align-items: center;
+}
+
+.vf-split__content {
+    max-width: var(--vf-content-max);
+}
+
+.vf-split__content > :last-child,
+.vf-lead-form .vf-form > :last-child {
+    margin-bottom: 0;
+}
+
+.vf-split__content .btn,
+.vf-paths .btn,
+.vf-marketing-grid__item .btn {
+    margin-top: var(--vf-space-4);
+}
+
+.vf-split__media img {
+    display: block;
+    width: 100%;
+    aspect-ratio: 16 / 10;
+    border-radius: var(--vf-radius-lg);
+    object-fit: cover;
+}
+
+.vf-steps > li {
+    display: grid;
+    grid-template-columns: var(--vf-space-12) minmax(0, 1fr);
+    gap: var(--vf-space-3);
+}
+
+.vf-steps__number {
+    display: grid;
+    width: var(--vf-space-12);
+    height: var(--vf-space-12);
+    place-items: center;
+    border: var(--vf-border-width) solid var(--vf-color-border-strong);
+    border-radius: var(--vf-radius-md);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-paths {
+    display: grid;
+    gap: var(--vf-grid-gap);
+}
+
+.vf-paths > li {
+    padding: var(--vf-card-padding);
+    border: var(--vf-border-width) solid var(--vf-color-border);
+    border-radius: var(--vf-radius-lg);
+}
+
+.vf-case-flow {
+    display: grid;
+    gap: var(--vf-grid-gap);
+    margin-bottom: 0;
+}
+
+.vf-case-flow > div {
+    padding-top: var(--vf-space-4);
+    border-top: var(--vf-border-width) solid var(--vf-color-border);
+}
+
+.vf-case-flow dt {
+    margin-bottom: var(--vf-space-2);
+    font-weight: var(--vf-font-weight-bold);
+}
+
+.vf-case-flow dd {
+    margin-bottom: 0;
+    color: var(--vf-color-muted);
+}
+
+.vf-quote {
+    max-width: var(--vf-content-max);
+    padding-left: var(--vf-space-6);
+    margin-bottom: 0;
+    border-left: var(--vf-border-width) solid var(--vf-color-border-strong);
+}
+
+.vf-quote blockquote {
+    margin-bottom: var(--vf-space-4);
+    font-size: var(--vf-font-size-h4);
+    line-height: var(--vf-line-height-h4);
+}
+
+.vf-quote blockquote p,
+.vf-quote figcaption {
+    margin-bottom: 0;
+}
+
+.vf-comparison {
+    overflow-x: auto;
+}
+
+.vf-comparison table {
+    width: 100%;
+    min-width: calc(var(--vf-grid-item-min) * 3);
+    border-collapse: collapse;
+}
+
+.vf-comparison caption {
+    color: var(--vf-color-muted);
+}
+
+.vf-comparison :is(th, td) {
+    padding: var(--vf-space-3);
+    border-bottom: var(--vf-border-width) solid var(--vf-color-border);
+    text-align: left;
+    vertical-align: top;
+}
+
+.vf-comparison thead th {
+    border-bottom-color: var(--vf-color-border-strong);
+}
+
+.vf-lead-form .vf-form {
+    padding: var(--vf-card-padding);
+    border: var(--vf-border-width) solid var(--vf-color-border);
+    border-radius: var(--vf-radius-lg);
+}
+
+.vf-lead-form .btn {
+    justify-self: start;
+}
+
+@media (min-width: 768px) {
+    .vf-split {
+        grid-template-columns: repeat(2, minmax(0, 1fr));
+    }
+
+    .vf-split--without-media:not(.vf-split--form) {
+        grid-template-columns: minmax(0, 1fr);
+    }
+
+    .vf-split--media-left .vf-split__media {
+        order: -1;
+    }
+
+    .vf-paths,
+    .vf-case-flow {
+        grid-template-columns: repeat(2, minmax(0, 1fr));
+    }
+}
+
+@media (min-width: 992px) {
+    .vf-case-flow {
+        grid-template-columns: repeat(3, minmax(0, 1fr));
+    }
+}
+
 @media (max-width: 575.98px) {
     .vf-hero__actions .btn,
     .vf-cta__inner .btn {
diff --git a/site/public/assets/website/tokens.css b/site/public/assets/website/tokens.css
index 57ed00f..a379720 100644
--- a/site/public/assets/website/tokens.css
+++ b/site/public/assets/website/tokens.css
@@ -38,7 +38,7 @@
     --vf-color-info: #0b5cad;
     --vf-color-info-soft: #e8f1fb;

-    --vf-font-primary: "TT Norms Pro", "TT Norms", Arial, sans-serif;
+    --vf-font-primary: "Onest", Arial, sans-serif;
     --vf-font-weight-regular: 400;
     --vf-font-weight-medium: 500;
     --vf-font-weight-bold: 700;
@@ -79,7 +79,7 @@
     --vf-grid-gap: var(--vf-space-6);
     --vf-form-gap: var(--vf-space-4);
     --vf-control-min-height: 44px;
-    --vf-showcase-item-min: 240px;
+    --vf-grid-item-min: 240px;

     --vf-radius-sm: 4px;
     --vf-radius-md: 8px;
diff --git a/site/src/Controller/UiKitController.php b/site/src/Controller/UiKitController.php
index 0f3be90..ebee564 100644
--- a/site/src/Controller/UiKitController.php
+++ b/site/src/Controller/UiKitController.php
@@ -15,4 +15,10 @@ final class UiKitController extends AbstractController
     {
         return $this->render('website/pages/ui_kit.html.twig');
     }
+
+    #[Route('/ui-kit/sections', name: 'ui_kit_sections', methods: ['GET'])]
+    public function sections(): Response
+    {
+        return $this->render('website/pages/ui_kit_sections.html.twig');
+    }
 }
diff --git a/site/templates/website/layouts/base.html.twig b/site/templates/website/layouts/base.html.twig
index 4df547a..43fa192 100644
--- a/site/templates/website/layouts/base.html.twig
+++ b/site/templates/website/layouts/base.html.twig
@@ -1,4 +1,4 @@
-{% set vf_asset_version = '910ac405b691' %}
+{% set vf_asset_version = 'ee1be40f5f4b' %}
 <!doctype html>
 <html lang="ru">
 <head>
diff --git a/site/templates/website/pages/ui_kit.html.twig b/site/templates/website/pages/ui_kit.html.twig
index 17f7df4..9fa5825 100644
--- a/site/templates/website/pages/ui_kit.html.twig
+++ b/site/templates/website/pages/ui_kit.html.twig
@@ -15,6 +15,7 @@
         items: [
             {label: 'Главная', href: path('home')},
             {label: 'UI-kit', href: path('ui_kit'), active: true},
+            {label: 'Секции', href: path('ui_kit_sections')},
         ],
     } only %}
 {% endblock %}
diff --git a/site/public/assets/fonts/onest/OFL.txt b/site/public/assets/fonts/onest/OFL.txt
new file mode 100644
index 0000000..b6f4f91
--- /dev/null
+++ b/site/public/assets/fonts/onest/OFL.txt
@@ -0,0 +1,93 @@
+Copyright 2021 The Onest Project Authors (https://github.com/googlefonts/onest)
+
+This Font Software is licensed under the SIL Open Font License, Version 1.1.
+This license is copied below, and is also available with a FAQ at:
+https://scripts.sil.org/OFL
+
+
+-----------------------------------------------------------
+SIL OPEN FONT LICENSE Version 1.1 - 26 February 2007
+-----------------------------------------------------------
+
+PREAMBLE
+The goals of the Open Font License (OFL) are to stimulate worldwide
+development of collaborative font projects, to support the font creation
+efforts of academic and linguistic communities, and to provide a free and
+open framework in which fonts may be shared and improved in partnership
+with others.
+
+The OFL allows the licensed fonts to be used, studied, modified and
+redistributed freely as long as they are not sold by themselves. The
+fonts, including any derivative works, can be bundled, embedded,
+redistributed and/or sold with any software provided that any reserved
+names are not used by derivative works. The fonts and derivatives,
+however, cannot be released under any other type of license. The
+requirement for fonts to remain under this license does not apply
+to any document created using the fonts or their derivatives.
+
+DEFINITIONS
+"Font Software" refers to the set of files released by the Copyright
+Holder(s) under this license and clearly marked as such. This may
+include source files, build scripts and documentation.
+
+"Reserved Font Name" refers to any names specified as such after the
+copyright statement(s).
+
+"Original Version" refers to the collection of Font Software components as
+distributed by the Copyright Holder(s).
+
+"Modified Version" refers to any derivative made by adding to, deleting,
+or substituting -- in part or in whole -- any of the components of the
+Original Version, by changing formats or by porting the Font Software to a
+new environment.
+
+"Author" refers to any designer, engineer, programmer, technical
+writer or other person who contributed to the Font Software.
+
+PERMISSION & CONDITIONS
+Permission is hereby granted, free of charge, to any person obtaining
+a copy of the Font Software, to use, study, copy, merge, embed, modify,
+redistribute, and sell modified and unmodified copies of the Font
+Software, subject to the following conditions:
+
+1) Neither the Font Software nor any of its individual components,
+in Original or Modified Versions, may be sold by itself.
+
+2) Original or Modified Versions of the Font Software may be bundled,
+redistributed and/or sold with any software, provided that each copy
+contains the above copyright notice and this license. These can be
+included either as stand-alone text files, human-readable headers or
+in the appropriate machine-readable metadata fields within text or
+binary files as long as those fields can be easily viewed by the user.
+
+3) No Modified Version of the Font Software may use the Reserved Font
+Name(s) unless explicit written permission is granted by the corresponding
+Copyright Holder. This restriction only applies to the primary font name as
+presented to the users.
+
+4) The name(s) of the Copyright Holder(s) or the Author(s) of the Font
+Software shall not be used to promote, endorse or advertise any
+Modified Version, except to acknowledge the contribution(s) of the
+Copyright Holder(s) and the Author(s) or with their explicit written
+permission.
+
+5) The Font Software, modified or unmodified, in part or in whole,
+must be distributed entirely under this license, and must not be
+distributed under any other license. The requirement for fonts to
+remain under this license does not apply to any document created
+using the Font Software.
+
+TERMINATION
+This license becomes null and void if any of the above conditions are
+not met.
+
+DISCLAIMER
+THE FONT SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
+EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO ANY WARRANTIES OF
+MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT
+OF COPYRIGHT, PATENT, TRADEMARK, OR OTHER RIGHT. IN NO EVENT SHALL THE
+COPYRIGHT HOLDER BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
+INCLUDING ANY GENERAL, SPECIAL, INDIRECT, INCIDENTAL, OR CONSEQUENTIAL
+DAMAGES, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
+FROM, OUT OF THE USE OR INABILITY TO USE THE FONT SOFTWARE OR FROM
+OTHER DEALINGS IN THE FONT SOFTWARE.
diff --git a/site/public/assets/fonts/onest/Onest-Variable.woff2 b/site/public/assets/fonts/onest/Onest-Variable.woff2
new file mode 100644
index 0000000..04b71e9
Binary files /dev/null and b/site/public/assets/fonts/onest/Onest-Variable.woff2 differ
diff --git a/site/templates/website/pages/ui_kit_sections.html.twig b/site/templates/website/pages/ui_kit_sections.html.twig
new file mode 100644
index 0000000..1fdf572
--- /dev/null
+++ b/site/templates/website/pages/ui_kit_sections.html.twig
@@ -0,0 +1,322 @@
+{% extends 'website/layouts/base.html.twig' %}
+
+{% block title %}Marketing Sections — UI-kit «Ваш Финдир»{% endblock %}
+{% block description %}Технический каталог production-паттернов маркетинговых секций публичного сайта «Ваш Финдир».{% endblock %}
+{% block head %}
+    <meta name="robots" content="noindex, nofollow">
+    <link href="/assets/website/components/showcase.css?v={{ vf_asset_version }}" rel="stylesheet">
+{% endblock %}
+
+{% block navigation %}
+    {% include 'website/components/_navbar.html.twig' with {
+        brand: 'Ваш Финдир',
+        brand_href: path('home'),
+        navigation_id: 'vf-sections-navigation',
+        items: [
+            {label: 'Главная', href: path('home')},
+            {label: 'UI-kit', href: path('ui_kit')},
+            {label: 'Секции', href: path('ui_kit_sections'), active: true},
+        ],
+    } only %}
+{% endblock %}
+
+{% block body %}
+    {% include 'website/sections/_hero.html.twig' with {
+        id: 'sections-hero',
+        eyebrow: 'Marketing Sections Library',
+        title: 'Готовые секции для ясного разговора о финансах бизнеса',
+        text: 'Техническая страница показывает production-паттерны, адаптивность и честные demo-состояния без вымышленных клиентов, цифр и продуктовых обещаний.',
+        variant: 'dark',
+        primary_action: {label: 'Смотреть паттерны', href: '#problem-section'},
+        secondary_action: {label: 'Открыть основы UI-kit', href: path('ui_kit')},
+    } only %}
+
+    {% include 'website/sections/_typography_stress_showcase.html.twig' with {
+        id: 'typography-stress-section',
+    } only %}
+
+    {% include 'website/sections/_text_list.html.twig' with {
+        id: 'problem-section',
+        marker: 'problem',
+        eyebrow: 'Problem / Text + List',
+        title: 'Когда финансовая картина бизнеса распадается на отдельные файлы',
+        text: 'Шесть типовых сигналов помогают быстро узнать свой контекст. Это демонстрационный контент, а не утверждение о конкретной компании.',
+        items: [
+            {text: 'Остатки на счетах видны, но свободные деньги не определены.'},
+            {text: 'Платежи планируются в нескольких несвязанных таблицах.'},
+            {text: 'План и факт сверяются нерегулярно.'},
+            {text: 'Доходность направлений оценивается без общей методики.'},
+            {text: 'Решения зависят от ручной сборки отчёта.'},
+            {text: 'Ответственность за финансовые данные размыта.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_grid.html.twig' with {
+        id: 'benefits-section',
+        marker: 'benefits',
+        eyebrow: 'Benefits / Grid',
+        title: 'Два результата одного управленческого контура',
+        text: 'Компактный Grid подходит для равноправных тезисов, которые действительно нужно сравнить рядом.',
+        items: [
+            {title: 'Понятная картина', text: 'Единые правила помогают читать план, факт и отклонения в одном контексте.'},
+            {title: 'Регулярные решения', text: 'Ритм подготовки данных становится частью управленческого процесса.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_split.html.twig' with {
+        id: 'feature-right-section',
+        marker: 'feature',
+        eyebrow: 'Feature / Split / Media Right',
+        title: 'Сначала смысл, затем подтверждающее изображение',
+        text: 'Контент остаётся первым в DOM и на мобильном экране. Demo media обозначает только допустимую область будущего реального материала.',
+        items: ['Один смысловой акцент', 'Содержательный alt для реального изображения', 'Соотношение области 16:10'],
+        action: {label: 'Пример вторичного действия', href: '#feature-left-section'},
+        media_position: 'right',
+        demo_media: true,
+    } only %}
+
+    {% include 'website/sections/_split.html.twig' with {
+        id: 'feature-left-section',
+        marker: 'feature',
+        eyebrow: 'Feature / Split / Media Left',
+        title: 'Вариант направления не меняет семантику секции',
+        text: 'На desktop media может находиться слева, но мобильный порядок сохраняет сначала заголовок и объяснение. CTA не обязателен.',
+        items: ['Тот же production partial', 'Без отдельной mobile-разметки', 'Без декоративного чередования фона'],
+        media_position: 'left',
+        demo_media: true,
+    } only %}
+
+    {% include 'website/sections/_steps.html.twig' with {
+        id: 'steps-section',
+        eyebrow: 'Steps / Ordered List',
+        title: 'Процесс из пяти последовательных шагов',
+        text: 'Нумерация отражает реальный порядок, поэтому используется семантический ordered list.',
+        steps: [
+            {title: 'Контекст', text: 'Фиксируем исходную управленческую задачу.'},
+            {title: 'Данные', text: 'Определяем источники и правила подготовки.'},
+            {title: 'Модель', text: 'Согласуем структуру планирования и отчётности.'},
+            {title: 'Ритм', text: 'Назначаем регулярные точки сверки.'},
+            {title: 'Решения', text: 'Используем результат в управлении.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_paths.html.twig' with {
+        id: 'paths-section',
+        eyebrow: 'Two Paths',
+        title: 'Два взаимоисключающих пути',
+        text: 'Паттерн применяется только когда пользователю действительно нужно выбрать один из двух следующих шагов.',
+        paths: [
+            {
+                title: 'Разобрать текущую ситуацию',
+                text: 'Подходит, когда сначала требуется определить проблемные зоны.',
+                items: ['Краткий контекст', 'Список вопросов'],
+                action: {label: 'Выбрать разбор', href: '#lead-form-section'},
+            },
+            {
+                title: 'Обсудить регулярную работу',
+                text: 'Подходит, когда задача и ожидаемый формат уже сформулированы.',
+                items: ['Известный запрос', 'Понятный горизонт работы'],
+                action: {label: 'Выбрать обсуждение', href: '#lead-form-section'},
+            },
+        ],
+    } only %}
+
+    {% include 'website/sections/_case_preview.html.twig' with {
+        id: 'case-preview-section',
+        eyebrow: 'Case Preview',
+        title: 'Структура кейса без вымышленных результатов',
+        text: 'Preview показывает контракт Problem → Action → Result. Содержимое ниже явно техническое и не описывает реального клиента.',
+        items: [
+            {label: 'Problem', text: 'Кратко описать подтверждённый исходный контекст.'},
+            {label: 'Action', text: 'Показать фактически выполненную работу без лишней детализации.'},
+            {label: 'Result', text: 'Указать проверяемый результат или честно оставить его без числовой метрики.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_text_list.html.twig' with {
+        id: 'proof-section',
+        marker: 'proof',
+        eyebrow: 'Proof / Text + List',
+        title: 'Доказательства должны быть проверяемыми',
+        text: 'Пока реальные материалы не предоставлены, библиотека показывает только допустимые типы подтверждений.',
+        items: [
+            {title: 'Процесс', text: 'Описание методики с понятными входами и результатом.'},
+            {title: 'Артефакт', text: 'Реальный обезличенный пример документа с разрешением на публикацию.'},
+            {title: 'Источник', text: 'Ссылка на проверяемую публикацию или подтверждённый кейс.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_quote.html.twig' with {
+        id: 'quote-section',
+        eyebrow: 'Quote',
+        title: 'Контракт цитаты',
+        quote: 'Здесь размещается только точная согласованная цитата. Демонстрационный текст не выдаётся за отзыв клиента.',
+        source: 'Demo label — не реальный автор и не клиент',
+    } only %}
+
+    {% include 'website/sections/_grid.html.twig' with {
+        id: 'pricing-section',
+        marker: 'pricing',
+        eyebrow: 'Pricing / Grid',
+        title: 'До четырёх сопоставимых вариантов',
+        text: 'Цены и преимущества не выдуманы: технический preview фиксирует только структуру будущих подтверждённых предложений.',
+        items: [
+            {title: 'Вариант A', text: 'Кратко описать кому подходит.', details: ['Подтверждённый состав', 'Понятное ограничение']},
+            {title: 'Вариант B', text: 'Кратко описать кому подходит.', details: ['Подтверждённый состав', 'Понятное ограничение']},
+            {title: 'Вариант C', text: 'Кратко описать кому подходит.', details: ['Подтверждённый состав', 'Понятное ограничение']},
+            {title: 'Вариант D', text: 'Кратко описать кому подходит.', details: ['Подтверждённый состав', 'Понятное ограничение']},
+        ],
+    } only %}
+
+    {% include 'website/sections/_comparison.html.twig' with {
+        id: 'comparison-section',
+        eyebrow: 'Comparison',
+        title: 'Сравнение трёх альтернатив',
+        text: 'Таблица использует заголовки строк и столбцов, а на узком экране прокручивается внутри своего региона.',
+        table_label: 'Демонстрационное сравнение вариантов работы',
+        caption: 'Demo: значения иллюстрируют структуру, а не коммерческое предложение.',
+        alternatives: ['Вариант A', 'Вариант B', 'Вариант C'],
+        rows: [
+            {criterion: 'Исходный контекст', values: ['Определён', 'Определён', 'Требует разбора']},
+            {criterion: 'Формат взаимодействия', values: ['Разовый', 'Регулярный', 'Диагностический']},
+            {criterion: 'Горизонт планирования', values: ['Короткий', 'Средний', 'Не задан']},
+            {criterion: 'Набор данных', values: ['Согласован', 'Согласован', 'Уточняется']},
+            {criterion: 'Ответственный', values: ['Назначен', 'Назначен', 'Определяется']},
+            {criterion: 'Ритм сверки', values: ['По завершении', 'Регулярный', 'После диагностики']},
+            {criterion: 'Результат', values: ['Артефакт', 'Процесс', 'Рекомендации']},
+            {criterion: 'Следующий шаг', values: ['Передача', 'Продолжение', 'Выбор формата']},
+        ],
+    } only %}
+
+    {% include 'website/sections/_faq.html.twig' with {
+        id: 'faq-section',
+        eyebrow: 'FAQ / Accordion',
+        title: 'Частые вопросы о библиотеке секций',
+        text: 'FAQ переиспользует production Accordion из базового UI-kit.',
+        items: [
+            {title: 'Это готовая маркетинговая страница?', text: 'Нет. Это технический каталог production-паттернов.'},
+            {title: 'Можно ли менять порядок секций?', text: 'Да, если порядок следует задаче и информационной иерархии страницы.'},
+            {title: 'Обязательно ли использовать все секции?', text: 'Нет. Используется минимальный набор, необходимый конкретному сценарию.'},
+            {title: 'Можно ли выдумать пример клиента?', text: 'Нет. Неподтверждённые имена, отзывы, логотипы и метрики запрещены.'},
+            {title: 'Когда нужна новая секция?', text: 'Только когда существующие паттерны не решают подтверждённую задачу.'},
+            {title: 'Где описан контракт?', text: 'В разделе Marketing Sections документа SITE_RULES.md.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_lead_form.html.twig' with {
+        id: 'lead-form-section',
+        eyebrow: 'Form + Context',
+        title: 'Обсудить финансовую задачу',
+        text: 'Форма демонстрирует только layout и production controls. На технической странице данные не отправляются и не сохраняются.',
+        context_items: ['Короткий набор полей', 'Явные labels', 'Согласие до отправки'],
+        action_label: 'Демонстрационная кнопка',
+        note: 'Demo only: обработчик отправки не подключён.',
+    } only %}
+
+    {% include 'website/sections/_grid.html.twig' with {
+        id: 'article-preview-section',
+        marker: 'article-preview',
+        eyebrow: 'Article Preview / Grid',
+        title: 'Два примера редакционных анонсов',
+        text: 'Заголовки служат нейтральной демонстрацией структуры и не имитируют существующие публикации.',
+        items: [
+            {title: 'Как сформулировать вопрос к управленческому отчёту', text: 'Demo-анонс: тезис, контекст и понятное направление перехода.', action: {label: 'Пример ссылки', href: '#article-preview-section'}},
+            {title: 'Какие данные нужны для план-факт анализа', text: 'Demo-анонс: без вымышленной даты, автора или показателей популярности.', action: {label: 'Пример ссылки', href: '#article-preview-section'}},
+        ],
+    } only %}
+
+    {% include 'website/sections/_text_list.html.twig' with {
+        id: 'text-content-stress',
+        marker: 'text-content-stress',
+        eyebrow: 'Contract stress / Text',
+        title: 'Text pattern без списка',
+        text: 'Optional items отсутствуют: section сохраняет heading, reading width и корректную иерархию без пустого list container.',
+    } only %}
+
+    {% include 'website/sections/_text_list.html.twig' with {
+        id: 'problem-min-stress',
+        marker: 'problem',
+        eyebrow: 'Contract stress / Problem minimum',
+        title: 'Минимум из трёх problem statements',
+        text: 'Технический boundary case для нижней границы контракта.',
+        items: [
+            {text: 'Первый демонстрационный тезис.'},
+            {text: 'Второй тезис с немного более длинным текстом для переноса.'},
+            {text: 'Третий демонстрационный тезис.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_grid.html.twig' with {
+        id: 'grid-three-stress',
+        marker: 'grid-stress',
+        eyebrow: 'Contract stress / Grid × 3',
+        title: 'Три равноправных элемента',
+        items: [
+            {title: 'Элемент A', text: 'Demo content.'},
+            {title: 'Элемент B', text: 'Demo content.'},
+            {title: 'Элемент C', text: 'Demo content.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_grid.html.twig' with {
+        id: 'grid-six-stress',
+        marker: 'grid-stress',
+        eyebrow: 'Contract stress / Grid × 6',
+        title: 'Шесть равноправных элементов',
+        items: [
+            {title: 'Элемент A', text: 'Demo content.'},
+            {title: 'Элемент B', text: 'Demo content.'},
+            {title: 'Элемент C', text: 'Demo content.'},
+            {title: 'Элемент D', text: 'Demo content.'},
+            {title: 'Элемент E', text: 'Demo content.'},
+            {title: 'Элемент F', text: 'Demo content.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_steps.html.twig' with {
+        id: 'steps-min-stress',
+        eyebrow: 'Contract stress / Steps minimum',
+        title: 'Последовательность из двух шагов',
+        steps: [
+            {title: 'Шаг один', text: 'Первое обязательное действие.'},
+            {title: 'Шаг два', text: 'Следующее действие после первого.'},
+        ],
+    } only %}
+
+    {% include 'website/sections/_comparison.html.twig' with {
+        id: 'comparison-min-stress',
+        eyebrow: 'Contract stress / Comparison minimum',
+        title: 'Две альтернативы и три критерия',
+        caption: 'Demo boundary case минимального контракта Comparison.',
+        alternatives: ['Вариант A', 'Вариант B'],
+        rows: [
+            {criterion: 'Критерий 1', values: ['Значение A', 'Значение B']},
+            {criterion: 'Критерий 2', values: ['Значение A', 'Значение B']},
+            {criterion: 'Критерий 3', values: ['Значение A', 'Значение B']},
+        ],
+    } only %}
+
+    {% include 'website/sections/_split.html.twig' with {
+        id: 'feature-no-media-stress',
+        marker: 'feature',
+        eyebrow: 'Contract stress / Split optional fields',
+        title: 'Feature без optional media и action',
+        text: 'Один и тот же production partial остаётся читаемым без необязательных блоков и не создаёт пустую колонку.',
+    } only %}
+
+    {% include 'website/sections/_cta.html.twig' with {
+        id: 'sections-cta',
+        title: 'Собирайте страницу из минимально необходимых паттернов',
+        text: 'Перед добавлением новой секции проверьте существующие контракты и правила расширения.',
+        action_label: 'Вернуться к началу',
+        action_href: '#main-content',
+    } only %}
+{% endblock %}
+
+{% block footer %}
+    {% include 'website/components/_footer.html.twig' with {
+        brand: 'Ваш Финдир',
+        privacy_href: path('privacy'),
+        variant: 'dark',
+    } only %}
+{% endblock %}
diff --git a/site/templates/website/sections/_case_preview.html.twig b/site/templates/website/sections/_case_preview.html.twig
new file mode 100644
index 0000000..9866062
--- /dev/null
+++ b/site/templates/website/sections/_case_preview.html.twig
@@ -0,0 +1,22 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-case-preview') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'case-preview' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    <dl class="vf-case-flow" data-vf-layout="case-preview">
+        {% for item in items %}
+            <div>
+                <dt>{{ item.label }}</dt>
+                <dd>{{ item.text }}</dd>
+            </div>
+        {% endfor %}
+    </dl>
+{% endblock %}
diff --git a/site/templates/website/sections/_comparison.html.twig b/site/templates/website/sections/_comparison.html.twig
new file mode 100644
index 0000000..e3936d8
--- /dev/null
+++ b/site/templates/website/sections/_comparison.html.twig
@@ -0,0 +1,33 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-comparison') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'comparison' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    <div class="vf-comparison" data-vf-layout="comparison" role="region" aria-label="{{ table_label|default(title) }}" tabindex="0">
+        <table>
+            <caption>{{ caption }}</caption>
+            <thead>
+                <tr>
+                    <th scope="col">{{ criterion_label|default('Критерий') }}</th>
+                    {% for alternative in alternatives %}<th scope="col">{{ alternative }}</th>{% endfor %}
+                </tr>
+            </thead>
+            <tbody>
+                {% for row in rows %}
+                    <tr>
+                        <th scope="row">{{ row.criterion }}</th>
+                        {% for value in row.values %}<td>{{ value }}</td>{% endfor %}
+                    </tr>
+                {% endfor %}
+            </tbody>
+        </table>
+    </div>
+{% endblock %}
diff --git a/site/templates/website/sections/_faq.html.twig b/site/templates/website/sections/_faq.html.twig
new file mode 100644
index 0000000..8f13cdb
--- /dev/null
+++ b/site/templates/website/sections/_faq.html.twig
@@ -0,0 +1,20 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-faq') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'faq' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    <div class="vf-content-width" data-vf-layout="faq">
+        {% include 'website/components/_accordion.html.twig' with {
+            accordion_id: section_id ~ '-accordion',
+            items: items,
+        } only %}
+    </div>
+{% endblock %}
diff --git a/site/templates/website/sections/_grid.html.twig b/site/templates/website/sections/_grid.html.twig
new file mode 100644
index 0000000..3ce3777
--- /dev/null
+++ b/site/templates/website/sections/_grid.html.twig
@@ -0,0 +1,34 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-grid') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = marker|default('grid') %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    <ul class="vf-marketing-grid" role="list" data-vf-layout="grid">
+        {% for item in items %}
+            <li class="vf-marketing-grid__item">
+                <h3 class="vf-h4">{{ item.title }}</h3>
+                {% if item.text|default(null) %}<p class="vf-text-muted">{{ item.text }}</p>{% endif %}
+                {% if item.details|default([]) %}
+                    <ul class="vf-item-list" role="list">
+                        {% for detail in item.details %}<li>{{ detail }}</li>{% endfor %}
+                    </ul>
+                {% endif %}
+                {% if item.action|default(null) %}
+                    {% include 'website/components/_button.html.twig' with {
+                        label: item.action.label,
+                        href: item.action.href,
+                        variant: 'outline-primary',
+                    } only %}
+                {% endif %}
+            </li>
+        {% endfor %}
+    </ul>
+{% endblock %}
diff --git a/site/templates/website/sections/_lead_form.html.twig b/site/templates/website/sections/_lead_form.html.twig
new file mode 100644
index 0000000..76c6579
--- /dev/null
+++ b/site/templates/website/sections/_lead_form.html.twig
@@ -0,0 +1,54 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface vf-lead-form' %}
+{% set section_id = id|default('vf-lead-form') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'lead-form' %}
+
+{% block section_content %}
+    <div class="vf-split vf-split--form" data-vf-layout="form-context">
+        <div class="vf-split__content">
+            {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+            <h2 id="{{ labelled_by }}">{{ title }}</h2>
+            <p class="vf-lead vf-text-muted">{{ text }}</p>
+            {% if context_items|default([]) %}
+                <ul class="vf-item-list" role="list">
+                    {% for item in context_items %}<li>{{ item }}</li>{% endfor %}
+                </ul>
+            {% endif %}
+        </div>
+
+        <form class="vf-form" data-vf-demo-form>
+            {% include 'website/components/_form_input.html.twig' with {
+                id: section_id ~ '-name',
+                name: 'name',
+                label: 'Имя',
+                required: true,
+                placeholder: 'Как к вам обращаться',
+            } only %}
+            {% include 'website/components/_form_input.html.twig' with {
+                id: section_id ~ '-contact',
+                name: 'contact',
+                label: 'Рабочий телефон или email',
+                required: true,
+                placeholder: '+7 900 000-00-00',
+            } only %}
+            {% include 'website/components/_form_textarea.html.twig' with {
+                id: section_id ~ '-task',
+                name: 'task',
+                label: 'Коротко о задаче',
+                placeholder: 'Контекст для первого разговора',
+            } only %}
+            {% include 'website/components/_form_checkbox.html.twig' with {
+                id: section_id ~ '-agreement',
+                name: 'agreement',
+                label: 'Согласен на обработку персональных данных',
+                required: true,
+            } only %}
+            {% include 'website/components/_button.html.twig' with {
+                label: action_label|default('Продолжить'),
+                type: 'button',
+            } only %}
+            {% if note|default(null) %}<p class="vf-small vf-text-muted">{{ note }}</p>{% endif %}
+        </form>
+    </div>
+{% endblock %}
diff --git a/site/templates/website/sections/_paths.html.twig b/site/templates/website/sections/_paths.html.twig
new file mode 100644
index 0000000..cc2ccd5
--- /dev/null
+++ b/site/templates/website/sections/_paths.html.twig
@@ -0,0 +1,34 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-paths') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = marker|default('paths') %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    <ul class="vf-paths" role="list" data-vf-layout="paths">
+        {% for path in paths %}
+            <li>
+                <h3>{{ path.title }}</h3>
+                <p class="vf-text-muted">{{ path.text }}</p>
+                {% if path.items|default([]) %}
+                    <ul class="vf-item-list" role="list">
+                        {% for item in path.items %}<li>{{ item }}</li>{% endfor %}
+                    </ul>
+                {% endif %}
+                {% if path.action|default(null) %}
+                    {% include 'website/components/_button.html.twig' with {
+                        label: path.action.label,
+                        href: path.action.href,
+                        variant: 'outline-primary',
+                    } only %}
+                {% endif %}
+            </li>
+        {% endfor %}
+    </ul>
+{% endblock %}
diff --git a/site/templates/website/sections/_quote.html.twig b/site/templates/website/sections/_quote.html.twig
new file mode 100644
index 0000000..5489b49
--- /dev/null
+++ b/site/templates/website/sections/_quote.html.twig
@@ -0,0 +1,19 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-quote') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'quote' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+    </div>
+
+    <figure class="vf-quote" data-vf-layout="quote">
+        <blockquote>
+            <p>{{ quote }}</p>
+        </blockquote>
+        {% if source|default(null) %}<figcaption class="vf-text-muted">{{ source }}</figcaption>{% endif %}
+    </figure>
+{% endblock %}
diff --git a/site/templates/website/sections/_split.html.twig b/site/templates/website/sections/_split.html.twig
new file mode 100644
index 0000000..d8069d1
--- /dev/null
+++ b/site/templates/website/sections/_split.html.twig
@@ -0,0 +1,42 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-split') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = marker|default('feature') %}
+{% set media_position = media_position|default('right') == 'left' ? 'left' : 'right' %}
+{% set has_media = media|default(null) or demo_media|default(false) %}
+
+{% block section_content %}
+    <div class="vf-split vf-split--media-{{ media_position }}{% if not has_media %} vf-split--without-media{% endif %}" data-vf-layout="split" data-vf-media-position="{{ media_position }}">
+        <div class="vf-split__content">
+            {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+            <h2 id="{{ labelled_by }}">{{ title }}</h2>
+            <p class="vf-lead vf-text-muted">{{ text }}</p>
+            {% if items|default([]) %}
+                <ul class="vf-item-list" role="list">
+                    {% for item in items %}<li>{{ item }}</li>{% endfor %}
+                </ul>
+            {% endif %}
+            {% if action|default(null) %}
+                {% include 'website/components/_button.html.twig' with {
+                    label: action.label,
+                    href: action.href,
+                    variant: 'outline-primary',
+                } only %}
+            {% endif %}
+        </div>
+
+        {% if has_media %}
+            <div class="vf-split__media">
+                {% if media|default(null) %}
+                    <img src="{{ media.src }}" alt="{{ media.alt }}">
+                {% else %}
+                    <div class="vf-demo-media" aria-hidden="true">
+                        <strong>Demo media</strong>
+                        <span>16:10</span>
+                    </div>
+                {% endif %}
+            </div>
+        {% endif %}
+    </div>
+{% endblock %}
diff --git a/site/templates/website/sections/_steps.html.twig b/site/templates/website/sections/_steps.html.twig
new file mode 100644
index 0000000..a8f9ef1
--- /dev/null
+++ b/site/templates/website/sections/_steps.html.twig
@@ -0,0 +1,25 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-steps') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = marker|default('steps') %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    <ol class="vf-steps" role="list" data-vf-layout="steps">
+        {% for step in steps %}
+            <li>
+                <span class="vf-steps__number">{{ loop.index }}</span>
+                <div>
+                    <h3 class="vf-h4">{{ step.title }}</h3>
+                    <p class="vf-text-muted">{{ step.text }}</p>
+                </div>
+            </li>
+        {% endfor %}
+    </ol>
+{% endblock %}
diff --git a/site/templates/website/sections/_text_list.html.twig b/site/templates/website/sections/_text_list.html.twig
new file mode 100644
index 0000000..91bbcc8
--- /dev/null
+++ b/site/templates/website/sections/_text_list.html.twig
@@ -0,0 +1,25 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-text-list') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = marker|default('text-list') %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        {% if eyebrow|default(null) %}<p class="vf-caption vf-text-muted">{{ eyebrow }}</p>{% endif %}
+        <h2 id="{{ labelled_by }}">{{ title }}</h2>
+        {% if text|default(null) %}<p class="vf-lead vf-text-muted">{{ text }}</p>{% endif %}
+    </div>
+
+    {% if items|default([]) %}
+        <ul class="vf-text-list" role="list" data-vf-layout="text-list">
+            {% for item in items %}
+                <li>
+                    {% if item.title|default(null) %}<h3 class="vf-h4">{{ item.title }}</h3>{% endif %}
+                    <p>{{ item.text }}</p>
+                    {% if item.href|default(null) %}<a href="{{ item.href }}">{{ item.link_label|default('Подробнее') }}</a>{% endif %}
+                </li>
+            {% endfor %}
+        </ul>
+    {% endif %}
+{% endblock %}
diff --git a/site/templates/website/sections/_typography_stress_showcase.html.twig b/site/templates/website/sections/_typography_stress_showcase.html.twig
new file mode 100644
index 0000000..c705b9b
--- /dev/null
+++ b/site/templates/website/sections/_typography_stress_showcase.html.twig
@@ -0,0 +1,35 @@
+{% extends 'website/sections/_base.html.twig' %}
+{% set section_class = 'vf-section--surface' %}
+{% set section_id = id|default('vf-typography-stress') %}
+{% set labelled_by = section_id ~ '-title' %}
+{% set section_name = 'typography-stress' %}
+
+{% block section_content %}
+    <div class="vf-section__heading">
+        <p class="vf-caption vf-text-muted">Typography stress test</p>
+        <h2 id="{{ labelled_by }}">Onest: кириллица, числа и длинные строки</h2>
+        <p class="vf-lead vf-text-muted">Техническая проверка реального production-шрифта без изменения typography scale.</p>
+    </div>
+
+    <div class="vf-typography-stress" data-vf-showcase="typography-stress">
+        <p class="vf-caption vf-text-muted">H1 / короткий</p>
+        <p class="vf-h1">Финансы без догадок</p>
+        <p class="vf-caption vf-text-muted">H1 / средний</p>
+        <p class="vf-h1">Финансовая система для устойчивого роста бизнеса</p>
+        <p class="vf-caption vf-text-muted">H1 / длинный</p>
+        <p class="vf-h1">Управляйте финансами бизнеса на основе данных, а не ощущения остатка на счёте</p>
+        <p class="vf-caption vf-text-muted">H2 / средний</p>
+        <p class="vf-h2">Планируйте движение денежных средств без кассовых разрывов</p>
+        <p class="vf-caption vf-text-muted">H2 / 100+ символов</p>
+        <p class="vf-h2">Собирайте план, факт и прогноз движения денег в одном управленческом контуре, чтобы обсуждать отклонения до принятия решения</p>
+        <p class="vf-caption vf-text-muted">H3 / средний</p>
+        <p class="vf-h3">Сверяйте план и факт по каждому направлению</p>
+        <p class="vf-caption vf-text-muted">H3 / 100+ символов</p>
+        <p class="vf-h3">Определите единые правила подготовки финансовых данных и регулярно проверяйте результат вместе с ответственными участниками</p>
+        <p class="vf-h4">Проверяйте отклонения до управленческой встречи</p>
+        <p class="vf-lead">Проверка символов: 2 450 000 ₽ · 18,5% · −7% · «план» (факт).</p>
+        <p>Аа Бб Вв Ёё Йй Фф Щщ Ъъ Ыы Ьь Ээ Юю Яя · 0123456789 · ₽ % № — «» () / + − =</p>
+        <p class="vf-small">Small: пояснение к данным за выбранный период.</p>
+        <p class="vf-caption">Caption: план / факт</p>
+    </div>
+{% endblock %}
diff --git a/site/tests/Website/MarketingSectionsTest.php b/site/tests/Website/MarketingSectionsTest.php
new file mode 100644
index 0000000..4a43ccf
--- /dev/null
+++ b/site/tests/Website/MarketingSectionsTest.php
@@ -0,0 +1,176 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Tests\Website;
+
+use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
+
+final class MarketingSectionsTest extends WebTestCase
+{
+    public function testMarketingSectionsCatalogRendersProductionPatterns(): void
+    {
+        $client = static::createClient();
+        $client->request('GET', '/ui-kit/sections');
+
+        self::assertResponseIsSuccessful();
+        self::assertSelectorCount(1, 'h1');
+        self::assertSelectorTextContains('h1', 'Готовые секции');
+        self::assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
+        self::assertSelectorExists('link[href^="/assets/website/components/showcase.css?v="]');
+        self::assertSelectorExists('nav a.active[href="/ui-kit/sections"]');
+
+        foreach ([
+            'hero',
+            'typography-stress',
+            'problem',
+            'benefits',
+            'steps',
+            'paths',
+            'case-preview',
+            'proof',
+            'quote',
+            'pricing',
+            'comparison',
+            'faq',
+            'lead-form',
+            'article-preview',
+            'cta',
+        ] as $section) {
+            self::assertSelectorExists(sprintf('[data-vf-section="%s"]', $section));
+        }
+
+        self::assertSelectorCount(3, '[data-vf-section="feature"]');
+        self::assertSelectorExists('[data-vf-section="feature"] [data-vf-media-position="left"]');
+        self::assertSelectorExists('[data-vf-section="feature"] [data-vf-media-position="right"]');
+        self::assertSelectorCount(2, '[data-vf-section="feature"] .vf-demo-media[aria-hidden="true"]');
+
+        self::assertSelectorCount(6, '#problem-section .vf-text-list > li');
+        self::assertSelectorCount(2, '#benefits-section .vf-marketing-grid__item');
+        self::assertSelectorCount(5, '#steps-section .vf-steps > li');
+        self::assertSelectorCount(2, '#paths-section .vf-paths > li');
+        self::assertSelectorCount(3, '#case-preview-section .vf-case-flow > div');
+        self::assertSelectorCount(4, '#pricing-section .vf-marketing-grid__item');
+        self::assertSelectorCount(2, '#article-preview-section .vf-marketing-grid__item');
+
+        self::assertSelectorExists('#problem-section .vf-text-list[role="list"]');
+        self::assertSelectorExists('#benefits-section .vf-marketing-grid[role="list"]');
+        self::assertSelectorExists('#steps-section .vf-steps[role="list"]');
+        self::assertSelectorCount(5, '#steps-section .vf-steps__number:not([aria-hidden])');
+        self::assertSelectorExists('#paths-section .vf-paths[role="list"]');
+
+        self::assertSelectorExists('[data-vf-layout="steps"]');
+        self::assertSelectorExists('[data-vf-layout="paths"]');
+        self::assertSelectorExists('[data-vf-layout="quote"] blockquote');
+        self::assertSelectorExists('[data-vf-layout="comparison"][role="region"][tabindex="0"]');
+        self::assertSelectorCount(4, '#comparison-section [data-vf-layout="comparison"] thead th[scope="col"]');
+        self::assertSelectorCount(8, '#comparison-section [data-vf-layout="comparison"] tbody th[scope="row"]');
+        self::assertSelectorCount(8, '#comparison-section [data-vf-layout="comparison"] tbody tr');
+
+        self::assertSelectorCount(6, '[data-vf-section="faq"] .accordion-item');
+        self::assertSelectorExists('[data-vf-section="faq"] [data-vf-component="accordion"]');
+        self::assertSelectorCount(1, '[data-vf-section="cta"] [data-vf-component="cta"]');
+    }
+
+    public function testMarketingSectionBoundaryContractsAreRendered(): void
+    {
+        $client = static::createClient();
+        $client->request('GET', '/ui-kit/sections');
+
+        self::assertResponseIsSuccessful();
+        self::assertSelectorCount(0, '#text-content-stress ul');
+        self::assertSelectorCount(3, '#problem-min-stress .vf-text-list > li');
+        self::assertSelectorCount(3, '#grid-three-stress .vf-marketing-grid__item');
+        self::assertSelectorCount(6, '#grid-six-stress .vf-marketing-grid__item');
+        self::assertSelectorCount(2, '#steps-min-stress .vf-steps > li');
+        self::assertSelectorCount(3, '#comparison-min-stress thead th[scope="col"]');
+        self::assertSelectorCount(3, '#comparison-min-stress tbody tr');
+        self::assertSelectorExists('#feature-no-media-stress .vf-split--without-media');
+        self::assertSelectorCount(0, '#feature-no-media-stress .vf-split__media');
+        self::assertSelectorCount(0, '#feature-no-media-stress [data-vf-component="button"]');
+    }
+
+    public function testLeadFormIsAccessibleDemoWithoutSubmission(): void
+    {
+        $client = static::createClient();
+        $client->request('GET', '/ui-kit/sections');
+
+        self::assertResponseIsSuccessful();
+        self::assertSelectorExists('[data-vf-demo-form]:not([action])');
+        self::assertSelectorCount(2, '[data-vf-demo-form] [data-vf-component="form-input"]');
+        self::assertSelectorCount(1, '[data-vf-demo-form] [data-vf-component="textarea"]');
+        self::assertSelectorCount(1, '[data-vf-demo-form] [data-vf-component="checkbox"]');
+        self::assertSelectorCount(4, '[data-vf-demo-form] label');
+        self::assertSelectorCount(0, '[data-vf-demo-form] button[type="submit"]');
+        self::assertSelectorCount(1, '[data-vf-demo-form] button[type="button"]');
+        self::assertSelectorTextContains('[data-vf-demo-form] .vf-small', 'обработчик отправки не подключён');
+    }
+
+    public function testOnestIsSelfHostedAndIsTheOnlyPrimaryWebsiteFont(): void
+    {
+        $font = $this->projectPath('public/assets/fonts/onest/Onest-Variable.woff2');
+        $license = $this->projectPath('public/assets/fonts/onest/OFL.txt');
+
+        self::assertFileExists($font);
+        self::assertSame('wOF2', substr($this->read($font), 0, 4));
+        self::assertGreaterThan(0, filesize($font));
+        self::assertFileExists($license);
+        self::assertStringContainsString('SIL OPEN FONT LICENSE Version 1.1', $this->read($license));
+
+        $baseCss = $this->read($this->projectPath('assets/styles/website/base.css'));
+        self::assertStringContainsString('@font-face', $baseCss);
+        self::assertStringContainsString('font-family: "Onest";', $baseCss);
+        self::assertStringContainsString('font-style: normal;', $baseCss);
+        self::assertStringContainsString('font-weight: 100 900;', $baseCss);
+        self::assertStringContainsString('font-display: swap;', $baseCss);
+        self::assertStringContainsString('url("/assets/fonts/onest/Onest-Variable.woff2")', $baseCss);
+
+        $tokensCss = $this->read($this->projectPath('assets/styles/website/tokens.css'));
+        self::assertStringContainsString('--vf-font-primary: "Onest", Arial, sans-serif;', $tokensCss);
+        foreach (['regular' => 400, 'medium' => 500, 'bold' => 700] as $name => $weight) {
+            self::assertStringContainsString(sprintf('--vf-font-weight-%s: %d;', $name, $weight), $tokensCss);
+        }
+
+        $activeTypography = $baseCss.$tokensCss.$this->read($this->siteRulesPath());
+        self::assertDoesNotMatchRegularExpression('/TT\s+Norms/i', $activeTypography);
+        self::assertDoesNotMatchRegularExpression('/fonts\.googleapis|fonts\.gstatic/i', $activeTypography);
+    }
+
+    public function testTypographyStressCasesUseTheProductionScaleWithoutForcedBreaks(): void
+    {
+        $client = static::createClient();
+        $client->request('GET', '/ui-kit/sections');
+
+        self::assertResponseIsSuccessful();
+        foreach (['vf-h1', 'vf-h2', 'vf-h3', 'vf-h4', 'vf-lead', 'vf-small', 'vf-caption'] as $class) {
+            self::assertSelectorExists(sprintf('[data-vf-showcase="typography-stress"] .%s', $class));
+        }
+        self::assertSelectorCount(3, '[data-vf-showcase="typography-stress"] .vf-h1');
+        self::assertSelectorCount(2, '[data-vf-showcase="typography-stress"] .vf-h2');
+        self::assertSelectorCount(2, '[data-vf-showcase="typography-stress"] .vf-h3');
+
+        self::assertSelectorTextContains('[data-vf-showcase="typography-stress"]', '₽');
+        self::assertSelectorTextContains('[data-vf-showcase="typography-stress"]', '%');
+        self::assertSelectorCount(0, '[data-vf-showcase="typography-stress"] br');
+    }
+
+    private function projectPath(string $relativePath): string
+    {
+        return dirname(__DIR__, 2).'/'.$relativePath;
+    }
+
+    private function siteRulesPath(): string
+    {
+        $local = dirname(__DIR__, 3).'/SITE_RULES.md';
+
+        return is_file($local) ? $local : '/workspace/SITE_RULES.md';
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
+```
