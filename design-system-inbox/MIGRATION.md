# Миграция классов сайта на тему «Ваш Финдир» v2.2

Тема заменяет стандартную тему Tailwind, поэтому эти классы перестают собираться. Таблица покрывает все 61 класс, которые сейчас есть в `site/templates`, `site/assets/scripts/website` и `site/src/Publication/Adapter` (проверено сборкой).

Правило: если нужного класса нет в таблице и в `README.md` — **остановиться и спросить**, не подбирать «похожий».

## Цвет

| Было | Стало | Примечание |
|---|---|---|
| `text-slate-950` | `text-fg` | |
| `text-slate-600` | `text-fg-secondary` | |
| `bg-slate-50` `hover:bg-slate-50` `bg-slate-100` | `bg-surface-muted` `hover:bg-surface-muted` | |
| `border-slate-200` | `border-border-subtle` — разделители (`border-b`, `border-t`, `hr`); просто `border` — рамка контейнера | цвет рамки по умолчанию уже `border` |
| `border-slate-300` `border-slate-400` `border-slate-500` | `border-border-strong` | поля, контурные кнопки |
| `bg-red-700` | `bg-accent-fill` | |
| `hover:bg-red-800` | `hover:bg-accent-fill-hover` | |
| `active:bg-red-900` | `active:bg-accent-fill-active` | |
| `border-red-700` · `hover:border-red-800` · `active:border-red-900` | `border-accent` · `hover:border-accent-fill-hover` · `active:border-accent-fill-active` | |
| `text-red-700` `focus:text-red-700` | `text-accent` `focus:text-accent` | |
| `hover:text-red-800` `hover:text-red-700` | `hover:text-accent-fill-hover` | |
| `bg-green-50` · `border-green-300` · `text-green-800` | `bg-success-bg` · `border-success-border` · `text-success` | |
| `bg-orange-50` · `border-orange-300` · `text-orange-800` | `bg-error-bg` · `border-error-border` · `text-error` | ошибка в системе — оранжевая |
| `bg-green-700 text-white` (бейдж «опубликовано» в админке) | `bg-success-bg text-success` | бейджи в системе мягкие |
| `bg-slate-600 text-white` (бейдж «черновик») | `bg-surface-muted text-fg-secondary` | |
| `backdrop:bg-slate-950/60` | `backdrop:bg-overlay` | |

## Тёмные области (подвал, баннер cookie, меню админки, код в статье)

Ставим класс `dark` на контейнер тёмной области — внутри все семантические цвета переключаются сами.

| Было | Стало (внутри блока с `dark`) |
|---|---|
| `bg-slate-950` | `dark bg-surface` на контейнере |
| `bg-slate-900` (блок кода в статье) | `dark bg-surface-muted text-fg` |
| `bg-slate-700` · `hover:bg-slate-800` | `bg-surface-raised` · `hover:bg-surface-raised` |
| `hover:bg-slate-950` (кнопка закрытия баннера cookie) | `hover:bg-surface-raised` — баннер использует `dark bg-surface`, hover на ink становится светлее |
| `bg-white text-slate-950` внутри `dark` | счётчики → `bg-accent-fill text-on-accent`; кнопки → `bg-surface-raised text-fg` |
| `text-slate-300` · `hover:text-slate-200` | `text-fg-secondary` · `hover:text-fg` |
| `border-slate-600` `border-slate-700` | `border-border-strong` · `border` |

## Кнопки (только в `_button.html.twig`)

Раздел 10 «Состояния» в `Design System.dc.html`. Примитивы `ink-*` разрешены только здесь.

| Вариант | Классы |
|---|---|
| primary | `bg-accent-fill text-on-accent hover:bg-accent-fill-hover active:bg-accent-fill-active` |
| secondary | `bg-ink-950 text-white hover:bg-ink-800 active:bg-ink-700` (было `…hover:bg-slate-950`) |
| outline | `border border-border-strong bg-surface text-fg hover:border-ink-500 hover:bg-surface-muted active:bg-ink-100` |
| общее | `h-control-lg rounded-md px-6 type-t5 font-semibold transition-colors duration-fast focus-visible:shadow-focus` |

## Фокус

| Было | Стало |
|---|---|
| `focus-visible:outline-2 focus-visible:outline-red-700` (+ `outline-offset-*`) у кнопок и ссылок | `focus-visible:shadow-focus` |
| `focus-visible:outline-white` в любой тёмной области с классом `dark` (+ `focus-visible:outline-2` и `outline-offset-*`) | `focus-visible:shadow-focus` — с классом `dark` используется focus-inverse |
| то же у полей ввода | `focus:border-accent focus:shadow-focus-field` |
| `aria-invalid:border-orange-700 aria-invalid:ring-2 aria-invalid:ring-orange-700` | `aria-invalid:border-error aria-invalid:shadow-error-field` |

## Типографика

Роль задаёт размер, интерлиньяж, вес и трекинг. `leading-*` и `font-bold` у заголовков удалить.

| Было | Стало |
|---|---|
| `text-3xl` — H1 страницы | `type-t2 md:type-t1` |
| `text-3xl` — заголовки в админке, H2 в Markdown | `type-t2-article` |
| `text-2xl` | `type-t2-article` |
| `text-xl` | `type-t3` |
| `text-base` | `type-t5` |
| `text-sm` | `type-t6` |
| `text-xs` | `type-t7` |
| `text-sm leading-6 text-orange-800` (ошибка поля в `navigation.js`) | `type-t7 font-medium text-error` |
| `leading-*` (`leading-5`, `-6`, `-7`, `-tight`, `-none`) | удалить |

## Размеры и раскладка

| Было | Стало | Примечание |
|---|---|---|
| `min-h-11` · `size-11` | `min-h-control-touch` · `size-control-touch` | зона касания 44 |
| `max-w-6xl` `max-w-5xl` | `max-w-page` | 1200 |
| `max-w-3xl` | `max-w-text` | 720 |
| `w-60` (меню админки) | `w-sidebar` | 256 |
| `min-w-52` (колонка таблицы админки) | `min-w-sidebar` | |
| `min-h-32` (textarea админки) | `min-h-24` | |
| `w-80` `max-w-80` (мобильное меню) | `w-full` | по системе мобильное меню — на весь экран (16.1) |
| `md:pr-28` (баннер cookie) | `md:pr-24` | |
| `rounded` | `rounded-xs` — код в тексте; `rounded-md` — поля и кнопки | |
| `border-l-4` (цитата в статье) | `border-l-2` | толщина только 1 или 2 |
| `duration-200` `duration-250` | `duration-base` | |
