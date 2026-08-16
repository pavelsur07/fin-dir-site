# Правила публичного сайта «Ваш Финдир»

## 1. Статус документа

`SITE_RULES.md` — Source of Truth для публичного сайта. Все новые и изменяемые
публичные страницы, секции, Twig-компоненты и website assets обязаны
соответствовать этому документу.

Если нужного решения здесь нет, разработчик не создаёт новый визуальный
паттерн молча. Сначала он проходит алгоритм расширения из раздела 12 и при
обоснованной необходимости обновляет этот документ вместе с реализацией.

Legacy-шаблоны вне `site/templates/website/` мигрируют по отдельным задачам.
Новый код не должен копировать их page-specific решения.

## 2. Технологическая база

- Symfony;
- Twig;
- Bootstrap 5.3.3 — единственный базовый UI framework;
- CSS;
- JavaScript — только для необходимой функциональной интерактивности.

Правила:

- не подключать второй UI или CSS framework;
- использовать Bootstrap-компонент, если он корректно решает задачу;
- не писать собственный JavaScript-аналог Bootstrap-компонента;
- не добавлять CSS/JS-зависимость ради одного простого элемента;
- не добавлять JS только для декоративной анимации;
- не помещать CSS или JavaScript inline в Twig;
- подключать website assets централизованно в website layout.

Stage 1 использует существующий проектный способ доставки Bootstrap через
versioned jsDelivr URL с SRI. Если появится требование автономной доступности,
CSP или отдельное решение по передаче данных CDN, Bootstrap необходимо
завендорить локально без смены UI framework.

## 3. Структура

Twig:

```text
site/templates/website/
├── layouts/       # Общий каркас страницы, metadata и assets
├── pages/         # Композиция конкретных страниц
├── sections/      # Крупные переиспользуемые блоки
└── components/    # Малые UI-компоненты
```

CSS source:

```text
site/assets/styles/website/
├── tokens.css
├── base.css
├── components/
└── sections/
```

Статические файлы текущего Nginx публикуются из
`site/public/assets/website/`. Это проверяемое зеркало CSS source, а не второй
источник правил. Редактировать можно только source. После изменения выполнить
`make assets`, затем `make assets-check`. `make assets` сначала полностью
пересоздаёт зеркало, поэтому удалённый или переименованный source-файл не
остаётся в public.

Release query определяется один раз в website layout и равен первым 12 знакам
SHA-256 конкатенации всех CSS source в сортированном порядке. Значение выводит
`make asset-version`, а functional test проверяет соответствие. Это обязательно,
потому что Nginx отдаёт static assets с immutable cache.

Page преимущественно собирает sections. Section использует общий section
pattern и готовые components. Layout не содержит page-specific разметку.
Бизнес-логика, запросы к БД и вычисления не размещаются в Twig.

Page-specific CSS допускается только для уникального поведения, которое нельзя
выразить существующими tokens, components или sections. Причина фиксируется в
этом документе до добавления CSS.

## 4. Design Tokens

Единственный источник базовых визуальных значений —
`site/assets/styles/website/tokens.css`.

### 4.1. Color System

#### Иерархия

Цветовая система следует одному порядку приоритетов:

```text
1. Brand Dark / Navy — визуальный фундамент бренда
2. Brand Red — действие и небольшой акцент
3. Neutral Palette — текст, фон, поверхности и границы
4. Semantic Palette — только системные состояния
```

Большую часть визуального пространства занимают Dark/Navy и нейтральные
поверхности. Красный не используется как декоративный цвет всего сайта.

#### Brand и state tokens

| Token | HEX | Роль | Типичное применение |
|---|---|---|---|
| `--vf-color-dark` | `#0B1020` | Brand Dark / Navy, основа | dark surface и основной текст на light surface |
| `--vf-color-primary` | `#B00020` | Brand Red, accent/action | Primary CTA, важная ссылка, selected state, небольшой brand accent |
| `--vf-color-primary-hover` | `#8A0019` | Hover Brand Red | hover интерактивного primary action |
| `--vf-color-primary-active` | `#6C0014` | Pressed Brand Red | краткое active/pressed состояние primary action |
| `--vf-color-primary-soft` | `#F7E6E9` | Мягкая brand surface | локальный selected/active context без большой красной площади |
| `--vf-color-focus` | `#B00020` | Focus indicator на light surface | keyboard focus; на Brand Red заменяется на on-primary |
| `--vf-color-on-primary` | `#FFFFFF` | Контент на Brand Red | текст, icon и focus indicator поверх primary surface |

Brand Red `#B00020` и Brand Dark `#0B1020` зафиксированы и не заменяются
другими оттенками без отдельного brand/design решения. Существующий Hover
`#8A0019` сохранён. Active `#6C0014` продолжает ту же последовательность
снижения relative luminance:

```text
Default #B00020 → Hover #8A0019 → Active #6C0014
0.09334          → 0.05473       → 0.03239
```

Brand Red преимущественно используется для Primary CTA, активных/selected
состояний, важных ссылок, логотипа и небольших фирменных акцентов. Без
смысловой причины он не применяется для больших background, каждой иконки,
каждого заголовка, длинного текста, всех borders или декоративного чередования
секций.

Brand Dark используется для ключевых dark surfaces, dark-варианта
Hero/Header/Footer, основного текста на светлой поверхности и элементов с
высокой визуальной значимостью. Не создавать случайные «почти чёрные» или
navy-оттенки в компонентах.

#### Neutral Palette

| Token | HEX | Роль |
|---|---|---|
| `--vf-color-surface-dark` | `#0B1020` | ключевая dark surface |
| `--vf-color-background` | `#F5F6F8` | light page background |
| `--vf-color-surface` | `#FFFFFF` | light component/section surface |
| `--vf-color-text` | `#0B1020` | основной текст на light surface |
| `--vf-color-muted` | `#5C677D` | вторичный текст на light surface |
| `--vf-color-text-on-dark` | `#FFFFFF` | основной текст на dark surface |
| `--vf-color-muted-on-dark` | `#8E99AF` | вторичный текст на dark surface |
| `--vf-color-focus-on-dark` | `#FFFFFF` | keyboard focus на dark surface |
| `--vf-color-border` | `#D9DCE4` | декоративное разделение поверхностей |
| `--vf-color-border-strong` | `#767B85` | граница form/control, когда boundary должна иметь contrast |

Light Surface используется для основного контента, cards, controls и секций,
которым не требуется dark visual emphasis. `border` не обозначает состояние и
не заменяет `border-strong` у доступной границы интерактивного control.
Отдельный `border-on-dark` не вводится, пока реальный dark component не
потребует видимой границы. Любой интерактивный элемент на Dark Surface обязан
использовать `--vf-color-focus-on-dark`, а не Brand Red focus.

#### Semantic Palette

| Состояние | Foreground | Soft surface | Только для |
|---|---|---|---|
| Success | `--vf-color-success` `#146C43` | `--vf-color-success-soft` `#E9F5EE` | успешно завершённая операция |
| Warning | `--vf-color-warning` `#805B00` | `--vf-color-warning-soft` `#FFF3CD` | ситуация, требующая внимания |
| Danger | `--vf-color-danger` `#B02A37` | `--vf-color-danger-soft` `#F8D7DA` | ошибка или опасное действие |
| Info | `--vf-color-info` `#0B5CAD` | `--vf-color-info-soft` `#E8F1FB` | нейтральная системная информация |

Semantic Colors передают состояние и не используются для визуального
разнообразия sections, cards, badges или decorative accents. Состояние также
передаётся текстом/семантикой, а не только цветом.

#### Contrast и сочетания

Минимум: normal text `4.5:1`, large text `3:1`, UI component boundary/state
`3:1`. Проверенные базовые сочетания:

| Сочетание | Contrast |
|---|---:|
| White / Brand Red | `7.33:1` |
| White / Brand Red Hover | `10.03:1` |
| White / Brand Red Active | `12.74:1` |
| White / Brand Dark | `18.93:1` |
| Muted on Dark / Brand Dark | `6.60:1` |
| Focus on Dark / Brand Dark | `18.93:1` |
| Text / Light Background | `17.51:1` |
| Muted / Light Background | `5.26:1` |
| Strong Border / Surface | `4.25:1` |
| Success / Success Soft | `5.76:1` |
| Warning / Warning Soft | `5.55:1` |
| Danger / Danger Soft | `4.86:1` |
| Info / Info Soft | `5.85:1` |

Brand Red / Brand Dark имеет только `2.58:1`: Brand Red запрещён как мелкий
текст или тонкая UI-графика на Dark Surface. Такое сочетание допустимо только
после проверки конкретного крупного/декоративного случая, где цвет не несёт
смысл и соответствующий WCAG criterion не применяется.

#### Добавление цвета и запреты

Новый цвет добавляется только когда существующие Brand, Neutral и Semantic
tokens не выражают подтверждённую роль. Сначала определить семантическое
назначение, foreground/background context, состояния и contrast; затем
добавить token, обновить Color System и `/ui-kit`, добавить проверку. HEX вне
`tokens.css` запрещён как CSS/style value. Канонический HEX разрешено повторить
только как текстовую документацию в этом разделе и `/ui-kit`; functional test
обязан проверять его соответствие реальному token.

- NO third brand accent color without explicit design decision;
- NO arbitrary HEX outside `tokens.css`;
- NO decorative use of semantic colors;
- NO random shades of red;
- NO random dark/navy shades;
- NO gradients from brand colors without explicit requirement;
- NO color variation only to make neighboring sections look different;
- NO new color token without semantic purpose.

### 4.2. Typography

Primary font token:

```text
"TT Norms Pro", "TT Norms", Arial, sans-serif
```

Разрешённые веса: `400`, `500`, `700`.

| Style | Desktop | Mobile | Weight |
|---|---|---|---|
| H1 | 56px / 60px | 40px / 44px | 700 |
| H2 | 40px / 44px | 32px / 36px | 700 |
| H3 | 32px / 36px | 24px / 30px | 700 |
| H4 | 24px / 30px | 20px / 26px | 700 |
| Lead | 20px / 30px | 18px / 28px | 400 |
| Body | 16px / 24px | 16px / 24px | 400 |
| Small | 14px / 20px | 14px / 20px | 400 |
| Caption | 12px / 16px | 12px / 16px | 500 |

Font family, size, line-height и weight задаются tokens. Страница не создаёт
новый размер текста. На обычной публичной странице один H1; визуальная и
семантическая иерархия заголовков совпадают. Long-form text использует
`--vf-content-max`.

Файлы TT Norms Pro не подключены, пока право webfont-использования не
подтверждено. Запрещено копировать шрифт с `tochka.com`, hotlink-ить его или
иной чужой CDN. До легального подключения работает указанный fallback.

### 4.3. Spacing

Базовая единица — `4px`. Разрешённая шкала:

```text
4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 80 / 96
```

Правила:

- icon ↔ text: `8px`;
- button ↔ button: `12px`;
- form field ↔ form field: `16px`;
- title ↔ description: `16px`;
- обычные content blocks: `24px` или `32px`;
- cards grid gap: `24px`;
- card padding: desktop `24px`, mobile `16px`;
- section vertical padding: desktop `80px`, tablet `64px`, mobile `48px`.

Использовать только `--vf-space-*` и семантические tokens. Значения вроде
`37px`, `53px` и `71px` ради визуальной подгонки запрещены.

`--vf-control-min-height: 44px` — не spacing, а минимальная touch target height
для интерактивного control. `--vf-showcase-item-min` применяется только в
техническом UI-kit для читаемой раскладки token previews.

### 4.4. Containers, borders, radius, shadows

- основной container: Bootstrap `.container`, максимум
  `--vf-container-max: 1200px`;
- ширина чтения: `--vf-content-max: 720px`;
- горизонтальный gutter: `16px` mobile, `24px` начиная с tablet;
- borders используют `--vf-border-width` и `--vf-color-border`;
- линии функциональных CSS-иконок используют `--vf-icon-stroke-width`;
- radius: `--vf-radius-sm`, `--vf-radius-md`, `--vf-radius-lg`;
- shadow допускается только через `--vf-shadow-card` и только когда помогает
  отделить интерактивную или приподнятую поверхность.

Не создавать `.container-home`, `.container-hero` или аналогичные контейнеры.
Не увеличивать radius и shadow ради декоративного разнообразия.

### 4.5. Breakpoints

Используются Bootstrap breakpoints:

```text
sm 576px
md 768px
lg 992px
xl 1200px
xxl 1400px
```

Их tokens документируют общую шкалу. CSS custom properties нельзя применять
в media query, поэтому media query повторяет только эти зафиксированные
значения. Новый breakpoint без отдельного адаптивного сценария запрещён.

## 5. Базовые Components

Stage 1 определяет:

- Button;
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

Production Twig partial является единственной реализацией компонента. UI-kit и
будущие страницы подключают тот же partial. Bootstrap отвечает за базовую
семантику и интерактивность; VF CSS только применяет tokens и согласованные
состояния.

CTA использует токенизированный Button variant `on-primary`, предназначенный
только для действия на primary surface и показанный внутри CTA на `/ui-kit`.

Для применимых компонентов обязательны Default, Hover, Focus, Active,
Disabled, Error и Success. Focus должен быть виден с клавиатуры. Disabled
элемент не должен выглядеть доступным. Error/Success передаются не только
цветом, но и текстом состояния.

## 6. Section Architecture

Общий pattern:

```text
section.vf-section
└── .container
    └── content
```

`sections/_base.html.twig` владеет каркасом. Stage 1 содержит Hero, Content
Section и CTA Section. Секция не задаёт собственные container width,
typography scale, button system, card system или breakpoints.

Hero не раздувается декоративным пространством. CTA Section использует общий
CTA component. Новая секция сначала комбинирует существующие components и
общий section pattern; global CSS для каждой новой секции не меняется.

## 7. Grid и responsive

Bootstrap container/grid используется последовательно. Mobile — состояние того
же компонента, не отдельный template или отдельная версия сайта.

Обязательные контрольные ширины: `375px`, `768px`, `1024px`, `1440px`.
На каждой ширине проверяются:

- отсутствие horizontal overflow;
- работа Navbar и Accordion;
- попадание forms и text в viewport;
- доступность кнопок;
- перестроение cards;
- иерархия Hero;
- адаптивные typography и section spacing;
- доступность интерактивных элементов с клавиатуры.

Не скрывать дефект layout глобальным `overflow-x: hidden`.

## 8. Accessibility baseline

- использовать semantic HTML landmarks;
- соблюдать последовательность H1 → H2 → H3;
- связывать каждый form control с `label`;
- использовать `button` для действия и `a` для навигации;
- давать интерактивному элементу понятное accessible name;
- обеспечивать keyboard navigation и видимый `:focus-visible`;
- задавать содержательный `alt` meaningful image;
- декоративное изображение задавать как `alt=""` и исключать из
  accessibility tree при необходимости;
- не передавать критическое состояние только цветом;
- сохранять достаточный contrast;
- учитывать `prefers-reduced-motion`, если motion когда-либо появится.

Stage 1 не добавляет изображения и декоративную анимацию.

## 9. UI-kit

`/ui-kit` — визуальный Source of Truth Design System. Он использует website
layout и production components/sections, а не демонстрационные копии.

На одной странице показаны Typography, Colors, Spacing, Buttons, Badges,
Cards, Alerts, Forms, Accordion, Breadcrumbs, Navigation, Hero, Content
Section и CTA. Основной H1 только один. Состояния компонентов должны быть
доступны для визуальной и клавиатурной проверки.

## 10. CSS и JavaScript

- NO inline CSS (`style=""` и `<style>`);
- NO inline JavaScript;
- external `<script src>` допускается только для согласованной функциональности;
- NO arbitrary colors, typography, spacing или border-radius;
- NO page-specific copies of reusable components;
- NO duplicated Twig components;
- NO desktop-only components;
- NO second UI framework;
- NO new component, если существующий решает задачу.

CSS components/sections использует tokens. Hardcoded base values допустимы
только внутри `tokens.css`; технические media queries используют только
зафиксированные Bootstrap breakpoints.

UI-kit-only styles находятся в `components/showcase.css` и подключаются только
страницей `/ui-kit`, а не общим production layout. Исключение — классы
симуляции состояния `vf-is-*`: они задаются production partial только по
явному параметру UI-kit и находятся в одном declaration block с реальным
pseudo-class. Такое co-location гарантирует, что демонстрация Focus/Hover не
расходится с production-состоянием.

## 11. AI-generated UI anti-patterns

**The agent MUST NOT introduce visual variety only for decorative purposes.**

Новый визуальный pattern обязан решать функциональную, информационную,
иерархическую, адаптивную или accessibility-задачу. «Чтобы выглядело
интереснее» — недостаточная причина.

Без явной смысловой причины запрещены:

- чрезмерное использование Cards и Card inside Card;
- повторение `heading → text → 3 equal cards` в большинстве секций;
- badge/pill или decorative icon возле каждого заголовка и пункта;
- gradients, glow, glassmorphism, decorative blur и blobs;
- случайные abstract background shapes и floating elements;
- чрезмерные radius и shadows;
- центрирование всей страницы или длинного текста;
- отдельный цветной фон почти для каждой секции;
- автоматическое чередование фонов ради разнообразия;
- oversized Hero с малым количеством полезной информации;
- новый layout/variant только для внешнего отличия соседних секций;
- fake dashboard, chart, product screenshot, customer metric, testimonial,
  company logo или декоративная статистика;
- элементы, выглядящие интерактивными, но ничего не делающие;
- fake data used as visual decoration.

Предпочтительный порядок: content and hierarchy first, decoration second.
Основные инструменты — typography, whitespace, content hierarchy, clear grid,
consistent spacing, reusable components, реальные данные и изображения.

## 12. Алгоритм расширения

До создания UI pattern ответить по порядку:

1. Есть ли подходящее решение в Bootstrap 5?
2. Есть ли уже VF Component?
3. Есть ли уже VF Section Pattern?
4. Можно ли расширить существующий component без изменения его смысла?
5. Можно ли решить задачу комбинацией существующих components?
6. Какую конкретную функциональную, информационную, адаптивную или
   accessibility-проблему решает новый pattern?
7. Только после этого создать минимальный pattern и добавить его правило в
   `SITE_RULES.md`.

Новая страница не является причиной для «немного другого дизайна» уже
существующего компонента.

## 13. Проверки изменения

Минимум:

```bash
make assets
make asset-version  # перенести результат в vf_asset_version в website layout
make assets-check
make lint
make cs
make phpstan
make deptrac
make test
```

Дополнительно для website UI:

- functional test `GET /ui-kit → 200`;
- рендер production sections/components;
- static scan `site/templates/website` на inline CSS/JS;
- static scan component/section CSS на hardcoded colors;
- browser checks на `375/768/1024/1440`;
- keyboard/focus и accessibility audit;
- visual review по AI anti-patterns.

Не утверждать, что browser, Lighthouse или external review пройдены, если они
фактически не запускались.
