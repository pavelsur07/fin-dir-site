# Stage 1.5 — Migration Bootstrap → Tailwind CSS

## Проект

**Ваш Финдир**

## Статус

Техническое задание на промежуточный Stage 1.5.

Stage выполняется **до продолжения Stage 2**.

---

# 1. Причина Stage

На текущем этапе проекта уже существуют:

- `/ui-kit`;
- production Twig components Stage 1;
- базовые reusable sections;
- Color System;
- локально разработанная demo-страница marketing sections (`/ui-kit/sections` или текущий локальный эквивалент).

Полноценные production marketing pages ещё не разработаны.

Принято архитектурное решение:

```text
Bootstrap 5
→ удалить

Tailwind CSS
→ использовать как единственный CSS framework Public Website
```

Это оптимальный момент миграции, потому что объём публичного UI ещё ограничен.

---

# 2. Решение по локальной странице sections

Локальную страницу sections **НЕ откладывать**.

Она входит в Stage 1.5 как:

```text
migration consumer
+
regression target
```

Но Stage 1.5 **не продолжает разработку Stage 2**.

Разрешено:

- сохранить существующую локальную страницу sections;
- мигрировать её Bootstrap classes/layout на Tailwind;
- мигрировать используемые ей components;
- проверить responsive/accessibility;
- сохранить существующий визуальный и информационный результат.

Запрещено:

- добавлять новые marketing sections;
- менять contracts sections;
- улучшать маркетинговую архитектуру;
- добавлять новые варианты блоков;
- писать реальный контент;
- выполнять редизайн sections.

Иными словами:

```text
Stage 1.5
не развивает /ui-kit/sections,
а делает уже существующую локальную страницу совместимой с новой foundation.
```

---

# 3. Важное правило по локальным изменениям

Перед началом работы Codex обязан выполнить:

```bash
git status --short
git diff
```

и определить локальные незакоммиченные файлы.

Локальная работа по `/ui-kit/sections` является **частью согласованного scope Stage 1.5**.

Её наличие:

- не является конфликтом само по себе;
- не является причиной откатывать изменения;
- не является причиной удалять файлы;
- не должно приводить к `git reset`, checkout или восстановлению версии из `master`.

Codex обязан сохранить и мигрировать эти изменения.

Если обнаружены **другие несвязанные локальные изменения**, которые могут быть затронуты миграцией, действовать по обычным правилам `AGENTS.md`.

---

# 4. Цель Stage 1.5

Полностью удалить зависимость Public Website от Bootstrap и перевести существующую Website Foundation на Tailwind CSS без изменения согласованного дизайна.

После Stage:

```text
SITE_RULES.md
      ↓
Tailwind Theme / Design Tokens
      ↓
Twig Components
      ↓
Reusable Sections
      ↓
/ui-kit
      ↓
local /ui-kit/sections
```

Bootstrap не должен участвовать ни в CSS, ни в JavaScript, ни в responsive/layout поведении Public Website.

---

# 5. Главный принцип миграции

Это **framework migration, а не redesign**.

Не менять без необходимости:

- Brand Colors;
- Typography scale;
- Onest, если он уже подключён локальной работой Stage 2;
- Spacing System;
- container width;
- content width;
- border-radius;
- shadows;
- UI hierarchy;
- Component API;
- Section API;
- demo content;
- порядок sections;
- responsive intent.

Допускаются минимальные визуальные отличия, вызванные заменой framework defaults, но итог должен соответствовать `SITE_RULES.md`, а не дефолтному стилю Tailwind.

---

# 6. Scope Stage 1.5

Stage разбить на подэтапы:

```text
1.5.0 — Preflight / Local Work Safety
1.5.1 — Tailwind Build Foundation
1.5.2 — Tailwind Design Tokens
1.5.3 — Remove Bootstrap Runtime
1.5.4 — Migrate Base Layout
1.5.5 — Migrate UI Components
1.5.6 — Migrate Interactive Components
1.5.7 — Migrate Foundation Sections
1.5.8 — Migrate /ui-kit
1.5.9 — Migrate local /ui-kit/sections
1.5.10 — Remove Legacy Bootstrap CSS
1.5.11 — Tests / Static Rules
1.5.12 — Responsive / Visual Regression
1.5.13 — Accessibility
1.5.14 — Documentation
1.5.15 — Self-review / Claude Review / Report
```

Каждый подэтап должен быть завершён и проверен до закрытия Stage 1.5.

---

# 7. Stage 1.5.0 — Preflight / Local Work Safety

До изменения файлов:

1. полностью прочитать:
   - `AGENTS.md`;
   - `SITE_RULES.md`;
   - `docs/architecture/PATTERNS.md`;
2. проверить Git status;
3. определить committed Stage 1 files;
4. определить локальную Stage 2 work;
5. найти все использования Bootstrap внутри Public Website;
6. определить текущую asset build схему;
7. определить текущую JS-зависимость от Bootstrap;
8. зафиксировать baseline UI.

## 7.1. Найти Bootstrap usage

Проверить минимум:

```text
site/templates/website/
site/assets/styles/website/
site/public/assets/website/
SITE_RULES.md
AGENTS.md
Makefile
.github/workflows/
```

Искать:

```text
bootstrap
cdn.jsdelivr.net/npm/bootstrap
--bs-
btn
btn-primary
container
row
col-
navbar
navbar-
accordion
accordion-
collapse
data-bs-
```

Не считать само слово `.container` автоматически ошибкой — проверить реальное назначение.

## 7.2. Baseline screenshots

До миграции сохранить локальные screenshots минимум:

```text
/ui-kit
/ui-kit/sections
```

на:

```text
375px
768px
1024px
1440px
```

Screenshots используются только как regression reference.

Не добавлять их в production assets без необходимости.

## 7.3. Acceptance

- [ ] локальные Stage 2 files идентифицированы;
- [ ] ни один локальный файл не потерян;
- [ ] Bootstrap usage inventory подготовлен;
- [ ] baseline screenshots получены;
- [ ] границы миграции понятны.

---

# 8. Stage 1.5.1 — Tailwind Build Foundation

## 8.1. Версия

Использовать стабильную **Tailwind CSS 4.x**.

Точная версия должна быть:

- явно зафиксирована;
- воспроизводима;
- не использовать floating `latest`.

## 8.2. Build tool

Проект не должен получать сложный frontend stack только ради Tailwind.

Предпочтительный порядок:

### Вариант A — standalone Tailwind CLI

Использовать, если он нормально интегрируется в текущий Docker/CI workflow.

### Вариант B — минимальный Tailwind CLI через package manager

Допустим только если standalone вариант делает CI/development сложнее.

В этом случае разрешены только минимально необходимые build dependencies:

```text
tailwindcss
@tailwindcss/cli
```

Не добавлять без отдельной причины:

```text
Vite
Webpack
Encore
React
PostCSS stack
Sass
frontend framework
```

Выбор A/B зафиксировать в плане с кратким техническим обоснованием.

## 8.3. Runtime

Tailwind — build-time dependency.

Production page не должна:

- загружать Tailwind CDN;
- запускать Tailwind в browser;
- зависеть от Node runtime.

В production отдаётся готовый compiled CSS.

## 8.4. Source CSS

Создать единый понятный entry point, например:

```text
site/assets/styles/website/app.css
```

Конкретное имя можно адаптировать к текущей структуре, но должен быть **один основной Tailwind input**.

В нём:

```css
@import "tailwindcss";
```

и Tailwind Theme.

## 8.5. Source scanning

Source detection должен явно охватывать:

```text
site/templates/website/
```

и локальные Twig files sections.

Предпочтительно ограничить scan область Public Website, чтобы generated CSS не зависел от случайных строк во всём репозитории.

Не сканировать:

```text
site/public/
vendor/
generated CSS
```

без необходимости.

## 8.6. Generated output

Tailwind output должен попадать в public asset, например:

```text
site/public/assets/website/app.css
```

Generated CSS:

- не редактируется вручную;
- генерируется из source;
- проверяется на drift;
- участвует в cache/version strategy.

## 8.7. Make targets

Обновить Makefile.

Должны существовать понятные команды, например:

```text
make assets
make assets-watch
make assets-check
```

или эквивалент.

`make assets` должен полностью воспроизводимо собирать website CSS.

`make assets-check` должен определять stale generated output.

## 8.8. Acceptance

- [ ] Tailwind version pinned;
- [ ] Tailwind работает build-time;
- [ ] Tailwind CDN отсутствует;
- [ ] browser Tailwind runtime отсутствует;
- [ ] есть один primary Tailwind CSS input;
- [ ] Twig website sources сканируются;
- [ ] public/generated paths не создают build loop;
- [ ] generated CSS не редактируется вручную;
- [ ] `make assets` воспроизводим;
- [ ] `make assets-check` выявляет drift.

---

# 9. Stage 1.5.2 — Tailwind Design Tokens

## 9.1. Основной принцип

Не использовать стандартную Tailwind palette как дизайн-систему сайта.

Существующая VF Color System остаётся Source of Truth по значениям.

Tailwind должен выдавать utilities только для утверждённых project colors.

## 9.2. Custom Tailwind palette

Отключить default Tailwind color palette через Tailwind theme и определить только утверждённые цвета проекта.

Минимально сохранить:

```text
Brand Red           #B00020
Brand Red Hover     #8A0019
Brand Red Active    #6C0014
Brand Red Soft

Brand Dark          #0B1020
Dark Surface        #1E2331

Page Background
Surface
Text
Muted
Text on Dark
Muted on Dark

Focus
Focus on Dark

Border
Border Strong

Success
Success Soft
Warning
Warning Soft
Danger
Danger Soft
Info
Info Soft
```

Не использовать:

```text
red-500
blue-600
slate-700
gray-100
```

как случайные Tailwind defaults.

## 9.3. Token naming

Выбрать semantic Tailwind names, например:

```text
bg-brand-red
bg-brand-dark
bg-surface-dark
bg-page
bg-surface

text-content
text-muted
text-on-dark

border-default
border-strong

text-success
bg-success-soft
```

Конкретное naming согласовать с существующим `SITE_RULES.md`.

Главное:

- имя отражает роль;
- не появляется parallel duplicated token system;
- источник значения однозначен.

## 9.4. Не держать две независимые системы tokens

Не должно существовать ситуации:

```text
--vf-color-primary = X

и отдельно

--color-primary = Y
```

где значения могут расходиться.

Допустимо создать compatibility alias только если он нужен для перехода, но:

- alias должен ссылаться на canonical token;
- не содержать отдельное значение;
- transitional alias должен быть документирован;
- по возможности удалить его в рамках Stage.

## 9.5. Spacing

Сохранить утверждённую шкалу:

```text
4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 80 / 96
```

Для numeric Tailwind spacing использовать только соответствующие классы:

```text
1
2
3
4
6
8
12
16
20
24
```

Дополнительно разрешён `0`.

Не использовать произвольные:

```text
p-5
gap-7
mt-11
px-[37px]
```

только потому, что Tailwind позволяет это сделать.

## 9.6. Arbitrary values

Запретить Tailwind arbitrary values:

```text
[37px]
[#123456]
[calc(...)]
```

по умолчанию.

Разрешение возможно только для технического случая, который:

1. нельзя выразить theme token;
2. имеет обоснование;
3. зафиксирован в `SITE_RULES.md`.

## 9.7. Typography

Сохранить текущую typography system.

Если Onest уже присутствует в локальной Stage 2 работе:

- сохранить;
- не откатывать к TT Norms;
- подключить в Tailwind theme.

Если Onest ещё не подключён, Stage 1.5 не должен самовольно расширять scope, если font migration не является необходимой частью уже существующих локальных изменений.

## 9.8. Breakpoints

Сохранить согласованное responsive поведение Stage 1.

Текущие контрольные точки:

```text
576
768
992
1200
1400
```

Tailwind breakpoints должны быть настроены так, чтобы существующий responsive intent не изменился случайно из-за Tailwind defaults.

Не принимать default Tailwind breakpoints молча.

## 9.9. Container

Сохранить:

```text
container max ≈ 1200px
content max ≈ 720px
mobile gutter ≈ 16px
tablet+ gutter ≈ 24px
```

Не использовать default Tailwind `.container` без проверки его поведения.

Предпочтительно создать понятный reusable website container pattern.

## 9.10. Acceptance

- [ ] default Tailwind color palette не используется Public Website;
- [ ] VF palette перенесена;
- [ ] third brand accent не появился;
- [ ] tokens имеют semantic naming;
- [ ] duplicated independent token systems отсутствуют;
- [ ] spacing scale сохранена;
- [ ] arbitrary spacing отсутствует;
- [ ] arbitrary colors отсутствуют;
- [ ] typography сохранена;
- [ ] current font не откатан;
- [ ] breakpoints явно настроены;
- [ ] container behaviour сохранено.

---

# 10. Stage 1.5.3 — Remove Bootstrap Runtime

Полностью удалить Bootstrap из Public Website runtime.

Удалить:

```text
Bootstrap CSS CDN
Bootstrap JS bundle CDN
SRI Bootstrap links
data-bs-* attributes
Bootstrap CSS variables
Bootstrap JS behaviour
```

Не оставлять Bootstrap «на всякий случай».

## 10.1. Проверка

В active Public Website не должно быть:

```text
bootstrap.min.css
bootstrap.bundle.min.js
--bs-
data-bs-
```

После миграции Bootstrap package/CDN не должен загружаться browser network.

---

# 11. Stage 1.5.4 — Migrate Base Layout

Обновить:

```text
site/templates/website/layouts/base.html.twig
```

или текущий production website layout.

## 11.1. Layout должен

- подключать только compiled Tailwind website CSS;
- не подключать Bootstrap;
- сохранять metadata;
- сохранять skip link;
- сохранять blocks Twig;
- сохранять Navbar/Footer architecture;
- сохранять asset version/cache-busting.

## 11.2. Body/Base styles

Перенести base styles через Tailwind theme/base layer.

Не создавать большой replacement `base.css`, который просто переписывает Bootstrap вручную.

Custom base styles оставить только для:

- typography defaults;
- focus baseline;
- body;
- semantic elements;
- genuinely global rules.

## 11.3. Acceptance

- [ ] layout без Bootstrap;
- [ ] один website compiled CSS;
- [ ] skip link работает;
- [ ] metadata не потеряны;
- [ ] cache-busting работает;
- [ ] base CSS минимален.

---

# 12. Stage 1.5.5 — Migrate UI Components

Мигрировать существующие production components.

Минимум:

```text
Button
Card
Badge
Alert
Input
Select
Textarea
Checkbox
Breadcrumb
Navbar
Footer
CTA
```

Accordion рассматривается отдельно как interactive component.

## 12.1. Twig components сохранить

Tailwind не является причиной отказаться от Twig partials.

Production component продолжает быть single implementation.

Например:

```text
_button.html.twig
_card.html.twig
_alert.html.twig
```

остаются reusable component API.

## 12.2. Utility-first

Основной style components должен задаваться Tailwind utilities непосредственно в production Twig component.

Не создавать заново Bootstrap-подобный CSS layer:

```css
.vf-btn {}
.vf-card {}
.vf-alert {}
```

только чтобы спрятать Tailwind classes.

Допустим небольшой semantic CSS, если utilities объективно не решают задачу чисто.

## 12.3. Variant maps

Для component variants использовать **полные статические class strings**.

Не создавать динамические Tailwind class fragments:

```twig
bg-{{ color }}-500
text-{{ tone }}
```

Tailwind должен видеть полный classname в source.

## 12.4. Component API

Сохранить существующие входные параметры, если нет реальной причины менять.

Не ломать локальную `/ui-kit/sections` из-за ненужного переименования component parameters.

## 12.5. States

Сохранить:

```text
Default
Hover
Focus
Active
Disabled
Error
Success
```

где применимо.

## 12.6. Acceptance

- [ ] все существующие components работают;
- [ ] Bootstrap classes удалены;
- [ ] Tailwind utilities используются;
- [ ] component API не сломан без необходимости;
- [ ] dynamic Tailwind classname construction отсутствует;
- [ ] states сохранены;
- [ ] UI-kit использует production components.

---

# 13. Stage 1.5.6 — Interactive Components

Tailwind сам по себе не предоставляет JavaScript behaviour.

Поэтому Bootstrap JS нельзя просто удалить без замены поведения.

## 13.1. Accordion / FAQ

Предпочтительно использовать native HTML:

```text
details / summary
```

если оно удовлетворяет:

- accessibility;
- keyboard;
- требуемому поведению;
- визуальной системе.

Не добавлять JS только ради accordion, если native solution достаточен.

## 13.2. Navbar mobile menu

После удаления Bootstrap Collapse mobile menu должно продолжить работать.

Выбрать минимальный вариант:

### Prefer

native HTML behaviour, если UX корректен.

### Otherwise

небольшой project-owned vanilla JS.

JS должен:

- отвечать только за open/close;
- обновлять `aria-expanded`;
- не содержать styling logic;
- не добавлять framework;
- не зависеть от Bootstrap.

## 13.3. Запрещено

Не добавлять ради двух интерактивных компонентов:

```text
Alpine.js
Flowbite
DaisyUI
Headless UI
React
Vue
новый JS framework
```

без отдельного архитектурного решения.

## 13.4. Acceptance

- [ ] Accordion работает без Bootstrap JS;
- [ ] Navbar mobile работает без Bootstrap JS;
- [ ] keyboard interaction работает;
- [ ] aria state корректен;
- [ ] лишняя JS dependency не добавлена.

---

# 14. Stage 1.5.7 — Migrate Foundation Sections

Мигрировать существующие:

```text
Section Base
Hero
Content Section
CTA Section
```

и другие уже committed Stage 1 sections.

## 14.1. Не менять section contracts

Не переизобретать:

```text
Hero API
CTA API
Content API
```

если текущий contract подходит.

## 14.2. Dark variants

Сохранить:

```text
Dark Hero
Dark Footer
Brand Dark
Dark Surface
```

без изменения Color System.

## 14.3. Acceptance

- [ ] sections не используют Bootstrap grid;
- [ ] dark variants сохранены;
- [ ] container сохранён;
- [ ] spacing сохранён;
- [ ] mobile layout соответствует baseline.

---

# 15. Stage 1.5.8 — Migrate `/ui-kit`

Полностью мигрировать committed `/ui-kit` на новую foundation.

`/ui-kit` остаётся визуальным Source of Truth.

## 15.1. Содержимое сохранить

Минимум:

```text
Typography
Colors
Spacing
Buttons
Cards
Badges
Alerts
Forms
Accordion
Breadcrumb
Navbar
Hero
Content
CTA
Footer
```

## 15.2. Не удалять состояния ради упрощения

UI-kit должен продолжать показывать:

```text
Default
Hover
Focus
Active
Disabled
Error
Success
```

где применимо.

## 15.3. Showcase CSS

Showcase-only style разрешён, но:

- не должен быть framework внутри framework;
- не должен попадать в production components;
- должен использовать Tailwind theme/tokens;
- не должен вводить собственную palette.

## 15.4. Acceptance

- [ ] `/ui-kit` HTTP 200;
- [ ] все Stage 1 components представлены;
- [ ] Bootstrap network requests = 0;
- [ ] Bootstrap classes = 0 в active UI-kit;
- [ ] visual hierarchy сохранена;
- [ ] state demos работают.

---

# 16. Stage 1.5.9 — Migrate local `/ui-kit/sections`

Этот пункт обязателен, потому что страница уже существует локально.

## 16.1. Scope

Мигрировать только существующее состояние локальной страницы.

Не:

- добавлять sections;
- удалять sections ради упрощения;
- менять demo content;
- менять contracts;
- менять порядок blocks без необходимости;
- выполнять redesign.

## 16.2. Reuse

Если локальная страница использует:

```text
Hero
Button
Card
Accordion
Form
CTA
Grid
Container
```

она должна после миграции использовать уже Tailwind-migrated production components.

Не писать второй Tailwind implementation специально для `/ui-kit/sections`.

## 16.3. Bootstrap cleanup

Удалить Bootstrap classes из локальных marketing sections:

```text
row
col-*
g-*
d-*
align-items-*
justify-content-*
```

и другие Bootstrap utilities.

Заменить Tailwind layout primitives:

```text
grid
flex
gap
responsive variants
```

в соответствии с `SITE_RULES.md`.

## 16.4. Acceptance

- [ ] локальная page сохранена;
- [ ] все существующие sections рендерятся;
- [ ] новый section не добавлен;
- [ ] Bootstrap classes отсутствуют;
- [ ] production components переиспользуются;
- [ ] page визуально сопоставима с baseline;
- [ ] 375/768/1024/1440 работают.

---

# 17. Stage 1.5.10 — Remove Legacy Bootstrap CSS

После успешной миграции удалить больше не нужные source CSS правила.

Не оставлять:

```text
старые Bootstrap overrides
--bs-* mapping
component CSS, написанный только для Bootstrap
Bootstrap-specific responsive rules
```

## 17.1. Не удалить полезные design rules

Перед удалением определить, относится правило к:

```text
Design Token
Base rule
Component rule
Showcase-only rule
Bootstrap override
```

Design intent должен быть перенесён в Tailwind перед удалением старого CSS.

## 17.2. Acceptance

- [ ] Bootstrap override CSS отсутствует;
- [ ] `--bs-*` отсутствуют;
- [ ] dead CSS удалён;
- [ ] Tailwind не сосуществует с Bootstrap compatibility layer.

---

# 18. Stage 1.5.11 — Static Rules / Tests

Обновить текущий `WebsiteFoundationTest` и другие website checks.

## 18.1. Проверять отсутствие Bootstrap

Автоматически проверять active Public Website на:

```text
bootstrap
data-bs-
--bs-
```

Разрешить слово Bootstrap только в исторической документации/отчётах, если такие файлы не являются active rules/runtime.

## 18.2. Tailwind arbitrary classes

Добавить проверки/правила против:

```text
[#...]
[37px]
arbitrary color
arbitrary spacing
```

Минимум для `templates/website`.

Не пытаться написать чрезмерно сложный parser Tailwind; проверка должна ловить очевидные нарушения без ложного ощущения полной гарантии.

## 18.3. Dynamic class construction

Проверять patterns типа:

```text
bg-{{ ...
text-{{ ...
border-{{ ...
```

Они запрещены.

Использовать explicit static maps.

## 18.4. Generated CSS

Проверить:

```text
source → build → public output
```

и отсутствие stale output.

## 18.5. Existing tests

Сохранить проверки:

```text
GET /ui-kit → 200
GET /ui-kit/sections → 200
```

если второй route уже присутствует в локальной работе.

## 18.6. Acceptance

- [ ] tests ловят Bootstrap runtime;
- [ ] tests ловят data-bs;
- [ ] tests ловят obvious arbitrary Tailwind values;
- [ ] tests ловят dynamic Tailwind fragments;
- [ ] asset drift проверяется;
- [ ] existing functional tests проходят.

---

# 19. Stage 1.5.12 — Responsive / Visual Regression

Проверить обе страницы:

```text
/ui-kit
/ui-kit/sections
```

на:

```text
375px
768px
1024px
1440px
```

## 19.1. Проверить

- no horizontal overflow;
- navbar;
- forms;
- buttons;
- cards;
- grids;
- accordion;
- Hero;
- CTA;
- dark variants;
- typography wrapping;
- section spacing;
- content width;
- media placeholders local sections.

## 19.2. Visual comparison

Сравнить с baseline Stage 1.5.0.

Не требовать пиксельного совпадения Bootstrap browser defaults.

Требуется совпадение по design intent:

```text
hierarchy
spacing
colors
typography
width
component states
responsive behaviour
```

Если появляется значимое отличие — задокументировать и исправить либо обосновать.

---

# 20. Stage 1.5.13 — Accessibility

Повторно проверить после удаления Bootstrap JS/CSS:

- keyboard navigation;
- skip link;
- visible focus;
- mobile menu;
- Accordion;
- form labels;
- disabled state;
- error/success;
- one H1;
- heading hierarchy;
- color contrast;
- semantic HTML.

## 20.1. Lighthouse

Запустить accessibility audit.

Не утверждать PASS, если фактически не запускался.

## 20.2. Acceptance

- [ ] keyboard navigation работает;
- [ ] navbar mobile keyboard accessible;
- [ ] accordion accessible;
- [ ] visible focus сохранён;
- [ ] forms accessible;
- [ ] Lighthouse не содержит критических accessibility ошибок.

---

# 21. Stage 1.5.14 — Update `SITE_RULES.md`

Полностью заменить Bootstrap-specific правила на Tailwind rules.

## 21.1. Technology base

Зафиксировать:

```text
Symfony
Twig
Tailwind CSS 4.x
minimal JavaScript only where native HTML is insufficient
```

Bootstrap удалить как разрешённую технологию.

## 21.2. Tailwind rules

Зафиксировать:

- Tailwind единственный CSS framework;
- project palette only;
- approved spacing scale only;
- arbitrary values forbidden by default;
- dynamic classname construction forbidden;
- full static variant maps;
- custom CSS only when utility solution objectively insufficient;
- no Tailwind plugin without concrete need;
- no component library on top of Tailwind without separate approval.

## 21.3. Component creation algorithm

Обновить алгоритм:

```text
1. Есть ли существующий VF Component?
2. Есть ли существующий VF Section/Layout Pattern?
3. Можно ли решить через Tailwind utilities внутри существующего component?
4. Можно ли расширить существующий component без смены смысла?
5. Только после этого создавать новый component/pattern.
```

Bootstrap больше не должен быть первым пунктом.

## 21.4. Custom CSS rule

Зафиксировать:

> Tailwind migration не означает запрет CSS вообще.

Custom CSS допустим для:

- `@font-face`;
- theme/base foundation;
- сложного native control styling;
- accessibility behavior;
- случая, который utilities выражают существенно хуже.

Но custom CSS нельзя использовать как привычный способ писать component stylesheet вместо Tailwind.

---

# 22. Update `AGENTS.md`

Точечно изменить Public Website / Technology Stack.

Заменить разрешение:

```text
Bootstrap 5
```

на:

```text
Tailwind CSS 4.x
```

Зафиксировать:

- Tailwind разрешён для Public Website;
- точная версия должна быть pinned;
- `SITE_RULES.md` остаётся Source of Truth;
- Bootstrap запрещён для нового/изменяемого Public Website code.

Не копировать весь `SITE_RULES.md` в `AGENTS.md`.

---

# 23. Build / CI

CI должен гарантировать:

```text
Tailwind build
→ generated CSS
→ lint/test
```

## 23.1. CI requirements

- Tailwind exact version reproducible;
- build command одинаков для local и CI;
- build не зависит от CDN runtime;
- generated output проверяется;
- build failure останавливает CI;
- stale output ловится.

## 23.2. Production

Production runtime не требует Node/Tailwind CLI.

Production получает уже собранный CSS внутри release/image.

Не изменять production infrastructure шире необходимого для доставки compiled CSS.

---

# 24. Definition of Done — Stage 1.5

Stage считается завершённым только если выполнены все применимые пункты.

## Local work

- [ ] local `/ui-kit/sections` сохранён;
- [ ] local files не потеряны;
- [ ] unrelated local work не затронут.

## Tailwind foundation

- [ ] Tailwind CSS 4.x установлен;
- [ ] exact version pinned;
- [ ] build reproducible;
- [ ] Tailwind используется build-time;
- [ ] production browser Tailwind runtime отсутствует;
- [ ] Tailwind CDN отсутствует.

## Bootstrap removal

- [ ] Bootstrap CSS не загружается;
- [ ] Bootstrap JS не загружается;
- [ ] `data-bs-*` отсутствуют;
- [ ] `--bs-*` отсутствуют;
- [ ] Bootstrap classes удалены из active website templates;
- [ ] Bootstrap compatibility CSS отсутствует.

## Design System

- [ ] existing Brand Colors сохранены;
- [ ] default Tailwind colors не используются;
- [ ] semantic project palette настроена;
- [ ] spacing scale сохранена;
- [ ] arbitrary colors отсутствуют;
- [ ] arbitrary spacing отсутствует;
- [ ] typography не сломана;
- [ ] Onest, если уже локально подключён, сохранён;
- [ ] breakpoints настроены явно;
- [ ] container/content width сохранены.

## Components

- [ ] Button мигрирован;
- [ ] Card мигрирован;
- [ ] Badge мигрирован;
- [ ] Alert мигрирован;
- [ ] Input мигрирован;
- [ ] Select мигрирован;
- [ ] Textarea мигрирован;
- [ ] Checkbox мигрирован;
- [ ] Breadcrumb мигрирован;
- [ ] Navbar мигрирован;
- [ ] Footer мигрирован;
- [ ] CTA мигрирован;
- [ ] Accordion работает без Bootstrap;
- [ ] component APIs не сломаны без причины;
- [ ] dynamic Tailwind fragments отсутствуют.

## Foundation sections

- [ ] Hero мигрирован;
- [ ] Content Section мигрирован;
- [ ] CTA Section мигрирован;
- [ ] Dark Hero сохранён;
- [ ] Dark Footer сохранён.

## UI-kit

- [ ] `/ui-kit` HTTP 200;
- [ ] все required components отображаются;
- [ ] states отображаются;
- [ ] visual hierarchy сохранена;
- [ ] Bootstrap requests = 0.

## Local Sections Demo

- [ ] `/ui-kit/sections` или локальный route работает;
- [ ] существующие marketing sections сохранены;
- [ ] новые sections не добавлены;
- [ ] existing demo content не переписывался без необходимости;
- [ ] Bootstrap classes удалены;
- [ ] Tailwind-migrated production components переиспользуются.

## Responsive

- [ ] `/ui-kit` проверен на 375;
- [ ] `/ui-kit` проверен на 768;
- [ ] `/ui-kit` проверен на 1024;
- [ ] `/ui-kit` проверен на 1440;
- [ ] sections page проверена на 375;
- [ ] sections page проверена на 768;
- [ ] sections page проверена на 1024;
- [ ] sections page проверена на 1440;
- [ ] horizontal overflow отсутствует.

## Accessibility

- [ ] skip link работает;
- [ ] visible focus работает;
- [ ] Navbar keyboard accessible;
- [ ] Accordion keyboard accessible;
- [ ] forms accessible;
- [ ] critical Lighthouse Accessibility failures отсутствуют.

## Tests

- [ ] Tailwind asset build проходит;
- [ ] asset drift check проходит;
- [ ] Bootstrap static scan проходит;
- [ ] dynamic Tailwind class scan проходит;
- [ ] functional tests проходят;
- [ ] existing project tests проходят;
- [ ] `make ci` проходит.

## Documentation

- [ ] `SITE_RULES.md` обновлён;
- [ ] `AGENTS.md` обновлён;
- [ ] Bootstrap больше не указан как разрешённый UI framework;
- [ ] Tailwind rules зафиксированы.

## Review

- [ ] full diff self-review выполнен;
- [ ] blocker/high исправлены;
- [ ] Claude Code review выполнен;
- [ ] blocker/high external review исправлены;
- [ ] final tests повторно запущены;
- [ ] Stage Report подготовлен.

---

# 25. Обязательные проверки поиска после миграции

В active Public Website выполнить поиск.

Ожидается **0 runtime occurrences**:

```bash
grep -R "cdn.jsdelivr.net/npm/bootstrap" site/templates/website site/assets/styles/website
grep -R "data-bs-" site/templates/website
grep -R -- "--bs-" site/assets/styles/website site/templates/website
```

Дополнительно проверить Bootstrap utility/component classes.

Не использовать только grep как единственное доказательство корректной миграции — требуется browser/regression проверка.

---

# 26. Stage Report

Финальный отчёт должен содержать:

```text
1. Какой Tailwind build вариант выбран и почему
2. Какая exact Tailwind version зафиксирована
3. Какие Bootstrap dependencies удалены
4. Какие Design Tokens перенесены
5. Какие Components мигрированы
6. Как реализованы Accordion и mobile Navbar
7. Что изменено в /ui-kit
8. Что изменено в local /ui-kit/sections
9. Какие локальные Stage 2 files были сохранены
10. Responsive results
11. Accessibility results
12. Visual regression results
13. Tests / make ci
14. Self-review
15. Claude Code review
16. Known limitations
```

---

# 27. Что НЕ делать в Stage 1.5

Запрещено расширять scope за framework migration.

Не:

- продолжать Stage 2;
- создавать новые sections;
- создавать production Главную;
- менять маркетинговый контент;
- создавать CMS;
- добавлять UI component library поверх Tailwind;
- добавлять DaisyUI;
- добавлять Flowbite;
- добавлять второй CSS framework;
- добавлять новый JS framework;
- менять Brand Colors;
- менять Typography scale без реальной regression-причины;
- массово переписывать Twig architecture;
- выполнять unrelated backend refactoring.

---

# 28. Главный критерий успеха

После Stage 1.5 пользователь визуально должен получить **тот же утверждённый UI-kit и ту же локальную библиотеку sections**, но технически:

```text
Bootstrap = 0

Tailwind = единственный CSS framework

Design System = сохранена

Production components = переиспользуются

Stage 2 можно продолжать без второй миграции
```

---

# 29. Gate перед продолжением Stage 2

Stage 2 не продолжать, пока одновременно не выполнено:

```text
/ui-kit green
/ui-kit/sections green
responsive green
accessibility green
make ci green
Bootstrap runtime = 0
self-review green
Claude review green
```

После этого продолжить Stage 2 уже только на Tailwind foundation.
