# Stage 2 — Marketing Sections Library / Demo

## Проект

**Ваш Финдир**

## Статус

Техническое задание на Stage 2 после завершения Stage 1 — Website Foundation / Design System и Color System.

---

# 1. Цель Stage 2

Создать **библиотеку переиспользуемых marketing section patterns** для будущих публичных страниц сайта «Ваш Финдир».

Stage 2 не должен создавать реальную главную страницу, лендинг, страницу диагностики или другую production marketing page.

Основной результат:

```text
Stage 1 Design System
        ↓
Typography + Colors + Spacing + Components
        ↓
Reusable Layout Patterns
        ↓
Reusable Marketing Sections
        ↓
/ui-kit/sections
        ↓
Stage 3 — сборка реальных страниц
```

После завершения Stage 2 новую страницу должно быть возможно спроектировать как композицию уже существующих sections:

```text
Hero
→ Problem
→ Product / Feature
→ Steps
→ Case
→ FAQ
→ CTA
```

без проектирования нового визуального языка для каждой страницы.

---

# 2. Главные принципы Stage 2

## 2.1. Это библиотека, а не страница

Stage 2 создаёт:

- layout patterns;
- reusable marketing sections;
- contracts sections;
- demo page `/ui-kit/sections`;
- правила выбора sections;
- responsive и accessibility поведение.

Stage 2 **не создаёт реальный маркетинговый контент и не собирает production page**.

---

## 2.2. Content and hierarchy first

Новый section должен решать конкретную:

- информационную;
- функциональную;
- иерархическую;
- адаптивную;
- accessibility-задачу.

Причина:

```text
«чтобы блок выглядел иначе»
```

не является основанием для создания нового section или variant.

---

## 2.3. Не превращать тип контента в отдельный UI-компонент автоматически

Запрещено создавать:

```text
ProblemCard
BenefitCard
FeatureCard
StepCard
CaseCard
ExpertCard
ArticleCard
```

только потому, что данные имеют разные названия.

Сначала определить структурный layout pattern:

```text
Text + List
Text + Media
Grid
Split
Sequential Steps
Choice
Quote
Comparison
Form
CTA
```

Разные типы контента должны переиспользовать эти patterns, если структура одинакова.

---

## 2.4. Stage 1 остаётся Source of Truth

Обязательны существующие правила:

```text
SITE_RULES.md
Design Tokens
Typography System
Color System
Spacing System
Bootstrap 5
UI Components
Section Base
Accessibility baseline
AI-generated UI anti-patterns
```

Stage 2 не должен создавать вторую дизайн-систему.

---

# 3. Scope Stage 2

Stage состоит из следующих подэтапов:

```text
Stage 2.0 — Typography Migration: TT Norms Pro → Onest
Stage 2.1 — Marketing Section Architecture
Stage 2.2 — Reusable Layout Patterns
Stage 2.3 — Marketing Sections Library
Stage 2.4 — Section Contracts
Stage 2.5 — Demo Content Rules
Stage 2.6 — /ui-kit/sections
Stage 2.7 — Responsive & Content Stress Test
Stage 2.8 — Accessibility
Stage 2.9 — AI UI Anti-pattern Review
Stage 2.10 — Tests & Automated Checks
Stage 2.11 — SITE_RULES.md Documentation
Stage 2.12 — Self-review / External Review / Stage Report
```

Каждый подэтап должен быть завершён и проверен до закрытия Stage 2.

---

# 4. Что НЕ входит в Stage 2

Не выполнять:

- полноценную Главную;
- страницу «Диагностика»;
- страницу тарифов;
- страницу кейсов;
- страницы «Веду сам» / «Работа с финдиром»;
- реальные маркетинговые тексты;
- реальные кейсы;
- реальные отзывы;
- реальные customer logos;
- реальные финансовые показатели;
- SEO-архитектуру production pages;
- CMS;
- page builder;
- A/B testing;
- аналитику воронки;
- новый UI framework;
- React/SPA;
- новый JS framework;
- сложные animations;
- декоративные motion effects;
- редизайн Stage 1 компонентов;
- новый Brand Color;
- изменение базовой Color System без отдельного обоснования.

---

# 5. Stage 2.0 — Typography Migration: TT Norms Pro → Onest

## 5.1. Цель

Полностью заменить незагруженный/лицензионно ограниченный `TT Norms Pro` на бесплатный open-source шрифт **Onest**.

Onest становится единственным primary font публичного сайта.

Использовать локально размещённый webfont.

Не использовать:

- TT Norms Pro;
- TT Norms;
- hotlink на сторонний font CDN;
- Google Fonts runtime request;
- npm package только ради подключения шрифта;
- новый frontend toolchain ради шрифта.

---

## 5.2. Font Family

Обновить Typography System:

```text
Primary font:
Onest

Fallback:
"Onest", Arial, sans-serif
```

Сохранить используемые веса:

```text
400 — Regular
500 — Medium
700 — Bold
```

Не добавлять другие weights без реальной необходимости.

---

## 5.3. Font Files

Предпочтительный формат:

```text
WOFF2
```

Допускается variable WOFF2, если он позволяет использовать необходимые веса без дополнительного frontend toolchain.

Файлы должны:

- храниться локально в проекте;
- иметь понятное происхождение из официального Open Font distribution;
- не загружаться во время выполнения страницы со стороннего CDN;
- сопровождаться лицензией OFL в репозитории.

Рекомендуемая структура:

```text
site/public/assets/fonts/onest/
├── Onest-Variable.woff2
└── OFL.txt
```

или эквивалентная существующей asset architecture проекта.

Не вводить отдельный asset pipeline только ради font files.

---

## 5.4. @font-face

Добавить централизованный `@font-face`.

Обязательно:

```text
font-family: "Onest"
font-style: normal
font-display: swap
```

Если используется variable font:

```text
font-weight: 100 900
```

Если используются static files — подключать только реально используемые `400 / 500 / 700`.

---

## 5.5. Design Tokens

Обновить:

```css
--vf-font-primary
```

с TT Norms Pro на Onest.

Удалить TT Norms из fallback chain.

---

## 5.6. SITE_RULES.md

Удалить прежние ограничения, относящиеся к отсутствию лицензии TT Norms Pro.

Зафиксировать:

```text
Onest = primary font Public Website.
```

Также зафиксировать:

- локальное self-hosted подключение;
- разрешённые weights;
- fallback;
- запрет произвольных font-family в sections/pages.

---

## 5.7. Финальная проверка Typography

После подключения Onest **не считать старые размеры автоматически правильными**.

Проверить текущую Typography scale:

```text
H1
H2
H3
H4
Lead
Body
Small
Caption
```

на реальном Onest.

Если метрики Onest требуют корректировки:

- корректировать только централизованные typography tokens;
- изменение должно применяться системно;
- не создавать page-specific `font-size`;
- не создавать отдельные размеры только для одной demo-section;
- объяснить изменение в Stage Report.

---

## 5.8. Проверка переносов

На реальном Onest проверить минимум:

### H1

Короткий:

```text
Финансы без догадок
```

Средний:

```text
Финансовая система для устойчивого роста бизнеса
```

Длинный:

```text
Управляйте финансами бизнеса на основе данных, а не ощущения остатка на счёте
```

### H2/H3

Проверить заголовки длиной:

```text
30–40 символов
60–80 символов
100+ символов
```

### Body / Lead

Проверить:

- 1 строку;
- 2–3 строки;
- 5–7 строк;
- русский текст;
- цифры;
- знак `₽`;
- проценты `%`;
- тире;
- кавычки «»;
- скобки.

---

## 5.9. Правила переносов

Запрещено исправлять плохой перенос через:

```html
<br>
```

в reusable section только ради конкретного demo-текста.

Использовать:

- width constraints;
- typography;
- responsive layout;
- нормальную редакционную длину текста.

Принудительный `<br>` допускается только как осознанная часть реального content contract в будущем, но не для исправления demo layout.

---

## 5.10. Acceptance — Typography Migration

- [ ] Onest подключён локально;
- [ ] font files загружаются HTTP 200;
- [ ] используется `font-display: swap`;
- [ ] OFL license присутствует рядом с font assets или в согласованном license location;
- [ ] `--vf-font-primary` использует Onest;
- [ ] TT Norms Pro удалён из active website Typography System;
- [ ] TT Norms удалён из fallback;
- [ ] `SITE_RULES.md` обновлён;
- [ ] 400 / 500 / 700 работают;
- [ ] computed `font-family` в browser действительно использует Onest;
- [ ] Cyrillic отображается корректно;
- [ ] `₽`, `%`, цифры и пунктуация отображаются корректно;
- [ ] H1–H4 визуально проверены;
- [ ] Lead/Body/Small/Caption проверены;
- [ ] длинные русские заголовки не ломают layout;
- [ ] page-specific typography hacks отсутствуют;
- [ ] финальные изменения Typography scale, если были, задокументированы.

---

# 6. Stage 2.1 — Marketing Section Architecture

## 6.1. Цель

Определить архитектуру marketing sections поверх Stage 1.

Не создавать отдельный архитектурный слой, если текущая структура уже подходит.

Основная структура остаётся:

```text
site/templates/website/
├── layouts/
├── pages/
├── sections/
└── components/
```

Marketing sections должны находиться внутри существующего `sections/`.

Допускается внутреннее логическое разделение:

```text
sections/
├── foundation/
└── marketing/
```

только если это реально улучшает навигацию и не требует широкого перемещения Stage 1 файлов.

Не выполнять массовое перемещение существующих файлов только ради красивой структуры.

---

## 6.2. Главное правило

```text
Page → Sections → Components
```

Marketing Section:

- не содержит бизнес-логики;
- не делает запросы к БД;
- не принимает Entity;
- не знает о конкретной production page;
- принимает только данные, необходимые для отображения;
- использует Stage 1 components;
- использует Design Tokens;
- не создаёт собственную Color/Typography/Spacing System.

---

## 6.3. Acceptance

- [ ] marketing sections находятся в существующей website architecture;
- [ ] отдельный новый framework/layer не создан;
- [ ] page-specific зависимости отсутствуют;
- [ ] section не принимает Doctrine Entity;
- [ ] section не содержит запросы/бизнес-логику;
- [ ] Stage 1 components переиспользуются.

---

# 7. Stage 2.2 — Reusable Layout Patterns

## 7.1. Цель

До создания семантических marketing sections определить минимальный набор структурных layout patterns.

Нужен не «дизайн под каждый блок», а несколько устойчивых композиций.

Минимально предусмотреть:

```text
1. Text / Content
2. Text + List
3. Split: Text + Media
4. Grid
5. Sequential Steps
6. Two-choice / Paths
7. Quote
8. Comparison
9. Form + Context
10. CTA
```

Hero уже существует в Stage 1 и не создаётся заново.

---

## 7.2. Split Pattern

Поддержать:

```text
Text | Media
Media | Text
```

Но не создавать два независимых section templates, если они отличаются только направлением.

Использовать один pattern с ограниченным variant:

```text
media_position = left | right
```

---

## 7.3. Grid Pattern

Grid используется только когда элементы действительно равноправны.

Не использовать Grid/Card автоматически для любого списка.

Grid должен корректно работать минимум с:

```text
2 элемента
3 элемента
4 элемента
6 элементов
```

Без отдельных layout hacks под каждое количество.

---

## 7.4. Sequential Steps

Steps должны показывать последовательность, а не набор независимых карточек.

Поддержать:

```text
2–5 шагов
```

Порядок должен быть понятен без декоративной графики.

---

## 7.5. Two-choice / Paths

Используется только когда пользователю действительно предлагаются два разных сценария.

Не превращать любой comparison в Two Paths.

Поддержать:

```text
2 варианта
```

Третий и более варианты используют другой pattern.

---

## 7.6. Acceptance Layout Patterns

- [ ] layout patterns документированы;
- [ ] нет дублирующихся left/right templates;
- [ ] Grid не является универсальным ответом на любой контент;
- [ ] Steps читаются как последовательность;
- [ ] Two Paths используется только для выбора двух сценариев;
- [ ] patterns используют существующие spacing/container rules;
- [ ] новые arbitrary CSS values не появились.

---

# 8. Stage 2.3 — Marketing Sections Library

## 8.1. Обязательный набор

На `/ui-kit/sections` должны быть представлены следующие смысловые section types.

Часть из них может быть реализована через общий layout pattern без отдельного CSS.

---

## 8.2. Hero

Hero уже существует.

Stage 2:

- проверить, что существующий Hero подходит для marketing pages;
- использовать существующий light/dark variant;
- не создавать Hero v2;
- не добавлять декоративные gradient/blob/image patterns.

Demo должен показать существующий Hero как reference, но не дублировать его реализацию.

### Acceptance

- [ ] используется production Hero Stage 1;
- [ ] новый Hero template не создан;
- [ ] demo показывает минимум один разрешённый вариант.

---

## 8.3. Problem / Pain

Назначение:

- быстро показать узнаваемые проблемы;
- позволить пользователю определить релевантность страницы.

Предпочтительная структура:

```text
Heading
Short intro
List / structured statements
```

Не превращать каждую проблему в Card по умолчанию.

Допустимый диапазон:

```text
3–6 проблем
```

### Acceptance

- [ ] есть demo Problem section;
- [ ] section работает без обязательных Cards;
- [ ] 3 и 6 элементов не ломают layout;
- [ ] длинный problem text переносится корректно.

---

## 8.4. Benefits / Outcomes

Назначение:

- описывать результат для пользователя;
- не дублировать Feature section.

Допустимый диапазон:

```text
2–6 outcomes
```

Можно использовать List или Grid в зависимости от структуры.

Не создавать отдельный визуальный язык только потому, что контент называется Benefits.

### Acceptance

- [ ] есть demo Benefits/Outcomes;
- [ ] pattern переиспользует существующую layout architecture;
- [ ] section отличим от Feature по назначению, а не декоративным стилем.

---

## 8.5. Product / Feature Explanation

Назначение:

- объяснить одну возможность продукта;
- показать связь «что это → зачем → как выглядит».

Основной pattern:

```text
Text + Media
```

Поддержать:

```text
media left
media right
```

Media в Stage 2 — **demo placeholder**, не fake screenshot.

Рекомендуемый demo aspect ratio:

```text
16:10
```

### Acceptance

- [ ] один section поддерживает left/right;
- [ ] отдельные duplicate templates не созданы;
- [ ] media placeholder сохраняет ratio;
- [ ] отсутствие реального screenshot не маскируется fake dashboard.

---

## 8.6. How It Works / Steps

Назначение:

- показать последовательность действий.

Поддержать:

```text
2–5 шагов
```

Каждый шаг:

```text
number/order
title
short text
```

Иконка не обязательна.

### Acceptance

- [ ] порядок шагов однозначен;
- [ ] layout работает с 2 и 5 шагами;
- [ ] нет обязательной декоративной Card на каждый шаг;
- [ ] mobile порядок сохраняется.

---

## 8.7. Two Paths / Choice

Назначение:

- показать два способа работы;
- помочь пользователю выбрать направление.

Поддержать строго:

```text
2 paths
```

Каждый path:

```text
title
short description
optional list
optional CTA
```

Не использовать semantic Success/Danger для обозначения «лучшего» и «хуже».

### Acceptance

- [ ] ровно два сценария визуально сравнимы;
- [ ] один вариант не объявляется «правильным» только цветом;
- [ ] CTA использует production Button.

---

## 8.8. Case Study Preview

Назначение:

```text
Problem
→ Action
→ Result
```

В Stage 2 используются только demo placeholders.

Запрещено:

- выдумывать клиента;
- выдумывать оборот;
- выдумывать проценты роста;
- fake logo;
- fake testimonial.

### Acceptance

- [ ] структура Problem → Action → Result понятна;
- [ ] нет придуманных business metrics;
- [ ] нет fake customer identity.

---

## 8.9. Proof / Expertise

Назначение:

- представить основания доверять;
- показать материалы, опыт или реальные proof points в будущем.

Stage 2 показывает только структуру.

Допустимые элементы:

```text
heading
text
list of proof items
optional links
```

Не создавать fake awards, badges или сертификаты.

### Acceptance

- [ ] demo section существует;
- [ ] нет fake awards/logos;
- [ ] нет декоративной статистики без данных.

---

## 8.10. Testimonial / Quote

Назначение:

- показать будущий customer quote.

В Stage 2:

- только явно помеченный Demo Quote;
- без имени реального/выдуманного клиента;
- без фото;
- без company logo;
- без fake job title.

Пример смысла:

```text
Демонстрационная цитата для проверки длины и переносов текста.
```

### Acceptance

- [ ] Quote pattern существует;
- [ ] demo однозначно не воспринимается как настоящий отзыв;
- [ ] длинная цитата корректно переносится.

---

## 8.11. Pricing Preview

Назначение:

- показать структуру будущего выбора тарифов;
- не создавать production pricing page.

Поддержать:

```text
2–4 options
```

Demo values:

```text
«Вариант A»
«Вариант B»
```

Не использовать реальные цены проекта в Stage 2.

Не создавать «Most Popular» badge без реальной бизнес-логики.

### Acceptance

- [ ] 2–4 options поддерживаются;
- [ ] fake real prices отсутствуют;
- [ ] fake popularity label отсутствует;
- [ ] responsive layout корректен.

---

## 8.12. Comparison

Назначение:

- сравнить варианты по одинаковым критериям.

Поддержать:

```text
2–3 alternatives
```

Количество criteria:

```text
3–8
```

Использовать semantic table/list structure, а не набор случайных cards.

### Acceptance

- [ ] критерии читаются по строкам;
- [ ] mobile comparison остаётся понятным;
- [ ] нельзя понять выбор только по цвету;
- [ ] 8 criteria не ломают layout.

---

## 8.13. FAQ

Использовать Stage 1 Accordion.

Не создавать отдельный FAQ accordion.

Поддержать demo:

```text
3–6 questions
```

### Acceptance

- [ ] production Accordion переиспользован;
- [ ] новый JS не добавлен;
- [ ] keyboard interaction работает;
- [ ] heading hierarchy корректна.

---

## 8.14. Lead / Diagnostic Form Section

Назначение:

- объединить контекст + форму.

Использовать production Form components Stage 1.

Stage 2:

- форма не отправляет реальную заявку;
- backend lead flow не реализуется;
- реальный CRM/API integration не создаётся.

Demo поля:

```text
Имя
Телефон или email — только как demo control
Комментарий
Consent checkbox
```

Не собирать и не сохранять введённые данные.

### Acceptance

- [ ] production form controls переиспользованы;
- [ ] form submit не создаёт lead;
- [ ] данные не сохраняются;
- [ ] labels/accessibility работают;
- [ ] mobile layout корректен.

---

## 8.15. CTA Section

Использовать Stage 1 CTA.

Не создавать новый CTA layout без доказанной необходимости.

Соблюдать существующее правило:

```text
не более одного крупного Brand Red conversion block на production page по умолчанию
```

На demo page допускается showcase, так как это техническая страница.

### Acceptance

- [ ] Stage 1 CTA переиспользован;
- [ ] CTA v2 не создан.

---

## 8.16. Content / Article Preview

Назначение:

- показывать будущие экспертные материалы.

Поддержать:

```text
2–4 items
```

Stage 2:

- demo titles;
- demo descriptions;
- без fake publication statistics;
- без fake dates, если дата не нужна для layout test.

### Acceptance

- [ ] 2–4 items работают;
- [ ] нет fake metrics;
- [ ] cards используются только если это оправдано preview structure.

---

# 9. Stage 2.4 — Section Contracts

## 9.1. Цель

Для каждого production marketing section определить контракт.

Контракт должен отвечать:

```text
Назначение
Required parameters
Optional parameters
Default values
Allowed variants
Allowed item count
Какие components используются
Когда применять
Когда НЕ применять
```

---

## 9.2. Пример

Концептуально:

```text
Feature Split

Purpose:
Объяснить одну возможность продукта с media.

Required:
title
text

Optional:
eyebrow
media
actions

Variants:
media-left
media-right

Do not use:
для списка 5 независимых преимуществ.
```

Формат документации можно выбрать исходя из текущего `SITE_RULES.md`, не создавая отдельную сложную schema system.

---

## 9.3. Semantic wrappers

Допускается semantic wrapper, например:

```text
Problem Section
Benefits Section
```

если он делает API понятнее.

Но если wrappers используют один layout:

- markup должен делегироваться общему pattern;
- CSS не должен дублироваться;
- wrapper не должен существовать только ради другого classname.

---

## 9.4. Acceptance

- [ ] у каждого required section есть contract;
- [ ] required/optional параметры понятны;
- [ ] variants закрытым списком;
- [ ] item limits определены;
- [ ] «когда НЕ использовать» описано;
- [ ] duplicate CSS/markup отсутствует.

---

# 10. Stage 2.5 — Demo Content Rules

## 10.1. Не использовать Lorem Ipsum

Demo content должен быть на русском языке и иметь реалистичную длину.

Причина:

- проверить кириллицу Onest;
- проверить реальные переносы;
- проверить высоту компонентов;
- проверить длинные слова и предложения.

---

## 10.2. Demo content должен быть явно демонстрационным

Использовать формулировки:

```text
Демонстрационный заголовок
Пример описания для проверки переноса текста
Шаг 1
Вариант A
Демонстрационная цитата
```

Допускается нейтральный финансовый контекст без утверждений о реальном продукте.

---

## 10.3. Запрещено придумывать

- клиентов;
- имена клиентов;
- компании клиентов;
- customer logos;
- отзывы;
- кейсы;
- реальные результаты;
- проценты роста;
- обороты;
- выручку;
- экономию;
- количество пользователей;
- награды;
- рейтинги;
- сертификаты;
- публикации в СМИ;
- fake product screenshots.

---

## 10.4. Media Placeholder

Для sections с media использовать технический placeholder.

Он должен явно содержать:

```text
Demo media
16:10
```

или аналогичную техническую подпись.

Placeholder:

- не имитирует реальный интерфейс продукта;
- не содержит fake chart/dashboard;
- используется только на `/ui-kit/sections`;
- showcase-only styles не попадают без необходимости в production CSS.

---

## 10.5. Acceptance

- [ ] Lorem Ipsum отсутствует;
- [ ] русский demo content используется;
- [ ] demo content явно не выдаётся за реальный;
- [ ] fake customer/business data отсутствует;
- [ ] fake product UI отсутствует.

---

# 11. Stage 2.6 — `/ui-kit/sections`

## 11.1. Создать отдельную техническую страницу

Маршрут:

```text
GET /ui-kit/sections
```

Страница является **Marketing Sections Demo / Visual Source of Truth**.

Не смешивать её с существующим `/ui-kit`.

Роли страниц:

```text
/ui-kit
→ foundations + components

/ui-kit/sections
→ marketing layout patterns + marketing sections
```

---

## 11.2. SEO

Обязательно:

```html
<meta name="robots" content="noindex, nofollow">
```

Не добавлять страницу в публичную маркетинговую навигацию.

Допускается ссылка из технической navigation UI-kit.

---

## 11.3. Структура demo page

Рекомендуемый порядок:

```text
Intro
Typography stress test
Hero
Problem
Benefits
Feature Split — media right
Feature Split — media left
Steps
Two Paths
Case Preview
Proof / Expertise
Quote
Pricing Preview
Comparison
FAQ
Lead Form
Article Preview
CTA
```

Не обязательно делать отдельный новый визуальный style между каждым demo section.

---

## 11.4. Каждая demo секция должна показывать

Минимум:

```text
Section name
Purpose
Production section itself
```

Дополнительную техническую документацию размещать сдержанно, чтобы она не превращала каждую секцию в Card-in-Card.

---

## 11.5. Production vs Showcase

`/ui-kit/sections` должен рендерить реальные production section partials.

Запрещено:

- копировать production markup внутрь demo;
- создавать отдельный demo version section;
- изменять production component только ради удобства showcase без необходимости.

---

## 11.6. Acceptance

- [ ] `/ui-kit/sections` возвращает HTTP 200;
- [ ] `noindex, nofollow` присутствует;
- [ ] страница не является production marketing page;
- [ ] все required sections представлены;
- [ ] используются production partials;
- [ ] demo copies отсутствуют;
- [ ] `/ui-kit` не превращён в длинную marketing page;
- [ ] technical navigation позволяет перейти между `/ui-kit` и `/ui-kit/sections`.

---

# 12. Stage 2.7 — Responsive & Content Stress Test

## 12.1. Контрольные ширины

Проверить:

```text
375px
768px
1024px
1440px
```

---

## 12.2. Для каждого section проверить

- horizontal overflow;
- heading wrapping;
- body wrapping;
- CTA placement;
- grid restructuring;
- media ratio;
- order on mobile;
- long words;
- 2–3 строки title;
- optional content;
- item min/max.

---

## 12.3. Content Stress Cases

Минимально проверить:

```text
короткий title
длинный title
короткий text
длинный text
минимальное item count
максимальное item count
отсутствующие optional fields
```

Section не должен ломаться, если optional eyebrow/action/media отсутствует.

---

## 12.4. Media ordering

Для Split:

Desktop:

```text
Text | Media
Media | Text
```

Mobile порядок должен быть осмысленным.

Зафиксировать единое правило, например:

```text
content first → media second
```

если нет конкретной причины сохранять визуальный left/right порядок.

Не менять порядок случайно между sections.

---

## 12.5. Acceptance

На 375 / 768 / 1024 / 1440:

- [ ] horizontal overflow отсутствует;
- [ ] все sections помещаются в viewport;
- [ ] text не обрезается;
- [ ] buttons доступны;
- [ ] media сохраняет ratio;
- [ ] grid перестраивается;
- [ ] mobile reading order логичен;
- [ ] long heading не ломает section;
- [ ] item min/max проверены;
- [ ] optional parameters проверены.

---

# 13. Stage 2.8 — Accessibility

Обязательны Stage 1 правила.

Дополнительно проверить marketing sections.

---

## 13.1. Heading hierarchy

Demo page должна иметь один H1.

Section titles:

```text
H2
```

Внутренние item titles:

```text
H3
```

Не выбирать heading level только ради размера текста.

---

## 13.2. Lists

Если элементы являются списком — использовать semantic:

```html
<ul>
<ol>
```

а не набор `<div>` только ради grid.

Steps по возможности должны отражать последовательность semantic structure.

---

## 13.3. Media

Demo placeholder:

- `aria-hidden="true"`, если не несёт содержательной информации;
- реальный production image contract должен требовать `alt`.

---

## 13.4. Comparison

Comparison должен иметь структуру, понятную:

- visual user;
- keyboard user;
- screen reader.

Если используется table — корректные headers обязательны.

---

## 13.5. Forms

- label/control association;
- visible focus;
- validation semantics Stage 1;
- consent readable;
- demo submit не выполняет действие.

---

## 13.6. Acceptance

- [ ] один H1;
- [ ] section headings semantic;
- [ ] списки semantic;
- [ ] steps semantic;
- [ ] media accessibility contract определён;
- [ ] comparison accessible;
- [ ] FAQ keyboard accessible;
- [ ] form labels работают;
- [ ] visible focus работает;
- [ ] Lighthouse Accessibility не содержит критических ошибок.

---

# 14. Stage 2.9 — AI-generated UI Anti-pattern Review

После реализации провести отдельное visual review всей `/ui-kit/sections`.

---

## 14.1. Проверить отсутствие

- Card inside Card;
- Card для каждого смыслового элемента;
- `heading + text + 3 equal cards` в большинстве sections;
- badge над каждым heading;
- icon рядом с каждым heading;
- icon у каждого list item без необходимости;
- gradients;
- glow;
- glassmorphism;
- decorative blur;
- blobs;
- abstract floating shapes;
- чрезмерные shadows;
- чрезмерные radius;
- случайные colored section backgrounds;
- автоматическое white/gray/dark чередование;
- oversized empty Hero;
- fake dashboards;
- fake charts;
- fake testimonials;
- fake logos;
- fake metrics;
- decorative stats;
- unnecessary variants.

---

## 14.2. Проверить визуальное разнообразие

Sections не должны быть одинаковыми до неразличимости.

Но разнообразие должно происходить из:

```text
content structure
information hierarchy
media presence
list/grid/steps/comparison nature
```

а не из случайных colors/effects.

---

## 14.3. Acceptance

- [ ] нет систематического Card abuse;
- [ ] разные data structures читаются по-разному;
- [ ] декоративное разнообразие не используется;
- [ ] existing patterns переиспользуются;
- [ ] страницы будущего можно собирать без нового visual pattern.

---

# 15. Stage 2.10 — Tests & Automated Checks

## 15.1. Functional

Добавить/обновить test:

```text
GET /ui-kit/sections
→ HTTP 200
```

Проверить:

- page title;
- один H1;
- noindex;
- required section markers;
- assets.

---

## 15.2. Production section coverage

Каждый required marketing section должен иметь однозначный marker:

```text
data-vf-section="..."
```

или существующий эквивалент.

Functional test должен проверять присутствие required set.

---

## 15.3. Static rules

Расширить существующие checks на новые website sections:

- NO inline `style=""`;
- NO `<style>`;
- NO inline JS;
- NO arbitrary HEX вне tokens;
- NO arbitrary typography;
- NO arbitrary spacing units;
- NO second UI framework.

Не дублировать существующие tests, если текущий dynamic scan уже автоматически покрывает новые файлы.

---

## 15.4. Typography checks

Проверить active source:

```text
SITE_RULES.md
site/assets/styles/website/
site/templates/website/
```

На отсутствие:

```text
TT Norms Pro
TT Norms
```

за исключением исторических документов вне active website rules, если они существуют и не являются runtime source.

---

## 15.5. Font asset checks

Проверить:

- font file существует;
- public path корректен;
- browser request HTTP 200;
- `@font-face` указывает локальный asset;
- внешнего runtime font request нет.

---

## 15.6. Browser checks

Провести browser-check на:

```text
375
768
1024
1440
```

Проверить:

- no horizontal overflow;
- failed resources = 0;
- Onest loaded;
- computed font-family;
- navigation;
- FAQ;
- focus;
- form;
- section min/max demos.

---

## 15.7. Команды проекта

После изменений выполнить минимум действующие project commands:

```bash
make assets
make asset-version
make assets-check
make lint
make cs
make phpstan
make deptrac
make test
```

или единый:

```bash
make ci
```

если он полностью включает необходимые проверки.

Не утверждать PASS для проверки, которая фактически не запускалась.

---

# 16. Stage 2.11 — Обновить `SITE_RULES.md`

Добавить раздел:

```text
Marketing Sections
```

Он должен содержать.

---

## 16.1. Section Selection Rules

Минимальная таблица:

```text
Need / Content type
→ Recommended section/pattern
→ Do not use
```

Пример:

```text
Последовательность действий
→ Steps
→ Grid независимых cards

Одна feature + screenshot
→ Split Text + Media
→ Grid

Два разных сценария
→ Two Paths
→ Comparison table без необходимости

Вопросы
→ FAQ / Accordion
→ отдельный custom collapse
```

---

## 16.2. Contracts

Зафиксировать:

- available sections;
- allowed variants;
- min/max items;
- required/optional fields;
- reuse rules.

---

## 16.3. New Section Decision

Перед созданием нового marketing section агент обязан ответить:

```text
1. Есть ли существующий marketing section?
2. Есть ли существующий layout pattern?
3. Можно ли решить задачу новым content contract без нового CSS?
4. Можно ли расширить существующий pattern без изменения его смысла?
5. Какую конкретную проблему решает новый section?
6. Почему существующие sections не подходят?
```

Только после этого новый pattern допускается.

---

## 16.4. Запрет

Новая production page **не является основанием** для нового section.

---

# 17. Stage 2.12 — Self-review

После реализации выполнить self-review как ревью чужого кода.

Проверить:

```text
Typography migration
Onest loading
Typography wrapping
Section architecture
Reuse
Duplication
Contracts
Responsive
Accessibility
AI anti-patterns
Fake content
Tests
Scope
```

---

## 17.1. Обязательные вопросы self-review

- Создан ли лишний component вместо reuse?
- Создан ли отдельный CSS под смысловой label?
- Не стало ли слишком много Cards?
- Есть ли два section, отличающиеся только названием?
- Есть ли variant только ради красоты?
- Есть ли fake content?
- Есть ли page-specific CSS?
- Можно ли собрать будущую page без нового pattern?
- Не изменены ли Stage 1 foundations без причины?

Все blocker/high и относящиеся medium исправить до external review.

---

# 18. External Review

После self-review выполнить Claude Code review по действующим правилам проекта.

Передать reviewer:

- Stage 2 task;
- границы;
- Section list;
- contracts;
- полный diff;
- tests;
- browser results;
- known limitations.

Отдельно попросить проверить:

```text
duplicate sections
over-abstraction
under-abstraction
Card abuse
AI SaaS visual patterns
responsive
accessibility
font migration
fake content
scope creep
```

Не считать Stage завершённым при наличии обоснованных blocker/high.

---

# 19. Stage Report

Подготовить краткий итоговый отчёт:

```text
1. Что реализовано
2. Typography migration result
3. Какие typography tokens изменены
4. Результат проверки переносов
5. Какие layout patterns созданы
6. Какие marketing sections созданы
7. Какие sections переиспользуют общий pattern
8. Какие contracts определены
9. Что доступно на /ui-kit/sections
10. Responsive result
11. Accessibility result
12. AI anti-pattern review
13. Tests / make ci
14. Self-review
15. Claude Code review
16. Known limitations
17. Что НЕ делалось
```

---

# 20. Definition of Done — Stage 2

Stage считается завершённым только при выполнении всех применимых пунктов.

---

## 20.1. Typography

- [ ] Onest установлен как primary website font;
- [ ] font self-hosted;
- [ ] OFL license сохранена;
- [ ] external font CDN не используется;
- [ ] `font-display: swap`;
- [ ] TT Norms Pro удалён из active website rules;
- [ ] TT Norms удалён из active fallback;
- [ ] weights 400/500/700 работают;
- [ ] Cyrillic работает;
- [ ] `₽`, `%`, цифры и punctuation работают;
- [ ] computed font-family проверен;
- [ ] Typography scale проверена на Onest;
- [ ] изменения Typography tokens, если нужны, централизованы;
- [ ] H1–H4 проверены;
- [ ] Lead/Body/Small/Caption проверены;
- [ ] длинные русские заголовки проверены;
- [ ] переносы не исправляются page-specific `<br>`.

---

## 20.2. Architecture

- [ ] Stage 1 architecture сохранена;
- [ ] Marketing sections находятся в существующей section architecture;
- [ ] Page → Section → Component соблюдено;
- [ ] business logic в Twig отсутствует;
- [ ] Entity в section contract не передаётся;
- [ ] page-specific dependencies отсутствуют.

---

## 20.3. Layout Patterns

- [ ] Text/Content pattern определён;
- [ ] Text + List определён;
- [ ] Split Text + Media определён;
- [ ] Grid определён;
- [ ] Steps определён;
- [ ] Two Paths определён;
- [ ] Quote определён;
- [ ] Comparison определён;
- [ ] Form + Context определён;
- [ ] CTA переиспользован;
- [ ] дубли left/right templates отсутствуют.

---

## 20.4. Marketing Sections

На `/ui-kit/sections` представлены:

- [ ] Hero;
- [ ] Problem/Pain;
- [ ] Benefits/Outcomes;
- [ ] Product/Feature;
- [ ] How It Works/Steps;
- [ ] Two Paths;
- [ ] Case Study Preview;
- [ ] Proof/Expertise;
- [ ] Quote/Testimonial placeholder;
- [ ] Pricing Preview;
- [ ] Comparison;
- [ ] FAQ;
- [ ] Lead/Form;
- [ ] Article Preview;
- [ ] CTA.

---

## 20.5. Reuse

- [ ] Stage 1 Hero переиспользован;
- [ ] Stage 1 CTA переиспользован;
- [ ] Stage 1 Accordion переиспользован;
- [ ] Stage 1 Form controls переиспользованы;
- [ ] Stage 1 Button переиспользован;
- [ ] одинаковые structures не имеют duplicate CSS;
- [ ] semantic wrappers, если есть, используют общие patterns.

---

## 20.6. Demo Content

- [ ] production marketing content не создан;
- [ ] Lorem Ipsum отсутствует;
- [ ] используется русский demo text;
- [ ] demo text явно демонстрационный;
- [ ] fake customer names отсутствуют;
- [ ] fake logos отсутствуют;
- [ ] fake testimonials отсутствуют;
- [ ] fake metrics отсутствуют;
- [ ] fake product screenshots отсутствуют;
- [ ] media placeholder технический и явно demo.

---

## 20.7. `/ui-kit/sections`

- [ ] маршрут существует;
- [ ] HTTP 200;
- [ ] `noindex, nofollow`;
- [ ] не добавлен в normal public navigation;
- [ ] production sections используются напрямую;
- [ ] demo copies отсутствуют;
- [ ] `/ui-kit` и `/ui-kit/sections` имеют понятное техническое разделение;
- [ ] все required patterns визуально доступны для проверки.

---

## 20.8. Responsive

Проверено:

- [ ] 375px;
- [ ] 768px;
- [ ] 1024px;
- [ ] 1440px;
- [ ] horizontal overflow отсутствует;
- [ ] headings не обрезаются;
- [ ] grids перестраиваются;
- [ ] Steps сохраняют порядок;
- [ ] Split имеет правильный mobile reading order;
- [ ] media ratio сохраняется;
- [ ] min/max item count не ломает layout;
- [ ] optional fields не ломают layout.

---

## 20.9. Accessibility

- [ ] один H1;
- [ ] headings semantic;
- [ ] lists semantic;
- [ ] Steps semantic;
- [ ] media contract требует alt;
- [ ] demo decorative placeholder скрыт от accessibility tree;
- [ ] Comparison accessible;
- [ ] FAQ keyboard accessible;
- [ ] Forms accessible;
- [ ] focus visible;
- [ ] нет критических Lighthouse Accessibility ошибок.

---

## 20.10. Design System

- [ ] новых arbitrary colors нет;
- [ ] новых arbitrary spacing нет;
- [ ] page-specific typography нет;
- [ ] arbitrary border-radius нет;
- [ ] second UI framework отсутствует;
- [ ] новый JS framework отсутствует;
- [ ] Stage 1 Color System не нарушена.

---

## 20.11. AI Anti-pattern Review

- [ ] Card abuse отсутствует;
- [ ] Card inside Card отсутствует без причины;
- [ ] `heading + text + 3 cards` не стал универсальным pattern;
- [ ] badges/icons не используются механически;
- [ ] gradients/glow/glassmorphism отсутствуют;
- [ ] decorative blobs отсутствуют;
- [ ] random section colors отсутствуют;
- [ ] fake dashboards/charts отсутствуют;
- [ ] fake metrics/testimonials/logos отсутствуют;
- [ ] variants существуют только по функциональной причине.

---

## 20.12. Documentation

- [ ] `SITE_RULES.md` содержит Marketing Sections;
- [ ] Section Selection Rules добавлены;
- [ ] contracts документированы;
- [ ] allowed variants документированы;
- [ ] min/max items документированы;
- [ ] правила создания нового section документированы.

---

## 20.13. Tests / Review

- [ ] Functional test `/ui-kit/sections` проходит;
- [ ] static checks проходят;
- [ ] font files загружаются без ошибок;
- [ ] browser checks выполнены;
- [ ] `make ci` проходит;
- [ ] self-review выполнен;
- [ ] blocker/high self-review закрыты;
- [ ] Claude Code review выполнен;
- [ ] blocker/high external review закрыты;
- [ ] Stage Report подготовлен.

---

# 21. Главный критерий успеха Stage 2

После завершения Stage 2 команда должна иметь возможность получить задачу:

```text
«Создать новую маркетинговую страницу»
```

и сначала собрать её как:

```text
Hero
+ Problem
+ Feature Split
+ Steps
+ Case Preview
+ FAQ
+ CTA
```

из существующей библиотеки.

Если для обычной новой страницы необходимо снова проектировать новый visual section, Stage 2 считается архитектурно незавершённым.

---

# 22. Production Gate

Stage 2 не должен автоматически переходить к созданию production pages.

После завершения:

```text
Stage 2
→ review
→ утверждение библиотеки sections
→ отдельный Stage 3
```

Только Stage 3 начинает сборку реальной Главной страницы и работу с настоящим маркетинговым контентом.
