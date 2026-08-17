# Правила публичного сайта «Ваш Финдир»

## 1. Статус документа

`SITE_RULES.md` — Source of Truth для публичного сайта. Все новые и изменяемые
публичные страницы, секции, Twig-компоненты и website assets обязаны
соответствовать этому документу.

Если нужного решения здесь нет, разработчик не создаёт новый визуальный
паттерн молча. Сначала он проходит алгоритм расширения из раздела 13 и при
обоснованной необходимости обновляет этот документ вместе с реализацией.

Legacy-шаблоны вне `site/templates/website/` мигрируют по отдельным задачам.
Новый код не должен копировать их page-specific решения.

## 2. Технологическая база

- Symfony;
- Twig;
- Tailwind CSS `4.3.3` — единственный базовый UI framework;
- CSS — один source entrypoint и один compiled production asset;
- website JavaScript — отдельные project-owned файлы только для согласованной интерактивности;
- JavaScript — минимально и только когда native HTML недостаточен.

Правила:

- не подключать второй UI или CSS framework;
- использовать Tailwind utilities внутри существующего VF Component/Section;
- сначала использовать native HTML для disclosure и другой простой интерактивности;
- не добавлять CSS/JS-зависимость ради одного простого элемента;
- не добавлять JS только для декоративной анимации;
- не помещать CSS или JavaScript inline в Twig;
- подключать website assets централизованно в website layout.

Tailwind используется только на build-time. Browser получает локальный
compiled CSS без Tailwind CDN/runtime. Bootstrap запрещён для нового и
изменяемого Public Website code и не участвует в CSS, JavaScript или layout.

## 3. Структура

Twig:

```text
site/templates/website/
├── layouts/       # Общий каркас страницы, metadata и assets
├── pages/         # Композиция конкретных страниц
├── sections/      # Крупные переиспользуемые блоки
└── components/    # Малые UI-компоненты
```

CSS source и build wrapper:

```text
site/assets/styles/website/
└── app.css

site/assets/scripts/website/
└── navigation.js

scripts/
└── tailwindcss.sh
```

Статические файлы текущего Nginx публикуются из
`site/public/assets/website/`: compiled `app.css` и копия `navigation.js`.
Это generated output, его нельзя редактировать вручную. `make assets`
компилирует CSS и копирует project-owned JavaScript, `make assets-watch`
запускает CSS watch после копирования JavaScript, `make assets-check`
повторяет production build во временную директорию и ловит drift обоих
файлов. Standalone CLI `4.3.3` загружается в
ignored `site/var/tools`, проверяется pinned SHA-256 и не попадает в production
image/runtime. Node и package manager проекту для website build не нужны.

Release query определяется один раз в website layout и равен первым 12 знакам
SHA-256 конкатенации `app.css` и `navigation.js` в этом порядке. Значение
выводит `make asset-version`, а functional test проверяет соответствие. Это
обязательно, потому что Nginx отдаёт static assets с immutable cache.

Page преимущественно собирает sections. Section использует общий section
pattern и готовые components. Layout не содержит page-specific разметку.
Бизнес-логика, запросы к БД и вычисления не размещаются в Twig.

Page-specific CSS допускается только для уникального поведения, которое нельзя
выразить существующими tokens, components или sections. Причина фиксируется в
этом документе до добавления CSS.

## 4. Design Tokens

Единственный источник базовых визуальных значений — token/theme blocks в
`site/assets/styles/website/app.css`. Канонические VF tokens используют
`--vf-*`; Tailwind `@theme inline` только связывает semantic utility names с
ними и не содержит независимых копий значений.

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
| `--vf-color-dark` | `#0B1020` | Brand Dark / Navy, ключевой визуальный фундамент | Hero Dark и основной текст на light surface |
| `--vf-color-primary` | `#B00020` | Brand Red, accent/action | Primary CTA, важная ссылка, selected state, небольшой brand accent |
| `--vf-color-primary-hover` | `#8A0019` | Hover Brand Red | hover интерактивного primary action |
| `--vf-color-primary-active` | `#6C0014` | Pressed Brand Red | краткое active/pressed состояние primary action |
| `--vf-color-primary-soft` | `#F7E6E9` | Мягкая brand surface | локальный selected/active context без большой красной площади |
| `--vf-color-focus` | `#005FCC` | Функциональный Focus на light surface | keyboard focus; визуально отделён от Brand Red и Danger |
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

Большой Brand Red background разрешён только для ключевого conversion CTA или
другого явно обоснованного action block. По умолчанию на странице допускается
не более одного крупного Brand Red conversion block. Brand Red не используется
как обычный section background.

Brand Dark — ключевой визуальный фундамент бренда. Он используется для Hero
Dark, основного текста на светлой поверхности и элементов с высокой визуальной
значимостью. Не создавать случайные «почти чёрные» или navy-оттенки в
компонентах. Нейтральный Dark Surface не является третьим brand color.

Focus `#005FCC` — функциональный accessibility color, а не дополнительный
brand accent. Focus и Danger обязаны различаться не только текстом состояния,
но и цветом: blue focus ring не заменяется Brand Red или Danger.

#### Neutral Palette

| Token | HEX | Роль |
|---|---|---|
| `--vf-color-surface-dark` | `#1E2331` | нейтральная dark surface для Footer и локальных dark contexts |
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

Tailwind semantic aliases не вводят новые значения: `bg-brand-red`,
`bg-brand-dark`, `bg-surface-dark`, `bg-page`, `bg-surface`, `text-content`,
`text-muted`, `text-on-dark`, `text-muted-on-dark`, `border-border-default`,
`border-border-strong`, `text-success`, `bg-success-soft` и остальные state
utilities ссылаются через `@theme inline` на соответствующий `--vf-color-*`.
Default Tailwind palette полностью отключена; случайные `red-500`, `blue-600`,
`slate-700` и `gray-100` недоступны как project tokens.

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
| White / Dark Surface | `15.67:1` |
| Muted on Dark / Dark Surface | `5.47:1` |
| Focus / Light Surface | `5.98:1` |
| Focus / Light Background | `5.53:1` |
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
canonical token block `app.css` запрещён как CSS/style value. Канонический HEX разрешено повторить
только как текстовую документацию в этом разделе и `/ui-kit`; functional test
обязан проверять его соответствие реальному token.

- NO third brand accent color without explicit design decision;
- NO arbitrary HEX outside canonical token block `app.css`;
- NO decorative use of semantic colors;
- NO random shades of red;
- NO random dark/navy shades;
- NO gradients from brand colors without explicit requirement;
- NO color variation only to make neighboring sections look different;
- NO new color token without semantic purpose.

### 4.2. Typography

Primary font token:

```text
"Onest", Arial, sans-serif
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

Onest — единственный primary font Public Website. Variable WOFF2 версии 2.001
получен из официального Open Font distribution, хранится локально в
`site/public/assets/fonts/onest/` вместе с OFL 1.1 и подключается через общий
`@font-face` с `font-display: swap`. Runtime-запросы шрифта к внешнему CDN
запрещены. Используются только веса `400`, `500`, `700`; другие веса требуют
реального сценария. Sections и pages не задают произвольный `font-family`.

Typography scale проверяется на реальном Onest. Плохой перенос demo-текста не
исправляется `<br>` или page-specific размером: сначала корректируются текст,
width constraint или центральный typography token. Stage 2 не потребовал
изменения существующих size и line-height tokens.

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

В Tailwind используется базовый шаг `4px` и только numeric classes `0`, `1`,
`2`, `3`, `4`, `6`, `8`, `12`, `16`, `20`, `24`. Runtime aliases
`--vf-space-*` вычисляются от Tailwind `--spacing` и не являются второй
независимой шкалой. Значения вроде `p-5`, `gap-7`, `37px`, `53px` и `71px`
ради визуальной подгонки запрещены.

`--vf-control-min-height: 44px` — не spacing, а минимальная touch target height
для интерактивного control. `--vf-grid-item-min: 240px` задаёт минимальную
читаемую ширину равноправного элемента в responsive Grid и его UI-kit previews.

### 4.4. Containers, borders, radius, shadows

- основной website container: `mx-auto w-full max-w-site px-4 md:px-6`,
  максимум `--vf-container-max: 1200px`;
- ширина чтения: `--vf-content-max: 720px`;
- максимальная ширина mobile Drawer: `--vf-navigation-drawer-max: 360px`;
- горизонтальный gutter: `16px` mobile, `24px` начиная с tablet;
- borders используют Tailwind `border` (`1px`) и `--vf-color-border`;
- линии функциональных CSS-иконок используют `border-2` (`2px`);
- radius: `--vf-radius-sm`, `--vf-radius-md`, `--vf-radius-lg`;
- shadow допускается только через `--vf-shadow-card` и только когда помогает
  отделить интерактивную или приподнятую поверхность.

Не создавать `.container-home`, `.container-hero` или аналогичные контейнеры.
Не увеличивать radius и shadow ради декоративного разнообразия.

### 4.5. Breakpoints

Tailwind breakpoints настроены явно и сохраняют исходный responsive intent:

```text
sm 576px
md 768px
lg 992px
xl 1200px
2xl 1400px
```

Значения находятся в Tailwind `@theme`, потому что CSS custom properties нельзя
применять в media query. Default breakpoints не принимаются молча. Новый
breakpoint без отдельного адаптивного сценария запрещён.

### 4.6. Native Browser Scrollbar

> Public Website MUST use the browser/OS native scrollbar for document scrolling. Do not style the main page scrollbar or its track/thumb.

Browser, operating system and user settings fully control the document
scrollbar on every Public Website page, including `/ui-kit` and
`/ui-kit/sections`. Local functional scroll areas also use native scrollbars by
default; any customization requires a separate confirmed UX decision.

Rules:

- NO custom document scrollbar;
- NO custom scrollbar track color;
- NO forced scrollbar width;
- NO fake scrollbar gutter;
- NO document rules for `::-webkit-scrollbar*`, `scrollbar-color`,
  `scrollbar-width`, `scrollbar-gutter` or forced `overflow-y: scroll`;
- NO `padding-right`, `padding-inline-end`, `margin-right` or background strip
  used to imitate or compensate the document scrollbar.
- NO forced document `color-scheme` whose purpose or effect is to override the
  browser/OS scrollbar appearance.

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
будущие страницы подключают тот же partial. Стили задаются Tailwind utilities
непосредственно в production Twig partial; варианты используют полные
статические class strings. Accordion использует native `details`/`summary` без
JavaScript. Mobile Navbar использует native modal `<dialog>` и минимальный
`navigation.js`, потому что off-canvas lifecycle не является disclosure.

### 5.1. Navbar

- Header на mobile всегда однострочный: внутренняя строка имеет высоту
  `64px`, а полная высота с нижним border — `65px`;
- Menu и Close имеют touch target `44×44px`, accessible labels и visible focus;
- mobile navigation работает до project breakpoint `lg`, desktop navigation — начиная с `lg`;
- Drawer открывается через `showModal()`, не участвует в document flow,
  имеет `100dvh`, `width: min(88vw, 360px)` и внутренний vertical scroll;
- modal backdrop токенизирован через Brand Dark с neutral opacity, но не является
  новым brand color;
- backdrop click, Close и `Esc` закрывают Drawer; после закрытия focus
  возвращается на Menu;
- пока modal открыта, background inert и не скроллится;
- scroll lock удерживает document в текущей позиции, не меняя
  native scrollbar, `html`/`body` overflow и не добавляя fake gutter;
- пункты Drawer выровнены слева, а active state обозначается Brand Red text
  и `aria-current="page"` без Card, pill и большого red background;
- motion короткий (`200ms`, `ease-out`) и отключается при `prefers-reduced-motion`;
- mobile Drawer и desktop links живут в одном production Navbar partial; отдельный
  UI-kit menu запрещён.

CTA использует токенизированный Button variant `on-primary`, предназначенный
только для действия на primary surface и показанный внутри CTA на `/ui-kit`.

Для применимых компонентов обязательны Default, Hover, Focus, Active,
Disabled, Error и Success. Focus должен быть виден с клавиатуры. Disabled
элемент не должен выглядеть доступным. Error/Success передаются не только
цветом, но и текстом состояния.

## 6. Section Architecture

Общий pattern:

```text
section[data-vf-section]
└── website container utilities
    └── content
```

`sections/_base.html.twig` владеет каркасом. Stage 1 содержит Hero, Content
Section и CTA Section. Секция не задаёт собственные container width,
typography scale, button system, card system или breakpoints.

Hero не раздувается декоративным пространством. CTA Section использует общий
CTA component. Новая секция сначала комбинирует существующие components и
общий section pattern; global CSS для каждой новой секции не меняется.

Hero Dark — production variant ключевого Hero на Brand Dark `#0B1020`. Он
использует `text-on-dark`, `muted-on-dark` и `focus-on-dark` и показывает
визуальный фундамент бренда. Footer Dark — production variant на нейтральном
Dark Surface `#1E2331` с теми же on-dark content tokens. Dark не применяется
автоматически ко всем sections; новый dark context требует конкретной
иерархической причины. Navbar Dark не вводится, пока для него нет отдельного
пользовательского сценария.

## 7. Marketing Sections

### 7.1. Доступные layout patterns

Marketing section всегда использует `sections/_base.html.twig`, общий
website container pattern, typography/spacing tokens и production components. Доступен
минимальный набор структур:

| Pattern | Когда применять | Ограничение |
|---|---|---|
| Text / Content | один связный смысловой блок | long-form ограничен `--vf-content-max` |
| Text + List | вводный текст и зависимый перечень | не превращать каждый пункт в Card |
| Split Text + Media | одна feature и подтверждающее media | один partial; `left` или `right` |
| Grid | действительно равноправные элементы | 2, 3, 4 или 6 без count-specific CSS |
| Sequential Steps | действия имеют порядок | 2–5 шагов, semantic `ol` |
| Two Paths | пользователь выбирает один из двух сценариев | строго 2 пути |
| Quote | одна подтверждённая цитата | не имитировать клиента demo-текстом |
| Comparison | варианты сравниваются по одинаковым критериям | semantic table; 2–3 варианта, 3–8 строк |
| Form + Context | объяснение и короткая lead/diagnostic form | только production form controls |
| CTA | одно ключевое conversion action | production CTA Stage 1 |

Hero не является новым pattern Stage 2: используется production Hero Stage 1
в разрешённых `light`/`dark` variants. Left/right Split — один pattern, а не
два шаблона. Mobile DOM order всегда `content → media`; визуальный `media-left`
применяется только начиная с tablet. Grid не является универсальной заменой
обычного списка, а Steps не используется для независимых элементов. Text /
Content реализуется общим Text + List partial без `items`, поэтому отдельный
template и CSS для него не создаются.

### 7.2. Правила выбора

| Need / content type | Рекомендуется | Не использовать |
|---|---|---|
| Узнаваемые проблемы | Problem / Text + List | Card для каждого тезиса по умолчанию |
| Равноправные outcomes | Benefits / Grid или List | отдельный декоративный язык Benefits |
| Одна feature + media | Feature Split | Grid независимых Cards |
| Последовательность действий | Steps | Grid независимых Cards |
| Два разных следующих сценария | Two Paths | Comparison table без общих критериев |
| Одинаковые критерии нескольких вариантов | Comparison | Two Paths для трёх и более вариантов |
| Вопросы и ответы | FAQ / production Accordion | новый custom collapse или JS |
| Контекст перед короткой формой | Lead / Form + Context | новая реализация form controls |
| Финальное ключевое действие | production CTA | новый CTA layout |

Состав страницы определяется информационной задачей. Новая production page не
является основанием для новой section. Не требуется использовать все patterns,
не допускается декоративное чередование patterns или backgrounds.

### 7.3. Контракты production sections

Во всех контрактах `id` опционален; `eyebrow` и вводный `text` опциональны,
если ниже явно не указано обратное. Пустой optional блок не рендерится.

Общие defaults: безопасный технический `id` задаётся partial; `eyebrow`,
actions, lists, details, links, media, source и note отсутствуют; массивы пусты;
Hero использует `light`, Split — `media_position=right`, demo media выключен;
link label Text + List — `Подробнее`, Lead button label — `Продолжить`.
Comparison использует `criterion_label=Критерий`, а `table_label` по умолчанию
равен `title`. Optional actions в Grid, Split и Two Paths используют production
Button variant `outline-primary`.

Связь semantic role с фактическим Twig API:

| Role | Production partial | `marker` / section name |
|---|---|---|
| Hero | `sections/_hero.html.twig` | `hero`, фиксирован |
| Problem | `sections/_text_list.html.twig` | required `marker: problem` |
| Benefits | `sections/_grid.html.twig` | required `marker: benefits` |
| Feature | `sections/_split.html.twig` | default `feature` |
| Steps | `sections/_steps.html.twig` | default `steps` |
| Two Paths | `sections/_paths.html.twig` | default `paths` |
| Case Preview | `sections/_case_preview.html.twig` | `case-preview`, фиксирован |
| Proof | `sections/_text_list.html.twig` | required `marker: proof` |
| Quote | `sections/_quote.html.twig` | `quote`, фиксирован |
| Pricing Preview | `sections/_grid.html.twig` | required `marker: pricing` |
| Comparison | `sections/_comparison.html.twig` | `comparison`, фиксирован |
| FAQ | `sections/_faq.html.twig` | `faq`, фиксирован |
| Lead Form | `sections/_lead_form.html.twig` | `lead-form`, фиксирован |
| Article Preview | `sections/_grid.html.twig` | required `marker: article-preview` |
| CTA | `sections/_cta.html.twig` | `cta`, фиксирован |

`marker` — обязательный semantic identifier при использовании общего partial в
production role; произвольный marker на production page не создаёт новый
section contract.

| Section | Purpose | Required | Optional / defaults | Variants и count | Reuse | Не применять |
|---|---|---|---|---|---|---|
| Hero | открыть страницу и сформулировать предложение | `title`, `text` | `eyebrow`, actions; variant `light` | `light`, `dark`; 0–2 actions | Hero + Button Stage 1 | как декоративную пустую заставку |
| Problem | помочь узнать релевантную проблему | `title`, `items[].text` | item title/link | 3–6 items | Text + List | для независимых benefits |
| Benefits | описать outcomes, а не функции | `title`, `items[].title` | item text/details/action | 2–6 items | Grid или Text + List | как переименование Feature |
| Feature Split | объяснить одну возможность и её media | `title`, `text` | `items`, `action`, `media`; position `right` | `left`, `right`; одна feature | Split + Button | для 5 независимых преимуществ |
| Steps | показать последовательность | `title`, `steps[].title`, `steps[].text` | intro text | 2–5 steps | Sequential Steps | если порядок не имеет значения |
| Two Paths | дать выбор двух сценариев | `title`, ровно два `paths` с title/text | path list/action | ровно 2 paths | Paths + Button | как good/bad или для 3+ вариантов |
| Case Preview | кратко показать подтверждённый кейс | `title`, `items[].label`, `items[].text` | intro text | ровно 3 labels: Problem/Action/Result | Case flow | без реального материала или с fake metrics |
| Proof | показать проверяемые основания доверия | `title`, `items[].text` | item title/link | 1–6 items | Text + List | для fake awards, logos или статистики |
| Quote | показать согласованную цитату | `title`, `quote` | `source` | одна цитата | Quote | без подтверждённого источника; demo как отзыв |
| Pricing Preview | сравнить подтверждённые предложения | `title`, `items[].title` | item text/details | 2–4 items | Grid | для fake prices или popularity badge |
| Comparison | сравнить одинаковые критерии | `title`, `caption`, alternatives, rows | labels | 2–3 alternatives; 3–8 rows | semantic table | если нет общих критериев |
| FAQ | ответить на частые вопросы | `title`, question/answer items | intro text | 3–6 questions | Accordion Stage 1 | для произвольного длинного контента |
| Lead Form | дать контекст и короткую demo/lead form | `title`, `text` | context list, `action_label`, note | один form block | form controls + Button Stage 1 | без backend flow выдавать form за рабочую |
| Article Preview | анонсировать подтверждённые материалы | `title`, `items[].title` | item text/action | 2–4 items | Grid | для fake dates, authors или metrics |
| CTA | завершить сценарием действия | `title`, `text`, action label/href | нет | один крупный CTA по умолчанию | CTA + Button Stage 1 | как обычный section background |

Semantic roles `Problem`, `Benefits`, `Proof`, `Pricing` и `Article Preview`
передаются общим Text/List или Grid patterns через документированный `marker`:
отдельные wrapper и CSS-копии для них запрещены. FAQ делегирует production
Accordion; CTA и Hero переиспользуют Stage 1 без v2 templates. Lead demo
использует `type="button"`: backend, CRM/API, сохранение и отправка данных в
Stage 2 отсутствуют.

### 7.4. Media и demo content

Реальное meaningful media требует содержательный `alt`. Декоративное media
использует пустой `alt`; технический `Demo media` placeholder существует
только на UI-kit, имеет ratio `16:10` и скрыт от accessibility tree. Fake
dashboard, screenshot, customer photo, logo и decorative metric запрещены.

Demo content пишется по-русски, имеет реалистичную длину и явно обозначается
как demo. Запрещены Lorem Ipsum, вымышленные клиенты, должности, компании,
отзывы, awards, цены, даты публикаций и business metrics. `/ui-kit/sections` —
`noindex` технический каталог production partials, не маркетинговая страница и
не источник реальных продуктовых утверждений.

### 7.5. Решение о новой section

Перед созданием нового marketing section последовательно ответить:

1. Есть ли существующий marketing section?
2. Есть ли существующий layout pattern?
3. Можно ли решить задачу новым content contract без нового CSS?
4. Можно ли расширить существующий pattern без изменения его смысла?
5. Какую конкретную проблему решает новый section?
6. Почему существующие sections не подходят?

Только после отрицательных ответов на вопросы о reuse и конкретного ответа на
последние два вопроса допускается минимальный новый pattern с обновлением этого
документа и `/ui-kit/sections`.

## 8. Grid и responsive

Tailwind `grid`/`flex`, project breakpoints и website container используются
последовательно. Mobile — состояние того же компонента, не отдельный template
или отдельная версия сайта.

Обязательные контрольные ширины: `320px`, `375px`, `768px`, `1024px`, `1440px`.
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

## 9. Accessibility baseline

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

Stage 1 не добавляет изображения и декоративную анимацию. Stage 2 добавляет
только технический media placeholder; он скрыт от accessibility tree.

## 10. UI-kit

`/ui-kit` — визуальный Source of Truth foundations/components. Он использует
website layout и production components/sections, а не демонстрационные копии.

На одной странице показаны Typography, Colors, Spacing, Buttons, Badges,
Cards, Alerts, Forms, Accordion, Breadcrumbs, Navigation, Dark Hero, Content
Section, CTA и Dark Footer. `/ui-kit/sections` — отдельный технический каталог
Marketing Sections, typography stress cases и layout variants. На каждой
странице основной H1 только один. Состояния компонентов должны быть доступны
для визуальной и клавиатурной проверки.

## 11. CSS и JavaScript

- NO inline CSS (`style=""` и `<style>`);
- NO inline JavaScript;
- external `<script src>` допускается только для согласованной функциональности;
- NO arbitrary Tailwind values (`[...]`) по умолчанию;
- NO dynamic classname fragments (`bg-{{ ... }}`, `text-{{ ... }}`);
- variants задаются только полными статическими class maps;
- только project palette; default Tailwind color palette отключена;
- только утверждённая numeric spacing scale;
- NO page-specific copies of reusable components;
- NO duplicated Twig components;
- NO desktop-only components;
- NO second UI framework;
- NO new component, если существующий решает задачу.
- NO custom document scrollbar;
- NO custom scrollbar track color;
- NO forced scrollbar width;
- NO fake scrollbar gutter.

Custom CSS допустим для `@font-face`, canonical theme/base foundation,
accessibility behavior и layout, который utilities выражают существенно хуже.
Текущие обоснованные custom utilities: auto-fit project Grid, минимальная
ширина Comparison table и `min(88vw, 360px)` для off-canvas Drawer. Custom CSS
допускается для Drawer transform/backdrop transition, потому что это stateful
behavior native `<dialog>`, а не новый визуальный вариант. Custom CSS не
используется как привычный способ писать component stylesheet вместо Tailwind.

UI-kit showcase использует те же production partials и обычные Tailwind
utilities. Принудительные Hover/Focus/Active состояния берутся из статических
maps того же production component, поэтому не расходятся с pseudo-states.
Tailwind source scanning ограничен `site/templates/website/`; `public`,
`vendor` и generated files не сканируются.

## 12. AI-generated UI anti-patterns

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

## 13. Алгоритм расширения

До создания UI pattern ответить по порядку:

1. Есть ли уже VF Component?
2. Есть ли уже VF Section/Layout Pattern?
3. Можно ли решить задачу Tailwind utilities внутри существующего component?
4. Можно ли расширить существующий component без изменения его смысла?
5. Можно ли решить задачу комбинацией существующих components?
6. Какую конкретную функциональную, информационную, адаптивную или
   accessibility-проблему решает новый pattern?
7. Только после этого создать минимальный pattern и добавить его правило в
   `SITE_RULES.md`.

Новая страница не является причиной для «немного другого дизайна» уже
существующего компонента.

## 14. Проверки изменения

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

- functional tests `GET /ui-kit → 200` и `GET /ui-kit/sections → 200`;
- рендер production sections/components;
- static scan `site/templates/website` на inline CSS/JS;
- static scan на Bootstrap runtime/classes, arbitrary values и dynamic Tailwind fragments;
- `make assets-check` для повторного Tailwind build и asset drift;
- browser checks на `320/375/768/1024/1440`;
- keyboard/focus и accessibility audit;
- visual review по AI anti-patterns.

Не утверждать, что browser, Lighthouse или external review пройдены, если они
фактически не запускались.
