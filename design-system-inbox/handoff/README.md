# Ваш Финдир · Design System v2.0 — внедрение в прод

Пакет для разработки. Цель — чтобы любая вёрстка продукта (кабинет на React, сайт на Symfony/Twig) собиралась **только из токенов и компонентов**, без инлайн-стилей и «сырых» значений, и не расходилась с дизайн-системой.

- Эталон визуала и правил: `Design System.dc.html` (макет в проекте).
- Эталон вёрстки: `handoff/design-system.html` — та же система классами, без единого `style="…"`.

---

## 1. Состав пакета

```
handoff/
├─ README.md                      ← этот документ
├─ design-system.html             ← эталонная вёрстка (открыть в браузере)
├─ tokens/
│  ├─ tokens.json                 ← ИСТОЧНИК ПРАВДЫ (W3C DTCG)
│  ├─ tokens.css                  ← CSS-переменные --vf-* (сборка из json)
│  └─ style-dictionary.config.mjs ← сборка json → css / ts / figma
├─ css/
│  ├─ base.css                    ← сброс, типографика .vf-t1…t8, раскладка
│  ├─ components.css              ← эталон компонентов .vf-*
│  └─ demo.css                    ← только для витрины, в прод не подключать
├─ lint/
│  ├─ .stylelintrc.json           ← запрет hex, px-значений, !important
│  └─ eslint.config.js            ← запрет style={{}}, hex в JSX
├─ react/                         ← образцы компонентов @vashfindir/ui
│  ├─ Button.tsx + Button.module.css
│  ├─ Stack.tsx                   ← Stack / Cluster / Grid / Container
│  └─ Logo.tsx
└─ twig/
   └─ Button.html.twig            ← зеркало React-компонента для Symfony UX
```

---

## 2. Принципы

1. **Один источник правды.** Все значения живут в `tokens.json`. CSS, TS и Figma-переменные генерируются, руками не правятся.
2. **Код не знает цветов.** В компонентах и страницах — только `var(--vf-color-*)`, `var(--vf-space-*)` и т. д. Hex, rgb(), px для отступов, радиусов, шрифтов и теней — запрещены линтером.
3. **Внешний вид — через props, а не через стили.** `<Button variant="secondary" size="md">`, а не `className="my-grey-button"`.
4. **Расстояния — через gap.** Соседи раскладываются примитивами `Stack / Cluster / Grid` с `gap` из шкалы. Margin между компонентами не используется.
5. **Тема — это переопределение семантики.** Компонент одинаков на светлом и тёмном фоне; меняется только набор `--vf-color-*` внутри `[data-theme="dark"]`.
6. **Автоматические проверки вместо договорённостей.** Всё, что можно проверить машиной, — проверяется в CI и блокирует merge.

---

## 3. Архитектура токенов

Три уровня. В коде продукта используются **только уровни 2 и 3**.

| Уровень | Пример | Где используется |
|---|---|---|
| 1. Примитивы | `--vf-ink-950`, `--vf-crimson-700`, `--vf-space-4` | только внутри tokens.css для сборки семантики |
| 2. Семантика | `--vf-color-fg-muted`, `--vf-color-accent`, `--vf-color-bg-subtle` | компоненты, страницы |
| 3. Компонентные | `--vf-button-primary-bg`, `--vf-input-ring` | внутри одного компонента |

**Нейминг:** `--vf-{категория}-{роль}-{вариант}-{состояние}` → `--vf-color-accent-hover`.
Название описывает **назначение**, а не значение: `--vf-color-fg-muted`, а не `--vf-grey-500`.

### Категории

| Категория | Токены | Значения |
|---|---|---|
| Цвет · поверхности | `--vf-color-bg`, `-bg-subtle`, `-bg-muted`, `-bg-inverse` | white · ink 50 · ink 100 · ink 950 |
| Цвет · текст | `--vf-color-fg`, `-fg-secondary`, `-fg-muted`, `-fg-disabled`, `-fg-on-accent` | ink 950 · 600 · 500 · 400 · white |
| Цвет · границы | `--vf-color-border`, `-border-subtle`, `-border-strong` | ink 200 · 100 · 300 |
| Цвет · акцент | `--vf-color-accent`, `-hover`, `-active`, `-text`, `-subtle`, `-subtle-fg` | crimson 700 · 800 · 900 · 700 · 100 · 800 |
| Цвет · статусы | `--vf-color-{success,warning,error,info}` + `-bg`, `-border` | см. tokens.css |
| Графики | `--vf-chart-1…8`, `-income`, `-expense` | категориальная палитра |
| Типографика | `--vf-t1…t8`, `--vf-font-button`, `-control`, `-table` | shorthand `font:` |
| Отступы | `--vf-space-{0,0-5,1,2,3,4,5,6,8,10,12,16,24}` | 0…96 px, модуль 4 |
| Контролы | `--vf-control-{lg,md,sm}`, `--vf-touch-min` | 48 · 40 · 32 · 44 |
| Радиусы | `--vf-radius-{none,xs,sm,md,lg,xl,full}` | 0 · 4 · 8 · 12 · 16 · 24 · 9999 |
| Тени | `--vf-shadow-{sm,md,lg,focus}` | |
| Анимация | `--vf-duration-{instant,fast,base,slow,deliberate}`, `--vf-ease-{out,in,standard}` | 0 · 120 · 200 · 320 · 480 ms |
| Слои | `--vf-z-{sticky,dropdown,overlay,modal,toast,tooltip}` | 100…600 |
| Сетка | `--vf-container-max`, `--vf-container-pad`, `--vf-gutter`, `--vf-measure` | 1200 · 16/32 · 16/24/32 · 720 |

**Брейкпоинты** в CSS-переменных не работают внутри `@media`, поэтому они фиксированы: `md 768`, `lg 1024`, `xl 1440`. В JS — из `tokens.js` (`breakpoint.md`).

### Темы

```html
<body>                       <!-- светлая по умолчанию -->
<section data-theme="dark">  <!-- тёмная секция, hero, подвал, боковое меню кабинета -->
```

Внутри `[data-theme="dark"]` переопределяются только семантические и компонентные токены. Добавить новую тему = добавить один блок переменных, без правки компонентов.

`prefers-reduced-motion: reduce` обнуляет длительности — компонентам ничего делать не нужно.

---

## 4. Как верстать: правильно и неправильно

```tsx
// ✗ Нельзя
<div style={{ display: 'flex', gap: 12, color: '#5E6A85' }}>…</div>
<button className="btn-red" style={{ borderRadius: 10 }}>Оплатить</button>

// ✓ Правильно
<Cluster gap={3}><Text tone="muted">…</Text></Cluster>
<Button variant="primary">Оплатить</Button>
```

```css
/* ✗ Нельзя */
.card { padding: 20px; border-radius: 10px; color: #434F6B; box-shadow: 0 2px 8px #0002; }

/* ✓ Правильно */
.card { padding: var(--vf-space-6); border-radius: var(--vf-radius-lg); color: var(--vf-color-fg-secondary); box-shadow: var(--vf-shadow-sm); }
```

**Нужного значения нет в токенах?** Это сигнал к дизайн-ревью, а не к `/* stylelint-disable */`. Либо используется ближайший токен, либо токен добавляется в систему (раздел 9).

**Разрешённые исключения** (с комментарием-причиной):
- логотип — размеры выводятся формулой из стороны знака S;
- графики на canvas/SVG — цвет берётся из JS через `getToken('chart.1')`, не строкой;
- сторонние виджеты (платёжная форма банка, карта) — изолируются в обёртке.

---

## 5. Компоненты

Каждый компонент существует в двух реализациях с **одинаковым API**: React (`@vashfindir/ui`) и Twig (Symfony UX TwigComponent). Классы общие — `components.css`.

**Нейминг классов:** `.vf-{блок}__{элемент}--{модификатор}`. Состояния — атрибутами: `aria-pressed`, `aria-invalid`, `aria-current`, `aria-expanded`, `disabled`, `data-loading`.

| Компонент | Props | Классы | Правила |
|---|---|---|---|
| **Button** | `variant: primary \| secondary \| ghost` · `size: lg \| md \| sm` · `loading` · `block` · `iconStart/End` · `href` | `.vf-btn--primary/--secondary/--ghost`, `--md`, `--sm`, `--block` | одна primary на экран; `loading` блокирует повторный клик; денежные CTA — не pill |
| **Input / Field** | `label` · `hint` · `error` · `optional` · `type` · `mask` | `.vf-field`, `.vf-input`, `__label/__hint/__error` | текст 16 px (iOS не зумит); ошибка = рамка + иконка + текст; проверка при blur |
| **Checkbox** | `checked` · `label` | `.vf-check` | согласие ПДн — без предзаполнения |
| **Chip** | `pressed` · `onToggle` | `.vf-chip[aria-pressed]` | выбор из 2–6 вариантов в формах и фильтрах |
| **Segmented** | `options` · `value` | `.vf-segmented`, `__item[aria-pressed]` | 2–4 взаимоисключающих варианта |
| **Badge** | `tone: neutral \| accent \| success \| warning \| error \| info` | `.vf-badge--{tone}` | статус всегда с текстом, не только цветом |
| **Alert** | `tone` · `title` · `action` | `.vf-alert--{tone}` | `role="alert"` только для ошибок |
| **Card** | `variant: default \| subtle \| raised` · `interactive` | `.vf-card--subtle/--raised/--interactive` | hover — только тень, без сдвига |
| **Table** | `columns` · `rows` · `align` | `.vf-table-wrap`, `.vf-table`, `[data-align="end"]` | суммы — вправо, tabular-nums; строка 48 |
| **Breadcrumbs** | `items` | `.vf-breadcrumbs` | nav + ol; последний — `aria-current="page"`; моб. — только родитель |
| **Logo** | `size: 24 \| 28 \| 32 \| 40 \| 64` · `signOnly` | `.vf-logo--{S}` | версия берётся из темы; не рисовать вручную |
| **Stack / Cluster / Grid / Container** | `gap` · `as` · `min` | `.vf-stack`, `.vf-cluster`, `.vf-grid`, `.vf-gap-*` | единственный способ задать расстояние между соседями |
| **Skeleton / Spinner** | `shape` | `.vf-skeleton`, `.vf-spinner` | скелетоны — для контента, спиннер — только в кнопке/поле |

**Следующие к реализации** (спецификации в Design System.dc.html): Select, DatePicker, AmountInput, Tabs, Modal, BottomSheet, Toast, Tooltip, Dropdown/Menu, Pagination, EmptyState, Avatar, Header (сайт), Sidebar (кабинет), Footer, LeadForm, ArticleCard, Chart-обёртки.

### Требования к каждому компоненту (Definition of Done)
- Только токены; проходит Stylelint и ESLint без disable.
- Все 6 состояний: default, hover, active, focus-visible, disabled, loading/error — где применимо.
- Клавиатура и screen reader: роль, `aria-*`, видимый фокус `--vf-shadow-focus`.
- Работает в `[data-theme="dark"]` без изменений кода.
- Story в Storybook на каждое состояние + скриншот-тест.
- React- и Twig-версии с одинаковыми props.

---

## 6. React: пакет @vashfindir/ui

```
packages/ui/
├─ src/tokens/        ← сгенерированные tokens.css, tokens.js, tokens.d.ts
├─ src/components/    ← Button/, Field/, … (tsx + module.css + stories + test)
├─ src/index.ts       ← публичный API: export { Button, Stack, … }
└─ package.json       ← "sideEffects": ["*.css"]
```

- Стили — **CSS Modules** (или vanilla-extract) с `var(--vf-*)`. CSS-in-JS со значениями в рантайме не используется.
- Проп `style` исключён из типов (`Omit<…, 'style' | 'className'>`). `className` принимают только примитивы раскладки.
- В приложении: `import '@vashfindir/ui/tokens.css'` один раз в корне, затем `import { Button } from '@vashfindir/ui'`.
- Если используется Tailwind — `theme` генерируется из `tokens.json`, произвольные значения `[#…]`, `[13px]` запрещены плагином `eslint-plugin-tailwindcss` (`no-arbitrary-value`).

## 7. Symfony / Twig

- `tokens.css`, `base.css`, `components.css` подключаются через AssetMapper или Webpack Encore — те же файлы, что в React-пакете (одна версия пакета).
- Компоненты — `symfony/ux-twig-component`: `<twig:Button variant="primary">`. PHP-класс компонента повторяет props React.
- В шаблонах запрещён атрибут `style` — проверка `twigcs` + кастомное правило или grep-проверка в CI: `grep -rn 'style="' templates/ && exit 1`.
- Письма (HTML email) — исключение: там инлайн-стили обязательны, но генерируются инлайнером (CssInliner) из тех же токенов на этапе сборки.

---

## 8. Контроль качества в CI

| Проверка | Инструмент | Блокирует merge |
|---|---|---|
| Сырые значения в CSS | Stylelint + `stylelint-declaration-strict-value` | да |
| `style={{}}`, hex в JSX | ESLint (`react/forbid-dom-props`, `no-restricted-syntax`) | да |
| `style="…"` в Twig | grep / twigcs | да |
| Типы props | TypeScript strict | да |
| Визуальная регрессия | Storybook + Chromatic или Playwright screenshots | да (требует approve) |
| Доступность | `@storybook/addon-a11y` / axe-core | да для critical |
| Контраст пар токенов | скрипт по tokens.json (WCAG 4.5 : 1 для текста) | да |
| Размер CSS-бандла | size-limit | предупреждение |

---

## 9. Как меняется система

1. **Изменение токена или компонента** — pull request в `packages/ui` с обновлённым `tokens.json`, story и скриншотами «до/после».
2. **Ревью** — дизайнер (владелец системы) + фронтенд-лид. `CODEOWNERS` на `tokens/` и `components/`.
3. **Версии** — semver через Changesets:
   - patch — исправление без изменения API и визуала;
   - minor — новый компонент, новый токен, новый вариант;
   - major — удаление/переименование токена или prop.
4. **Удаление** — сначала `@deprecated` + предупреждение в консоли и линтере, удаление не раньше следующей major.
5. **Figma** — переменные синхронизируются из `tokens.figma.json` (Tokens Studio или Figma Variables API). Имена в Figma и в коде совпадают 1 : 1.

---

## 10. План внедрения

| Шаг | Что | Результат |
|---|---|---|
| 1 | Завести `packages/ui`, положить `tokens.json`, настроить Style Dictionary | `tokens.css` / `tokens.js` из одного источника |
| 2 | Подключить Stylelint и ESLint в режиме warning, собрать отчёт по текущему коду | карта техдолга |
| 3 | Примитивы раскладки + Button, Field, Chip, Badge, Alert, Card, Table | покрыто ~70 % интерфейса кабинета |
| 4 | Storybook + скриншот-тесты + a11y | визуальные изменения видны в PR |
| 5 | Twig-версии тех же компонентов для сайта | сайт и кабинет на одной системе |
| 6 | Перевод экранов по одному: сначала кабинет (платежи, счета), затем сайт | линтеры переводятся в error по мере миграции |
| 7 | Линтеры в error для всего репозитория | новые нарушения невозможны |

Для массовой замены в старом коде — codemod (jscodeshift): `style={{ gap: 12 }}` → `<Stack gap={3}>`, hex → ближайший семантический токен по таблице соответствий.

---

## 11. Чек-лист pull request

- [ ] Нет `style=` в JSX/Twig и нет hex/rgb в CSS/TS.
- [ ] Используются семантические токены, а не примитивы.
- [ ] Расстояния между элементами — через `gap` / примитивы раскладки.
- [ ] Новый визуальный вариант — новый prop компонента, а не локальный класс.
- [ ] Проверено в `[data-theme="dark"]` и на 375 / 768 / 1440 px.
- [ ] Фокус с клавиатуры виден, у иконок без текста есть `aria-label`.
- [ ] Числа — tabular-nums, суммы по правилам форматирования (неразрывные пробелы, ₽ после числа, минус U+2212).
- [ ] Обновлены story и скриншоты.
