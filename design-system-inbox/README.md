# Дизайн-система «Ваш Финдир» v2.2 → Tailwind CSS v4

Пакет для внедрения на vashfindir.ru. Всё сгенерировано из `Design System.dc.html` v2.2 (раздел 29 «Единая таблица токенов»).

| Файл | Что это |
|---|---|
| `vf-theme.css` | Тема Tailwind v4: токены, тёмная тема, роли типографики, базовые стили |
| `tokens.json` | Те же токены в формате W3C Design Tokens (для других платформ, писем, PDF) |
| `eslint.config.js` | Линтер для JS/JSX: ловит классы вне системы |
| `check-templates.mjs` | То же для Twig/HTML-шаблонов (ESLint их не читает) |
| `tools/` | Перегенерация токенов из нового файла дизайн-системы |

**Правило одно: `vf-theme.css` и `tokens.json` руками не редактируются.** Меняется дизайн-система → дизайнер присылает новый `Design System.dc.html` → перегенерируем.

---

## 1. Подключение

```bash
npm i -D tailwindcss@4 @tailwindcss/cli   # или @tailwindcss/vite / @tailwindcss/postcss
npm i @fontsource-variable/inter @fontsource-variable/manrope
```

```css
/* assets/styles/app.css */
@import "@fontsource-variable/inter";
@import "@fontsource-variable/manrope";
@import "tailwindcss";
@import "vf-theme.css";
```

Шрифты ставятся из npm и отдаются с нашего домена: кириллица включена, запросов к Google Fonts нет.

## 2. Что делает тема

Тема **заменяет** стандартную тему Tailwind, а не расширяет её (`--*: initial`). Поэтому классов вне системы просто не существует — проверено сборкой Tailwind v4.3:

| Собирается (система) | Не собирается (вне системы) |
|---|---|
| `p-4` `gap-5` `mt-10` `py-24` `px-0.5` | `p-7` `gap-9` `m-11` |
| `bg-surface` `text-fg` `bg-accent-fill` `bg-ink-50` | `bg-red-500` `text-gray-700` |
| `rounded-md` `rounded-full` | `rounded-2xl` |
| `shadow-md` `shadow-focus-field` | `shadow-xl` |
| `type-t1` `text-t5` `font-semibold` | `text-sm` `text-2xl` `font-black` `leading-tight` |
| `md:` `lg:` `xl:` | `sm:` `2xl:` |
| `max-w-text` `max-w-page` | `max-w-md` |

Tailwind v4 всё равно собирает произвольные значения (`p-[13px]`, `bg-[#123456]`), `duration-300`, `border-3`, `ring-2`. Их ловят линтер и `check-templates.mjs` (раздел 5).

## 3. Шпаргалка: система → классы

**Цвет.** В разметке — только семантика. Примитивы (`ink-*`, `crimson-*`) — только внутри базовых компонентов.

| Токен | Класс |
|---|---|
| fg / fg-secondary / fg-muted / fg-disabled | `text-fg` `text-fg-secondary` `text-fg-muted` `text-fg-disabled` |
| accent (ссылки, ключевые числа) | `text-accent` |
| accent-fill (+ hover / active) | `bg-accent-fill hover:bg-accent-fill-hover active:bg-accent-fill-active` |
| on-accent | `text-on-accent` |
| surface / surface-muted / surface-raised | `bg-surface` `bg-surface-muted` `bg-surface-raised` |
| border-subtle / border / border-strong | `border-border-subtle` `border-border` `border-border-strong` (рамка по умолчанию уже `border`) |
| статусы | `text-success bg-success-bg border-success-border` (так же warning, error, info) |
| overlay | `bg-overlay` |
| графики | `bg-chart-1` … `bg-chart-8`, `bg-chart-income`, `bg-chart-expense` |

**Тёмная тема и блоки на ink.** Класс `dark` переключает все семантические цвета. Его можно поставить не только на `<html>`, но и на отдельный блок: подвал, ink-hero, тост, боковое меню кабинета.

```html
<footer class="dark bg-surface text-fg-secondary">…</footer>   <!-- ink 950 и белый текст автоматически -->
```

Hover primary на ink «светлее, а не темнее» получается сам: у `accent-fill-hover` в `.dark` своё значение.

**Типографика.** Используйте `type-*` — один класс задаёт шрифт, размер, интерлиньяж, вес и трекинг (и tnum у числовых ролей).

| Роль | Класс | Дополнительные веса |
|---|---|---|
| T1 Display 48/56 | `type-t2 md:type-t1` | — |
| T2 H2 36/44 | `type-t2-article md:type-t2` | — |
| T2-article 30/38 | `type-t2-article` | — |
| T3 20/28 · 700 | `type-t3` | — |
| T3-num 20/28 · 800 | `type-t3-num` | — |
| H4 16/26 · 700 · H4-num 800 | `type-h4` · `type-h4-num` | — |
| T4 Lead 18/28 | `type-t4` | — |
| T5 Body 16/26 | `type-t5` | `font-semibold` |
| T6 Small 14/22 | `type-t6` | `font-medium` `font-semibold` |
| T7 Caption 12/18 | `type-t7` | `font-medium` `font-semibold` |
| T8 Число 30/38 | `type-t8` | — |
| T-code 24/32 | `type-t-code` | — |
| T-mono 14/22 · 12/18 | `type-t-mono` · `type-t-mono-sm` | `font-medium` |

Мобильные размеры T1 и T2 в системе совпадают с T2 и T2-article, поэтому адаптив — просто сменой роли на `md:`.

**Прочее.**

| Что | Классы |
|---|---|
| Высоты контролов 24 / 32 / 40 / 44 / 48 / 56 | `h-control-xs` `-sm` `-md` `-touch` `-lg` `-xl` |
| Иконки 16 / 20 / 24 / 32 | `size-icon-sm` `-md` `-lg` `-xl` |
| Радиусы 0 / 4 / 8 / 12 / 16 / 24 / full | `rounded-none` `-xs` `-sm` `-md` `-lg` `-xl` `-full` |
| Обводка 1 / 2 | `border` · `border-2` |
| Фокус кнопки / на ink / поля / ошибка поля | `focus-visible:shadow-focus` · `shadow-focus-inverse` · `focus:border-accent focus:shadow-focus-field` · `border-error shadow-error-field` |
| Тени | `shadow-sm` `shadow-md` `shadow-lg` |
| Анимация | `transition-colors duration-fast ease-out` · `duration-base` `-slow` `-deliberate` `-instant` · `ease-in` `ease-standard` · `animate-spin` `animate-skeleton` |
| Контейнеры | `max-w-text` (720) · `max-w-page` (1200) |
| Пропорции медиа | `aspect-video` (16:9) `aspect-photo` (3:2) `aspect-portrait` (4:5) `aspect-screenshot` (16:10) `aspect-square` |

Фокус тоже переключается в `.dark`: `shadow-focus` и `shadow-focus-field` на ink сами берут crimson 400 и crimson 900.

## 4. Порядок работ

1. Подключить тему и шрифты. Собрать проект — всё, что «пропало» из вёрстки, это классы вне системы: заменить на токены.
2. Собрать базовые компоненты **до страниц**: `Logo` (по формулам раздела 00, единственное место с «нестандартными» размерами), `Button`, `Input`, `Badge`, `Chip`, функция `money()` из раздела 11. Каждый — во всех состояниях раздела 10.
3. Шапка и подвал: они на всех страницах и сразу проверяют токены.
4. Hero главной, затем остальные страницы — только из компонентов.
5. Страницы со слотами `⟨…⟩` и людьми без подписанного согласия не публикуются (раздел 30, «Открыто»).

## 5. Проверки в CI

```bash
npx eslint .                                                   # JS/JSX
npx @tailwindcss/cli -i assets/styles/app.css -o build/app.css
node check-templates.mjs build/app.css templates                # Twig/HTML
```

`check-templates.mjs` падает (exit 1), если в `class="…"` есть класс, которого нет в собранном CSS, или запрещённое значение. Twig-вставки `{{ … }}` и `{% … %}` пропускаются. Классы-хуки для JS называйте с префиксом `js-` — они игнорируются.

Дополнительно рекомендую скриншотные тесты (Playwright или Storybook + Chromatic) и `@axe-core/playwright` для контраста.

## 6. Обновление токенов

```bash
node tools/extract.cjs "Design System.dc.html"   # новый файл от дизайнера
node tools/generate.mjs                          # перезаписывает vf-theme.css и tokens.json
```

Если значения нет в таблице токенов — это вопрос к дизайнеру, а не повод дописать его в тему.
