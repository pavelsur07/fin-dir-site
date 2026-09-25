# Stage 9 — Public Website design system v2: отчёт

## Результат

Публичные Twig-страницы переведены на foundations и production components v2. Сохранены опубликованные тексты и факты, маршруты, метаданные и JSON-LD, блог и пагинация, `POST /lead` с CSRF и обработкой ошибок, cookie notice и управление мобильным меню. Hero главной — светлый и текстовый. Демо-цены, возможности кабинета и фотография из макета не опубликованы. Admin, `conwix/`, Traefik и схема БД не менялись.

Единственный исходный website stylesheet — `site/assets/styles/website/app.css`; Tailwind 4.3.3 собирает `site/public/assets/website/app.css` через `make assets`. В CSS включены семантические токены светлой и тёмной тем, типографика, состояния и стиль Markdown-статей. Onest и действующие v1 aliases удалены. Inter и Manrope размещены локально в WOFF2 subsets с лицензиями OFL и `font-display: swap`. Добавлены logo, favicon v2 и новое непрозрачное OG-изображение 1200×630 по новому URL. `SITE_RULES.md` переписан под v2.

`/ui-kit` и `/ui-kit/sections` показывают production components и секции v2. Точное воспроизведение полной верстки `Design System.dc.html` в `ui_kit.html.twig` пользователь выделил в следующую отдельную задачу.

## Проверки

| Уровень | Результат |
|---|---|
| Unit | Контрастность семантических пар в обеих темах и guard против v1 aliases, inline styles/handlers и второго website stylesheet проходят. |
| Integration | N/A: persistence, миграции и внешние адаптеры не менялись. |
| Functional | `make ci` прошёл: 221 тест, 2311 assertions; маршруты, SEO/JSON-LD, блог, форма, cookie и UI-kit входят в проверку. |
| E2E | Playwright: 11 маршрутов на 320/375/768/1024/1440px, 55 сочетаний без горизонтального скролла; menu → форма → результат, ошибка и успех формы, фокус, Escape и reduced motion проверены. |

`make assets`, `make asset-version` (`609590ab3e53`), `make assets-check`, `make ci` и `git diff --check` прошли. Собранный CSS и версия в layout совпадают. Проверка исходников не нашла Onest и старых visual aliases в runtime website code.

## Решения и review

Сверка handoff показала, что `tokens.css` содержит тёмную тему и component tokens, которых нет в JSON. Они перенесены в canonical CSS token block. `border-strong` в обеих темах задан Ink 500: исходные значения handoff не обеспечивали 3:1 для границы control. Тёмный `accent-active` установлен Crimson 700 ради контраста белого текста ≥4.5:1. Отклонения описаны в `SITE_RULES.md` и проверены тестом.

Self-review полного diff проверил границы Public Website, источники стилей, сохранение форм/SEO/маршрутов, активные классы, контраст, адаптивность и отсутствие изменений Admin. Два независимых Claude Code review не нашли blocker/high. Первое выявило 6 medium и 6 low; повторное проверило исправления и указало на неполный guard старых классов, контраст нажатой кнопки в тёмной теме и мелкие несоответствия шаблонов. Исправлены JS-классы ошибок формы, размер и непрозрачность OG, вес шрифтов, фокус CTA, восстановлены проверки исходного теста, дополнены правила сайта, исправлены тёмная граница, `tracking-normal`, cascade заголовков, focus главного блока и шум в diff. После второго ревью дополнены guard и матрица состояний формы, исправлены активный цвет кнопки, индикация ошибки checkbox, ссылка обновления 500, выравнивание и H1 страницы результата заявки. Пара `focus`/`error` остаётся близкой по оттенку из палитры v2; состояния различаются кольцом, рамкой, текстом ошибки и `aria-invalid`.

## Ограничения и выпуск

В dev-режиме Symfony выводит debug page для 404/500, поэтому production Twig error pages проверены линтером и функциональными проверками, но не визуально в браузере. Сквозной тест формы отправил одну тестовую заявку в локальную dev-БД; внешняя Telegram-отправка не была настроена. Исходный `design-system-inbox/**` и посторонний `docs/man-holding-head-1080x1080.png` уже находились в index/рабочей папке до начала этапа; эта задача не меняла их состояние и не готовила commit. Публикация, commit, push и deploy не выполнялись. Для выпуска нужно публиковать все Twig-страницы вместе с новым compiled CSS и assets; runtime не зависит от `design-system-inbox`.
