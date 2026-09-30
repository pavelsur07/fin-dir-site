# Аудит страницы статьи «Газета» против раздела 26 дизайн-системы

Дата: 2026-09-30. Правки кода не вносились.

**Дизайн:** `design-system-inbox/Design System.dc.html`, секция `ds-26`, включая таблицу «Сетка страницы» и правила «Лучшие практики».

**Код:**
- `site/templates/website/pages/publication/blog_post.html.twig` (ниже «шаблон»);
- `site/src/Publication/Adapter/MarkdownRenderer.php` («рендерер»), классы тела статьи;
- `site/templates/website/layouts/base.html.twig` («base»), мета и og;
- `site/src/Publication/Query/PublicPost/PostStructuredData.php`, JSON-LD.

Наличие классов в теме проверено сборкой Tailwind на пробном файле, файлы проекта не менялись.

## Таблица «дизайн / код / расхождение»

| # | Блок | Дизайн (раздел 26) | Код: файл и фактические классы | Расхождение |
|---|---|---|---|---|
| 1 | Сетка страницы | Две колонки: статья ≤720 и панель 240–300, gap 40–64, max 1120. Панель `sticky top-24`. Ниже 1024 панель уходит под статью, оглавление становится аккордеоном сверху. | Шаблон: `<article class="max-w-text space-y-8">`. Контейнер даёт `page.html.twig`: `max-w-page px-4 md:px-8`. Боковой колонки нет. | **Нет боковой панели, sticky и адаптива панели.** Всё в одной колонке 720. Расстояние hero→текст 40 не задано (`space-y-8` = 32). |
| 2а | Шапка: крошки | `t6` medium, `fg-secondary`, шевроны 16px (иконки), последний пункт `fg`, `nowrap`, обрезка по 240px. Рубрика — отдельный уровень («Блог / Налоги / статья»). | Шаблон: `ol flex flex-wrap items-center gap-x-2 gap-y-1 type-t6 text-fg-secondary`, разделитель — текст «/» с `aria-hidden`, последний `text-fg`. | Разделитель текстовый, а не иконка. Нет обрезки длинного заголовка: он переносится. В крошках 3 уровня, рубрики нет. |
| 2б | Шапка: рубрика | Бейдж `t7` medium, `px-2 py-0.5`, `rounded-xs`, `surface-muted`, `fg-secondary`. | Нет. В `PublicPostView` нет поля рубрики. | **Отсутствует, нужны данные.** |
| 2в | Шапка: H1 и лид | H1 48/56, `hyphens-auto`, `text-balance`, блок max 800. Лид `t4`, `fg-secondary`, `text-pretty`. | Шаблон: H1 `type-t2 md:type-t1 text-fg`, лид `type-t4 text-fg-secondary`, оба внутри колонки 720. | Размеры совпадают. Нет `hyphens-auto`, `text-balance`, `text-pretty` (в теме они есть). Ширина 720 вместо 800. |
| 2г | Шапка: автор, мета | Аватар 40, имя `t6` semibold, должность `t7` muted. Мета: дата, «Обновлено», «N мин чтения», `tabular-nums`. Разделитель `border-t border-border-subtle pt-4`. | Шаблон: `<p class="type-t6 text-fg-muted">дата · Редакция Ваш Финдир</p>`. | Нет аватара, должности, разделителя, «Обновлено» и времени чтения. `updatedAt` есть в модели, но не выводится. Автора нет в модели. |
| 2д | Шапка: обложка | `aspect-video`, `rounded-lg`, max-height 520, OG 1200×630. | Нет. В модели нет поля обложки, рендерер вырезает картинки. | **Отсутствует.** Нужен токен max-height (см. список ниже). |
| 3 | «Коротко» | Отдельный блок до первого H2: `surface-muted`, `rounded-lg`, `p-6`, заголовок `t3`, маркированный список. | Рендерер: цитата с меткой «Короткий вывод» получает `my-6 rounded-md border-l-2 border-accent bg-surface-muted p-6`. Заголовка `t3` и списка нет. | `rounded-md` вместо `rounded-lg`, добавлена акцентная полоса, которой в дизайне нет. Это абзац в теле, а не блок с тезисами. |
| 4 | Оглавление | В sticky-панели. Карточка `border rounded-lg p-5`. «Содержание» `t6` semibold, счётчик «2/4», прогресс-полоса 4px, пункты `t6` с точкой 6px, `min-h` 32, активный — `fg` жирнее, `aria-current`. Только H2, до 7. | Шаблон: `nav rounded-md bg-surface-muted p-6`, заголовок `type-t3`, `ol list-decimal … type-t5`, ссылки `text-accent`. Заголовки H2 без `scroll-mt-*`. | **Нет активного пункта, прогресса, sticky, скролл-шпиона.** Другая карточка (заливка вместо рамки), другой кегль (`t5` вместо `t6`). Нет ограничения на 7 пунктов. Нет отступа под шапку при якорном переходе. |
| 5 | H2/H3/абзацы/списки | H2 30/38 всегда (`t2-article`), сверху 48. Абзацы `t5`, между ними 20, `text-pretty`. Списки: gap 8, `ul` отступ 20, `ol` 24. H3 в блоке нет. | Рендерер: h2 `mb-4 mt-10 type-t2-article md:type-t2 text-fg`; h3 `mb-3 mt-8 type-t3 text-fg`; p `mb-4 last:mb-0`; ul `mb-4 list-disc pl-6`; ol `list-decimal pl-6`; li `mb-2`. | H2 на `md:` растёт до 36/44 вместо фиксированных 30/38. Верх H2 40 вместо 48 (`mt-12`), абзацы 16 вместо 20 (`mb-5`). `ul` с отступом 24 вместо 20 (`pl-5`). |
| 6 | Таблица | Обёртка `rounded-md border`, `overflow-auto`. `min-width` 420. Шапка `surface-muted`, `th` `px-4 py-3`, вес 500, `fg-muted`. Первая колонка semibold, последняя вправо и `nowrap`. Тексты `fg`. Подпись `t7` muted под таблицей. | Рендерер: обёртка `my-6 overflow-x-auto rounded-md border focus-visible:shadow-focus`, `tabindex=0`, `role=region`. Таблица `w-full min-w-modal-md border-collapse text-left type-t6`. `th`: `border-b border-border-strong bg-surface-muted p-3 font-semibold text-fg`, `scope="col"`. `td`: `border-b border-border-subtle p-3 align-top text-fg-secondary`. | Min-width 560 вместо 420: токена 420 нет (TODO в коде). `th` `p-3` вместо `px-4 py-3`, вес и цвет другие (semibold/`fg` вместо medium/`fg-muted`). Текст ячеек `fg-secondary` вместо `fg`. Нет выравнивания чисел и `tabular-nums`. Нет подписи под таблицей. Доступность лучше дизайна: `scope`, фокус и `region`. |
| 7 | Врезки | Три вида: **«Важно»** (info: `info-bg`, `info-border`, `rounded-md p-4`, иконка 20, заголовок `t5` semibold `text-info`); **цитата** (`figure` с фото 120px, текст `t3`, автор `t6`, `rounded-lg p-6 border`); **«Коротко»** (см. п. 3). | Рендерер, по метке в цитате: *summary* `border-l-2 border-accent bg-surface-muted rounded-md p-6`; *practice* `border border-success-border bg-success-bg rounded-md p-6`; *key* `border-l-2 border-border-strong bg-surface-muted rounded-md p-6`. Любая цитата становится врезкой. | Нет info-врезки «Важно» (токены `info-*` в теме есть). Нет цитаты с автором и фото. Все три вида кода придуманы нами: *practice* на `success-*` и *key* нейтральная в дизайне не описаны. Нет иконок и стилизованных заголовков. Обычную цитату оформить нельзя. Медиа-блоки `figure/figcaption` и пары 4:5 не выводятся: рендерер вырезает картинки. |
| 8 | CTA и «Поделиться» | CTA: тёмная карточка (`dark bg-surface rounded-lg p-6`), текст `t3`, кнопка `h-control-lg rounded-md` primary. Один после основного раздела и один в конце. Панель: форма с вопросом (`H4`, поле `h-control-lg`, кнопка, согласие). «Поделиться»: подпись `t6`, кнопки 40×40 `rounded-sm border` с иконкой 20, `border-t pt-6`. | В шаблоне статьи нет. Есть готовые `components/_button.html.twig`, `_icon.html.twig`, `_form_input.html.twig` и секция `_lead_form`. | **Отсутствуют оба блока и форма в панели.** «Поделиться» потребует иконок и, для «копировать ссылку», JS. Решение по месту CTA (в теле или в шаблоне) не принято: в Markdown вставить нельзя. |
| 9 | JSON-LD и og | Article с автором, датами и обложкой; BreadcrumbList; FAQPage (по задаче); canonical; og:image 1200×630. | `PostStructuredData`: `BlogPosting` (headline, url, `datePublished`, `dateModified`, `inLanguage`, `author` = Organization, `publisher`, `description`) и `BreadcrumbList` из 3 пунктов. Base: `og:image` = статичный `/assets/og-image-v2.png`, блок `og_image` в статье не переопределён; `og:type=article`, canonical ✓. | Нет `image` в схеме, нет Person-автора. **FAQPage нет** (отложено, `docs/plan/Stage_Article_FAQ_JsonLd.md`). Крошки не включают рубрику. У всех статей один общий og:image. Даты и canonical — ✓. `BlogPosting` — подтип `Article`, для Google допустимо, но в дизайне указан `Article`. |

## Классы и значения, которых нет в `vf-theme.css`

Проверено сборкой на пробном файле; всё остальное из дизайна собирается (`sticky`, `top-6`, `lg:w-sidebar`, `aspect-*`, `scroll-mt-6`, `hyphens-auto`, `text-pretty`, `text-balance`, `tabular-nums`, `rounded-lg`, `info-*`, `mt-12`, `mb-5`, `pl-5`, `size-control-md`, `size-icon-md` и т. д.).

| Что нужно дизайну | Есть ли токен | Ближайшее |
|---|---|---|
| `max-h-*` для обложки (520px) | **нет** (`max-h-96` и `max-h-page` не собираются) | нет; вопрос дизайнеру |
| `max-w-60` (обрезка последнего пункта крошек, 240px) | **нет** | `max-w-sidebar` (256) |
| Min-width таблицы 420px | **нет** | `min-w-modal-sm` (400), сейчас `min-w-modal-md` (560) |
| Ширина боковой панели 240–300 | диапазона нет, есть 256 | `w-sidebar` |
| Ширина прогресс-полосы «из JS» и `transition` по width | не проверялось: динамический `width` невозможен без inline-стиля или JS, оба запрещены `SITE_RULES.md` | дискретные шаги классами (`w-1/4` и т. п., нужно проверить в теме) либо решение дизайнера |

## Итог

**Совпадает:** типографика H1, лида, тела и списков по кеглю, `max-w-text`, радиус и рамка обёртки таблицы, цвета `fg`, `accent`, `surface-muted`, BreadcrumbList, canonical, `og:type=article`.

**Главные расхождения:**
1. Нет боковой панели вообще (оглавление с прогрессом, форма, sticky).
2. Нет автора, рубрики, обложки, даты обновления, времени чтения: в модели `Post` нет этих полей.
3. Нет CTA и «Поделиться».
4. Виды врезок в коде расходятся с дизайном (нет «Важно», нет цитаты, лишние *practice* и *key*).
5. H2 в реализации растёт до 36/44 на `md:` по ТЗ задачи, а раздел 26 фиксирует 30/38. Нужно решить, чем руководствоваться.

## Открытые вопросы

- Какой токен для max-height обложки и min-width таблицы? Как делать прогресс-полосу без inline-стилей и JS-стилей?
- Оставляем ли *practice* и *key* в коде или сводим всё к дизайнерской «Важно»?
- Нужны ли автор, рубрика и обложка (это изменение модели данных и миграция) или остаёмся на «Редакция Ваш Финдир»?
- Где живёт CTA: в шаблоне после тела статьи или маркером в Markdown?
- H2 в статье: фиксированный `type-t2-article` (раздел 26) или с `md:type-t2` (ТЗ задачи)?
