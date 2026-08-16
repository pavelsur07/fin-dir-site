# Stage 1 — Website Foundation / Design System

## Проект

**Ваш Финдир**

## Назначение документа

Этот документ — техническое задание на Stage 1 разработки публичного сайта проекта «Ваш Финдир».

Цель Stage 1 — не разработать полноценную главную страницу, а создать устойчивый фундамент сайта: правила дизайна, структуру шаблонов, design tokens, UI-компоненты, базовые секции, UI-kit и критерии качества.

После завершения Stage 1 новые страницы и секции должны добавляться без переписывания существующих правил дизайна и без появления хаотичных page-specific решений.

---

# 1. Цель Stage

Создать технический фундамент публичного сайта «Ваш Финдир», на котором можно дальше собирать страницы без:

- копирования верстки;
- появления уникальных CSS-правил для каждой страницы;
- произвольных цветов, размеров, радиусов и отступов;
- дублирования компонентов;
- появления визуально разных решений для одинаковых задач;
- переписывания существующих страниц при развитии сайта;
- постепенного превращения сайта в набор несвязанных между собой шаблонов;
- типовых AI-generated UI паттернов.

**Результат Stage 1:** готовая дизайн-система, структура шаблонов, UI-kit и формализованные правила разработки.

---

# 2. Scope Stage 1

На этом этапе **не разрабатываем полноценную главную страницу сайта**.

Должны быть сделаны:

1. `SITE_RULES.md`;
2. изменение `AGENTS.md` со ссылкой на `SITE_RULES.md`;
3. Design Tokens;
4. Typography system;
5. Spacing system;
6. базовая структура Twig;
7. базовая структура CSS/JS;
8. базовые UI Components;
9. Section architecture;
10. Container/Grid rules;
11. Responsive rules;
12. Accessibility baseline;
13. `/ui-kit`;
14. AI-generated UI anti-pattern rules;
15. автоматические проверки;
16. тесты;
17. self-review;
18. внешнее review по действующим правилам проекта;
19. итоговый Stage Report.

---

# 3. SITE_RULES.md

Создать файл:

```text
SITE_RULES.md
```

`SITE_RULES.md` является **Source of Truth для публичного сайта**.

Все изменения публичного сайта должны соответствовать этому файлу.

Если реализация требует решения, которого нет в `SITE_RULES.md`, разработчик не должен молча создавать новый визуальный паттерн.

Сначала необходимо:

1. проверить существующие Bootstrap-компоненты;
2. проверить существующие VF-компоненты;
3. проверить существующие VF-section patterns;
4. определить, можно ли расширить существующий компонент;
5. только после этого создавать новый компонент или новый визуальный паттерн.

Если новый паттерн действительно необходим, соответствующее правило должно быть добавлено в `SITE_RULES.md`.

---

## 3.1. Технологическая база

Зафиксировать:

```text
Symfony
Twig
Bootstrap 5
CSS
JavaScript — только при необходимости интерактивности
```

### Правила

- Bootstrap 5 является базовым UI framework.
- Не подключать дополнительный UI framework.
- Не подключать CSS framework только ради одного компонента.
- Не создавать собственный JavaScript-компонент, если задача корректно решается средствами Bootstrap.
- JavaScript использовать только для функциональной интерактивности.
- Декоративная анимация сама по себе не является достаточной причиной для добавления JS.
- Не добавлять зависимости без необходимости.

### Проверка

- [ ] существует `SITE_RULES.md`;
- [ ] Symfony, Twig, Bootstrap 5, CSS и JS перечислены как технологическая база;
- [ ] явно прописан запрет второго UI framework;
- [ ] явно прописано правило минимизации JS;
- [ ] в `AGENTS.md` имеется обязательная ссылка на `SITE_RULES.md`.

---

# 4. Design Tokens

Все базовые визуальные значения должны иметь единый источник.

Минимально определить:

```text
colors
typography
spacing
containers
border-radius
borders
shadows
breakpoints
```

Пример структуры:

```css
--vf-color-primary
--vf-color-primary-hover
--vf-color-text
--vf-color-muted
--vf-color-background
--vf-color-surface
--vf-color-border
--vf-color-success
--vf-color-warning
--vf-color-danger

--vf-space-1
--vf-space-2
--vf-space-3
...

--vf-radius-sm
--vf-radius-md
--vf-radius-lg

--vf-container-max
```

Не требуется создавать сотни токенов.

Нужен минимальный, понятный и реально используемый набор.

---

## 4.1. Правило использования токенов

Запрещается на уровне отдельных страниц или компонентов писать произвольные значения, если соответствующая характеристика уже является частью Design System.

Пример запрещенного подхода:

```css
padding: 37px;
border-radius: 13px;
color: #24354a;
```

если эти значения могут быть выражены существующими токенами.

---

## 4.2. Проверка Design Tokens

- [ ] существует отдельный файл `tokens.css`;
- [ ] основные цвета определены токенами;
- [ ] typography определена централизованно;
- [ ] spacing определен централизованно;
- [ ] radius определен централизованно;
- [ ] container widths определены централизованно;
- [ ] breakpoints имеют единое правило;
- [ ] UI-kit использует реальные токены;
- [ ] основные production components используют токены;
- [ ] поиск по CSS не выявляет дублирующиеся произвольные значения вместо уже существующих токенов.

---

# 5. Typography System

Типографика должна быть полностью определена в `SITE_RULES.md` и Design Tokens.

## 5.1. Основной шрифт

Референс — типографика сайта `https://tochka.com/`.

Для проекта использовать:

```text
Primary font: TT Norms Pro

Fallback:
"TT Norms Pro", "TT Norms", Arial, sans-serif
```

Используемые веса:

```text
400 — Regular
500 — Medium
700 — Bold
```

Не использовать большее количество начертаний без отдельной необходимости.

### Важно

- не копировать файлы шрифтов с `tochka.com`;
- не hotlink'ить webfont с чужих CDN;
- использовать только легально полученные файлы шрифта;
- до подключения webfont должно быть подтверждено право его использования на сайте;
- при отсутствии лицензированных файлов шрифта архитектура Typography System всё равно должна быть готова, а fallback должен работать корректно.

---

## 5.2. Базовая типографическая шкала

Начальная шкала:

```text
H1:
desktop 56px / 60px
mobile  40px / 44px

H2:
desktop 40px / 44px
mobile  32px / 36px

H3:
desktop 32px / 36px
mobile  24px / 30px

H4:
desktop 24px / 30px
mobile  20px / 26px

Lead:
desktop 20px / 30px
mobile  18px / 28px

Body:
16px / 24px

Small:
14px / 20px

Caption:
12px / 16px
```

Разрешено корректировать шкалу в процессе Stage 1 только если изменение применяется системно ко всей Typography System и отражается в `SITE_RULES.md`.

---

## 5.3. Правила Typography

- `font-family`, `font-size`, `line-height`, `font-weight` задаются централизованно;
- страницы не должны самостоятельно создавать новые размеры текста;
- новый text style нельзя создавать, если существующий решает ту же задачу;
- H1 используется для основного заголовка страницы;
- на обычной публичной странице должен быть один основной H1;
- визуальная иерархия должна соответствовать смысловой HTML-иерархии;
- не использовать размер текста как декоративный прием без смысловой причины;
- длинный текст не должен растягиваться на всю ширину desktop-контейнера.

---

## 5.4. Проверка Typography

На `/ui-kit` одновременно вывести:

```text
H1
H2
H3
H4
Lead
Body
Small
Caption

Regular
Medium
Bold
```

Acceptance:

- [ ] основной font-family задан через token;
- [ ] fallback определен;
- [ ] H1–H4 представлены на UI-kit;
- [ ] Lead представлен;
- [ ] Body представлен;
- [ ] Small представлен;
- [ ] Caption представлен;
- [ ] используемые font-weight представлены;
- [ ] отсутствуют альтернативные случайные размеры для тех же задач;
- [ ] Typography корректно перестраивается на mobile;
- [ ] отсутствие webfont не ломает layout.

---

# 6. Spacing System

Использовать единую spacing scale.

Базовая единица:

```text
4px
```

Разрешенная шкала:

```text
4
8
12
16
24
32
48
64
80
96
```

Соответствующие значения должны быть представлены токенами.

Пример:

```css
--vf-space-1: 4px;
--vf-space-2: 8px;
--vf-space-3: 12px;
--vf-space-4: 16px;
--vf-space-6: 24px;
--vf-space-8: 32px;
--vf-space-12: 48px;
--vf-space-16: 64px;
--vf-space-20: 80px;
--vf-space-24: 96px;
```

---

## 6.1. Базовые правила расстояний

Начальные правила:

```text
icon ↔ text:
8px

button ↔ button:
12px

form field ↔ form field:
16px

title ↔ description:
16px

обычные content blocks:
24px / 32px

cards grid gap:
24px

card padding:
desktop 24px
mobile  16px

section vertical padding:
desktop 80px
tablet  64px
mobile  48px
```

Эти значения являются базовыми правилами, а не поводом создавать отдельные значения для каждой страницы.

---

## 6.2. Правила Spacing

- расстояния должны использовать утвержденную spacing scale;
- разные страницы не должны иметь собственную spacing system;
- section spacing задается системно;
- card padding задается системно;
- form spacing задается системно;
- grid gap задается системно;
- новый spacing token создается только если существующая шкала действительно не решает задачу;
- запрещено использовать произвольные `37px`, `53px`, `71px` и аналогичные значения только ради визуальной подгонки.

---

## 6.3. Проверка Spacing

На `/ui-kit` должна быть отдельная визуальная секция `Spacing`.

Она должна показывать:

```text
4
8
12
16
24
32
48
64
80
96
```

Также показать:

- card padding;
- form gaps;
- grid gaps;
- section vertical spacing.

Acceptance:

- [ ] spacing tokens существуют;
- [ ] все значения spacing scale представлены на UI-kit;
- [ ] Hero использует стандартные section spacing rules;
- [ ] CTA использует стандартные section spacing rules;
- [ ] Cards используют утвержденный padding;
- [ ] Forms используют утвержденные gaps;
- [ ] отсутствуют необоснованные произвольные spacing values.

---

# 7. Структура Twig шаблонов

Создать архитектуру:

```text
templates/website/
├── layouts/
├── pages/
├── sections/
└── components/
```

Ответственность:

```text
layouts/
общий layout страницы

pages/
композиция конкретных страниц

sections/
крупные переиспользуемые блоки сайта

components/
маленькие UI-компоненты
```

---

## 7.1. Главное правило Page Composition

`page` должен преимущественно **собирать sections**, а не содержать сотни строк уникальной HTML-разметки.

Страница — композиция существующих секций.

Секция — композиция существующих UI-компонентов.

Не дублировать одинаковую разметку между страницами.

---

## 7.2. Проверка Twig architecture

- [ ] существуют все четыре каталога;
- [ ] UI-kit работает через общий website layout;
- [ ] минимум один component подключается через отдельный Twig template;
- [ ] минимум одна section подключается из отдельного Twig template;
- [ ] page не дублирует код component/section;
- [ ] layout не содержит page-specific разметку;
- [ ] component не содержит бизнес-логику страницы.

---

# 8. Структура Assets

Создать понятное разделение:

```text
assets/styles/website/
├── tokens.css
├── base.css
├── components/
└── sections/
```

Если используется отдельный JS:

```text
assets/controllers/website/
```

или существующая структура JS проекта, если она уже определена архитектурой приложения.

---

## 8.1. Запрещено

Не создавать:

```text
home.css
pricing.css
diagnostic.css
```

если CSS описывает компонент или секцию, которые могут использоваться повторно.

Page-specific CSS допустим только при объективной необходимости и должен быть минимальным.

---

## 8.2. Проверка Assets

- [ ] CSS разделен по ответственности;
- [ ] отсутствуют inline `<style>`;
- [ ] отсутствует inline `style=""`;
- [ ] отсутствует inline JavaScript в Twig;
- [ ] один и тот же компонент не описан CSS несколько раз;
- [ ] reusable styles находятся в component/section слоях, а не в page-specific файлах;
- [ ] подключение assets централизовано.

---

# 9. Базовые UI Components

На Stage 1 не требуется большая библиотека.

Реализовать минимум:

```text
Button
Card
Badge
Alert
Form Input
Select
Textarea
Checkbox
Accordion
Breadcrumb
Navbar
Footer
CTA
```

Использовать возможности Bootstrap 5, если они подходят по функциональности.

Не переписывать собственный аналог Bootstrap-компонента без необходимости.

---

## 9.1. Состояния компонентов

Для применимых компонентов предусмотреть:

```text
Default
Hover
Focus
Active
Disabled
Error
Success
```

Не все состояния обязательны для всех типов компонентов, только там, где они имеют смысл.

---

## 9.2. Проверка компонентов

Для каждого компонента должны быть показаны соответствующие состояния на `/ui-kit`.

Acceptance:

- [ ] каждый перечисленный компонент существует;
- [ ] каждый представлен на `/ui-kit`;
- [ ] компонент корректно выглядит на desktop;
- [ ] компонент корректно выглядит на mobile;
- [ ] focus-state видим;
- [ ] одинаковый компонент используется через одну реализацию;
- [ ] отсутствуют две визуально одинаковые реализации одного компонента;
- [ ] Bootstrap-компонент не продублирован собственной реализацией без причины.

---

# 10. Section Architecture

Создать не все будущие маркетинговые секции, а **паттерн их построения**.

Для Stage 1 реализовать минимум:

```text
Section Base
Hero
Content Section
CTA Section
```

Общий section pattern:

```text
section
 └ container
      └ content
```

---

## 10.1. Общие правила Section

Секция не должна самостоятельно придумывать:

```text
container width
vertical spacing
breakpoints
typography system
button system
card system
```

Если секции нужен новый визуальный паттерн, сначала проверить возможность использования существующего.

---

## 10.2. Проверка Section Architecture

- [ ] Hero создан отдельной section;
- [ ] Content Section создан отдельной section;
- [ ] CTA создан отдельной section;
- [ ] Hero и CTA используют единые container rules;
- [ ] Hero и CTA используют утвержденные spacing tokens;
- [ ] Typography внутри sections использует Typography System;
- [ ] новая тестовая section может быть создана без изменения global CSS;
- [ ] секции не содержат page-specific hacks.

---

# 11. Container / Grid

Использовать единое правило ширины контента.

За основу использовать Bootstrap container/grid с минимальными VF-модификациями.

Не создавать:

```text
.container-home
.container-pricing
.container-hero
```

без объективной причины.

---

## 11.1. Content Width

Для длинного текста предусмотреть отдельное ограничение ширины текста.

Ориентир:

```text
640–720px
```

Это не означает создание отдельного container для каждой секции.

Использовать единый reusable utility/pattern для comfortable reading width.

---

## 11.2. Проверка Container/Grid

- [ ] существует одно базовое правило container;
- [ ] Hero использует его;
- [ ] CTA использует его;
- [ ] UI-kit использует его;
- [ ] ширина сайта не задается отдельно на каждой странице;
- [ ] long-form text не растягивается на всю ширину large desktop;
- [ ] Bootstrap grid используется последовательно.

---

# 12. Responsive

Основной принцип:

**Mobile является состоянием того же компонента, а не отдельной версией сайта.**

Обязательно проверить минимум:

```text
375px
768px
1024px
1440px
```

---

## 12.1. Acceptance Responsive

На каждой ширине:

- [ ] отсутствует горизонтальный scroll;
- [ ] Navbar работает;
- [ ] формы помещаются в viewport;
- [ ] текст не выходит за container;
- [ ] кнопки остаются доступными;
- [ ] карточки корректно перестраиваются;
- [ ] Hero сохраняет логическую иерархию;
- [ ] Typography корректно адаптируется;
- [ ] spacing не становится визуально избыточным;
- [ ] интерактивные элементы остаются доступными для использования.

---

# 13. UI-kit

Создать техническую страницу:

```text
/ui-kit
```

Она должна стать визуальным Source of Truth для реализации Design System.

---

## 13.1. Состав UI-kit

Обязательно показать:

```text
Typography
Colors
Spacing
Buttons
Badges
Cards
Alerts
Forms
Accordion
Breadcrumbs
Navigation
Hero
Content Section
CTA
```

---

## 13.2. Важное правило UI-kit

UI-kit не должен создавать отдельные демонстрационные копии компонентов.

Он должен использовать **те же production components и production sections**, которые будут использовать реальные страницы сайта.

---

## 13.3. Проверка UI-kit

Acceptance:

- [ ] `/ui-kit` возвращает HTTP 200;
- [ ] присутствуют все заявленные блоки;
- [ ] используются реальные Twig components;
- [ ] используются реальные Twig sections;
- [ ] UI-kit не содержит копий production components;
- [ ] Typography scale видна на одной странице;
- [ ] Spacing scale видна на одной странице;
- [ ] responsive можно проверить на UI-kit;
- [ ] состояния компонентов визуально доступны для проверки.

---

# 14. Accessibility Baseline

Не превращать Stage 1 в отдельный accessibility-проект, но заложить базовые обязательные правила.

Обязательно:

- semantic HTML;
- корректные `label` для form controls;
- keyboard navigation;
- `alt` для meaningful images;
- декоративные изображения не должны засорять accessibility tree;
- видимый focus;
- корректная иерархия H1 → H2 → H3;
- `button` используется для действия;
- `a` используется для навигации;
- интерактивные элементы должны иметь понятное доступное название;
- не полагаться только на цвет для передачи критического состояния.

---

## 14.1. Проверка Accessibility

- [ ] форма UI-kit имеет связанные `label`;
- [ ] все интерактивные элементы доступны через Tab;
- [ ] focus визуально виден;
- [ ] на UI-kit один основной H1;
- [ ] semantic elements используются корректно;
- [ ] Lighthouse Accessibility не содержит критических ошибок.

---

# 15. Правила расширения Design System

Перед созданием любого нового UI решения разработчик обязан пройти следующий порядок:

```text
1. Есть ли подходящее решение в Bootstrap 5?
2. Есть ли уже VF Component?
3. Есть ли уже VF Section Pattern?
4. Можно ли расширить существующий компонент?
5. Можно ли решить задачу комбинацией существующих компонентов?
6. Только после этого создавать новый компонент/паттерн.
```

Новый компонент не должен создаваться только потому, что на новой странице хочется «немного другой дизайн».

### Проверка

- [ ] алгоритм явно присутствует в `SITE_RULES.md`;
- [ ] все реализованные Stage 1 components можно сопоставить с этим правилом;
- [ ] отсутствуют визуальные дубликаты компонентов.

---

# 16. AI-generated UI Anti-patterns

В `SITE_RULES.md` создать отдельный обязательный раздел:

```text
AI-generated UI anti-patterns
```

Цель — не допустить типового AI/SaaS-дизайна, при котором страница технически аккуратна, но выглядит как набор автоматически сгенерированных шаблонов.

---

## 16.1. Главное правило

```text
The agent MUST NOT introduce visual variety only for decorative purposes.
```

Новый визуальный паттерн должен решать конкретную задачу:

- функциональную;
- информационную;
- иерархическую;
- адаптивную;
- accessibility.

«Чтобы блок выглядел интереснее» не является достаточной причиной.

---

## 16.2. Запрещенные AI UI паттерны

Запрещено без явной функциональной или смысловой причины:

- чрезмерное использование карточек;
- превращение каждого смыслового блока в Card;
- Card внутри Card;
- одинаковая композиция `heading → text → 3 equal cards` во многих секциях;
- badge/pill над каждым заголовком;
- иконка возле каждого заголовка;
- декоративная иконка у каждого пункта списка;
- случайные gradients;
- glow effects;
- glassmorphism;
- blur ради декоративного эффекта;
- декоративные blobs;
- случайные abstract background shapes;
- чрезмерный border-radius;
- одинаково большие radius у всех элементов;
- чрезмерные shadows;
- декоративные floating elements;
- центрирование всего текста страницы;
- центрирование длинных текстовых блоков;
- отдельный цветной фон почти для каждой секции;
- автоматическое чередование `white → gray → colored → white`;
- oversized Hero с небольшим количеством полезной информации;
- новый layout только для того, чтобы соседние sections выглядели разными;
- новый вариант существующего component только ради визуального разнообразия;
- fake dashboard;
- fake chart;
- fake product screenshot;
- fake customer metric;
- fake testimonial;
- fake company logo;
- декоративная статистика без реальных данных;
- UI элементы, которые выглядят интерактивными, но ничего не делают.

---

## 16.3. Предпочтительный подход

Предпочитать:

```text
Typography
Whitespace
Content hierarchy
Clear grid
Consistent spacing
Real product screenshots
Real data
Reusable components
Simple sections
```

вместо декоративного усложнения.

Главный принцип:

```text
Content and hierarchy first.
Decoration second.
```

---

## 16.4. Повторное использование

Если две секции могут использовать один и тот же component/layout pattern, существующий паттерн должен быть переиспользован.

Не создавать визуально новую версию только для того, чтобы страница выглядела разнообразнее.

---

## 16.5. Проверка AI Anti-patterns

После реализации Stage 1 провести отдельный Visual Review.

Проверить:

- [ ] нет Card внутри Card без функциональной причины;
- [ ] не каждый смысловой блок оформлен карточкой;
- [ ] отсутствует массовое использование badges/pills;
- [ ] отсутствуют случайные gradients/glow/glassmorphism;
- [ ] отсутствуют бессмысленные decorative icons;
- [ ] отсутствует визуальное разнообразие без функциональной причины;
- [ ] Hero не раздут искусственно;
- [ ] нет fake dashboards/charts/metrics/testimonials;
- [ ] reusable patterns действительно переиспользуются;
- [ ] whitespace и typography используются как основные инструменты иерархии.

---

# 17. Общие запреты

В `SITE_RULES.md` явно зафиксировать отдельными правилами:

```text
NO inline CSS
NO inline JavaScript
NO arbitrary colors
NO arbitrary typography
NO arbitrary spacing
NO arbitrary border-radius
NO page-specific copies of reusable components
NO second UI framework
NO duplicated Twig components
NO desktop-only components
NO new component when an existing component solves the task
NO decorative visual variation without functional reason
NO fake data used as visual decoration
```

---

# 18. AGENTS.md

Не переносить весь `SITE_RULES.md` в `AGENTS.md`.

Добавить в `AGENTS.md` компактное обязательное правило:

```markdown
## Public Website

For all changes to the public website, the agent MUST read and follow:

- `SITE_RULES.md`

`SITE_RULES.md` is the source of truth for:

- website design system;
- design tokens;
- typography;
- spacing;
- Twig structure;
- UI components;
- section architecture;
- CSS rules;
- responsive behavior;
- accessibility baseline;
- AI-generated UI anti-patterns.

Do not introduce new website UI patterns, arbitrary styles,
duplicate components, or decorative variants that conflict
with `SITE_RULES.md`.
```

### Проверка

- [ ] раздел существует в `AGENTS.md`;
- [ ] имеется явная ссылка на `SITE_RULES.md`;
- [ ] `SITE_RULES.md` указан как Source of Truth;
- [ ] AI-generated UI anti-patterns входят в область обязательных правил;
- [ ] правила не продублированы полностью внутри `AGENTS.md`.

---

# 19. Автоматические проверки

Минимально Stage должен обеспечить автоматическую проверку базовых запретов.

---

## 19.1. Functional Test

Проверить:

```text
GET /ui-kit
→ HTTP 200
```

---

## 19.2. HTML / Rendering

Проверить:

- страница рендерится;
- основные sections присутствуют;
- основные components присутствуют;
- assets загружаются;
- нет критических template errors.

---

## 19.3. Static Checks

Добавить проверку Twig/CSS на запрещенные конструкции.

Минимум искать:

```text
style="
<style>
<script>
```

в Twig публичного сайта.

Если проект требует допустимого исключения, оно должно быть явно задокументировано.

По возможности также выявлять:

- повторяющиеся hardcoded colors;
- явно случайные spacing values;
- дублирующиеся page-specific styles.

---

# 20. Тестирование

Минимальный набор:

### Functional

- `/ui-kit` возвращает HTTP 200;
- website layout рендерится;
- Hero section рендерится;
- CTA section рендерится.

### Responsive manual/automated verification

Проверить:

```text
375px
768px
1024px
1440px
```

### Regression

Существующий test suite проекта должен оставаться зеленым.

---

# 21. Self-review

После реализации Stage агент обязан выполнить self-review собственного кода как чужого.

Проверить минимум:

```text
Architecture
Duplication
Twig responsibilities
CSS responsibilities
Design Tokens usage
Typography consistency
Spacing consistency
Responsive
Accessibility
AI UI anti-patterns
Tests
```

Все найденные замечания уровня blocker/major должны быть исправлены до передачи Stage на внешнее review.

---

# 22. External Review

После зеленого self-review выполнить внешнее review по действующим правилам проекта.

В review отдельно запросить проверку:

- архитектуры UI;
- дублирования;
- несоблюдения tokens;
- arbitrary CSS values;
- responsive;
- accessibility;
- AI-generated UI anti-patterns;
- избыточной сложности.

Замечания должны быть либо исправлены, либо явно задокументированы с причиной отклонения.

---

# 23. Stage Report

После окончания Stage подготовить краткий отчет:

```text
1. Что реализовано
2. Какие файлы созданы/изменены
3. Какие Design Tokens определены
4. Какие Components реализованы
5. Какие Sections реализованы
6. Что доступно на /ui-kit
7. Какие тесты выполнены
8. Результат responsive проверки
9. Результат accessibility проверки
10. Результат AI anti-pattern review
11. Результат self-review
12. Результат external review
13. Известные ограничения
```

---

# 24. Definition of Done — Stage 1

Stage считается завершенным **только если одновременно выполнены все условия**.

## Rules

- [ ] создан `SITE_RULES.md`;
- [ ] `AGENTS.md` требует соблюдения `SITE_RULES.md`;
- [ ] `SITE_RULES.md` является Source of Truth;
- [ ] определена процедура добавления нового UI pattern;
- [ ] определены AI-generated UI anti-patterns.

## Design Tokens

- [ ] существует `tokens.css`;
- [ ] colors определены централизованно;
- [ ] typography определена централизованно;
- [ ] spacing определен централизованно;
- [ ] radius определены централизованно;
- [ ] containers определены централизованно;
- [ ] breakpoints имеют единые правила.

## Typography

- [ ] TT Norms Pro установлен как primary font token;
- [ ] определен fallback;
- [ ] подтверждено право использования webfont перед подключением файлов;
- [ ] шрифт не загружается с `tochka.com`;
- [ ] font files не скопированы с чужого сайта;
- [ ] H1–H4 определены;
- [ ] Lead определен;
- [ ] Body определен;
- [ ] Small определен;
- [ ] Caption определен;
- [ ] используются только утвержденные font weights;
- [ ] typography представлена на UI-kit.

## Spacing

- [ ] spacing scale `4/8/12/16/24/32/48/64/80/96` определена;
- [ ] spacing tokens созданы;
- [ ] section spacing определен;
- [ ] card padding определен;
- [ ] form gaps определены;
- [ ] grid gaps определены;
- [ ] spacing представлен на UI-kit;
- [ ] отсутствуют необоснованные произвольные spacing values.

## Architecture

- [ ] создана структура `layout/page/section/component`;
- [ ] создана структура CSS;
- [ ] page преимущественно собирается из sections;
- [ ] sections используют components;
- [ ] reusable markup не дублируется.

## Components

- [ ] Button реализован;
- [ ] Card реализован;
- [ ] Badge реализован;
- [ ] Alert реализован;
- [ ] Form Input реализован;
- [ ] Select реализован;
- [ ] Textarea реализован;
- [ ] Checkbox реализован;
- [ ] Accordion реализован;
- [ ] Breadcrumb реализован;
- [ ] Navbar реализован;
- [ ] Footer реализован;
- [ ] CTA component/pattern реализован;
- [ ] states компонентов представлены на UI-kit.

## Sections

- [ ] Section Base реализован;
- [ ] Hero реализован;
- [ ] Content Section реализован;
- [ ] CTA Section реализован;
- [ ] sections используют общие container rules;
- [ ] sections используют общие spacing rules;
- [ ] sections используют Typography System.

## UI-kit

- [ ] создан `/ui-kit`;
- [ ] `/ui-kit` возвращает HTTP 200;
- [ ] UI-kit использует production components;
- [ ] UI-kit использует production sections;
- [ ] Typography представлена;
- [ ] Colors представлены;
- [ ] Spacing представлен;
- [ ] Components представлены;
- [ ] Hero представлен;
- [ ] CTA представлен.

## Responsive

- [ ] проверено 375px;
- [ ] проверено 768px;
- [ ] проверено 1024px;
- [ ] проверено 1440px;
- [ ] нет horizontal overflow;
- [ ] Navbar работает;
- [ ] Forms помещаются в viewport;
- [ ] Cards корректно перестраиваются;
- [ ] Typography адаптируется;
- [ ] section spacing адаптируется.

## Code Quality

- [ ] нет inline CSS;
- [ ] нет inline JS;
- [ ] нет дублирования основных components;
- [ ] нет второго UI framework;
- [ ] нет необоснованных page-specific CSS copies;
- [ ] нет произвольных визуальных паттернов;
- [ ] существующие tests проходят.

## Accessibility

- [ ] form labels связаны с controls;
- [ ] keyboard navigation работает;
- [ ] focus видим;
- [ ] semantic HTML соблюден;
- [ ] heading hierarchy соблюдена;
- [ ] нет критических Lighthouse Accessibility ошибок.

## AI UI Review

- [ ] нет чрезмерного использования Cards;
- [ ] нет Card inside Card без причины;
- [ ] нет шаблонного повторения `heading + text + 3 cards`;
- [ ] нет чрезмерных badges/pills;
- [ ] нет декоративных gradients/glow/glassmorphism;
- [ ] нет бессмысленных icons;
- [ ] нет fake metrics/charts/testimonials;
- [ ] нет визуальных variants только ради разнообразия;
- [ ] существующие patterns переиспользуются.

## Review

- [ ] проведен self-review;
- [ ] замечания self-review исправлены;
- [ ] проведено external review;
- [ ] blocker/major замечания external review исправлены или обоснованно отклонены;
- [ ] подготовлен Stage Report.

---

# 25. Итоговый результат Stage

После завершения Stage должна существовать следующая архитектура:

```text
AGENTS.md
      ↓
SITE_RULES.md
      ↓
Design Tokens
      ↓
Typography + Spacing
      ↓
Bootstrap 5
      ↓
UI Components
      ↓
Reusable Sections
      ↓
UI Kit
      ↓
Будущие страницы сайта
```

Stage 1 должен дать возможность в следующих этапах собирать новые страницы из существующих правил и компонентов, не создавая новый дизайн заново для каждой страницы.

---

# 26. Что НЕ входит в Stage 1

Чтобы не усложнять первый этап, в Stage 1 не входят:

- полноценная главная страница;
- CMS;
- визуальный page builder;
- сложная система контентных блоков;
- A/B testing framework;
- маркетинговая аналитика;
- SEO-архитектура всех будущих страниц;
- полный набор marketing sections;
- сложные анимации;
- собственный UI framework поверх Bootstrap;
- создание уникального фирменного шрифта;
- масштабная brand-system разработка.

Эти задачи должны выполняться только в следующих Stage по мере необходимости.
