# Ваш Финдир — Design System v1.0
> Единый источник правды для разработки и работы с Claude.  
> Файл: `wp-content/themes/vashfindir/docs/DESIGN-SYSTEM.md`  
> Обновлять после каждого значимого изменения.

---

## 0. Как пользоваться с Claude

**Каждый промпт начинать так:**

```
Ты разрабатываешь WordPress-тему vashfindir.ru.
Прочитай файл docs/DESIGN-SYSTEM.md и строго следуй его правилам.
Задача: [описание задачи]
```

**Золотые правила при работе с Claude:**
1. Никогда не создавай новых CSS-переменных — используй существующие из раздела «Токены»
2. Никогда не создавай новых классов кнопок — используй таблицу «Кнопки»
3. Новый template part = отдельный файл в `template-parts/landing/` по boilerplate
4. CSS новой секции добавляй в `landing.css` с комментарием `/* === VF-НАЗВАНИЕ === */`
5. Все заголовки секций через `.section-title` / `.section-subtitle` — никаких inline стилей

---

## 1. Токены (единственный источник)

### Цвета — ИСПОЛЬЗУЙ ТОЛЬКО ЭТИ

| Переменная | Значение | Применение |
|---|---|---|
| `--vf-brand` | `#0A1548` | Основной цвет бренда, заголовки, тёмные блоки |
| `--vf-brand-2` | `#0d1c63` | Hover для brand, вторичный акцент |
| `--brand-red` | `#B00020` | CTA-кнопка «Регистрация», акцентный красный |
| `--brand-red-hover` | `#8E001A` | Hover для красного CTA |
| `--vf-text` | `#0b1020` | Основной текст |
| `--vf-muted` | `#5c677d` | Вторичный текст, подписи |
| `--vf-bg` | `#f5f6f8` | Фон страницы |
| `--vf-card` | `#ffffff` | Фон карточек |
| `--vf-border` | `rgba(10,21,72,.14)` | Рамки по умолчанию |

**⚠️ Запрещено:** `--brand`, `--cta`, `--muted`, `--text` — это алиасы из `landing.css`, они дублируют выше. В новом коде только `--vf-*`.

### Типографика

| Переменная | Значение | Применение |
|---|---|---|
| `--vf-fs-h1` | `clamp(32px, 3.2vw, 46px)` | H1, page-title |
| `--vf-fs-h2` | `clamp(24px, 2.1vw, 34px)` | H2, section-title |
| `--vf-fs-h3` | `20px` | H3, карточки |
| `--vf-fs-body` | `16px` | Основной текст |
| `--vf-fs-small` | `13px` | Подписи, метки |
| `--vf-lh-body` | `1.55` | Межстрочный для текста |
| `--vf-font-sans` | `system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif` | Единственный шрифт |

### Отступы и геометрия

| Переменная | Значение | Применение |
|---|---|---|
| `--vf-radius` | `6px` | Радиус по умолчанию (кнопки, карточки, инпуты) |
| `--vf-shadow` | `0 10px 30px rgba(10,21,72,.08)` | Тень карточек |
| `--vf-border` | `rgba(10,21,72,.14)` | Граница элементов |
| `--vf-content-max` | `1320px` | Максимальная ширина контента |

**Padding секций:**
- `.vf-section-pad-lg` — основные секции (определено в `main.css`)
- `.vf-section-pad-md` — page header / intro

---

## 2. Кнопки — ПОЛНАЯ ТАБЛИЦА (семь → три)

> **Проблема:** в теме сейчас 7 разных классов кнопок. Ниже — каноническая таблица. В новом коде — только эти три.

| Класс | Вид | Когда использовать |
|---|---|---|
| `.vf-btn.vf-btn--primary` | Тёмно-синий fill | Главный CTA: «Регистрация», «Подключить», «Начать» |
| `.vf-btn.vf-btn--secondary` | Белый, синяя обводка | Вторичное действие: «Войти», «Подробнее» |
| `.vf-cta-btn-secondary` | Красный fill | Акцентный CTA: «Обсудить с CFO», «Записаться» |

---

**Исключение: блок тарифов**

В `template-parts/blocks/tariffs-2.php` используется
четвёртый класс `.vf-cta-btn-outline` — outline-кнопка
для нерекомендуемых тарифов (1-й и 3-й план).

Это осознанное решение: визуально отделяет
«не рекомендуем» от акцентного красного CTA.
Не заменять. Не распространять на другие блоки.

---

**HTML-паттерн:**
```html
<!-- Главный CTA -->
<a class="vf-btn vf-btn--primary" href="#">Попробовать бесплатно</a>

<!-- Вторичный -->
<a class="vf-btn vf-btn--secondary" href="#">Войти</a>

<!-- Красный акцент -->
<a class="vf-cta-btn-secondary" href="#">Записаться на диагностику</a>

<!-- Полная ширина (добавь модификатор) -->
<a class="vf-btn vf-btn--primary vf-btn--block" href="#">...</a>
```

**⚠️ Устаревшие — не использовать в новом коде:**
`.btn-brand`, `.btn-cta`, `.btn-cta-secondary`, `.btn-outline-brand`, `.btn-cta-fill`, `.vf-cta-btn-primary`, `.vf-cta-btn-outline`

---

## 3. Компоненты-лего

### 3.1 Поверхность `.vf-surface`

Базовый блок для карточек. Стили в `main.css`.

```html
<div class="vf-surface">
  Контент
</div>
```

Переопределение через CSS-переменные:
```css
.my-special-card {
  --vf-surface-border: rgba(10,21,72,.20);
  --vf-surface-radius: 12px;
  --vf-surface-bg: #f0f2f5;
}
```

### 3.2 Типографика секции

Всегда так — никаких inline `font-size` / `color`:
```html
<h2 class="section-title">Заголовок раздела</h2>
<p class="section-subtitle">Подзаголовок или лид раздела</p>
```

Для H1 страницы:
```html
<h1 class="page-title">Заголовок страницы</h1>
```

### 3.3 Тайл (метрика/факт)

```html
<div class="tile">
  <div class="k">Выручка</div>
  <div class="v">1 240 000 ₽</div>
  <div class="h">Остаток на счетах сегодня</div>
</div>
```

### 3.4 Иконка-пилюля

```html
<div class="vf-who__icon" aria-hidden="true">
  <i class="bi bi-graph-up"></i>
</div>
```

### 3.5 Форма / инпут

```html
<input class="vf-field" type="text" name="name" placeholder="Ваше имя" required>
```

### 3.6 Чекбокс-согласие

```html
<label class="vf-consent">
  <input type="checkbox" name="consent" checked>
  <span>Даю согласие на обработку персональных данных...</span>
</label>
```

### 3.7 Хлебные крошки

```php
<?php get_template_part('template-parts/blocks/breadcrumbs'); ?>
```

---

## 4. Секции-лего (template parts)

### Список всех готовых секций

| Секция | Файл | Аргументы |
|---|---|---|
| Hero (главная, 1:1) | `template-parts/landing/hero-front-page.php` | нет |
| Hero широкий (16:9) | `template-parts/landing/hero-wide.php` | `$args` массив |
| Уровень контроля | `template-parts/landing/control-level.php` | нет |
| Лид-форма | `template-parts/landing/lead-capture.php` | нет |
| FAQ | `template-parts/landing/faq.php` | нет |
| Тарифы v2 | `template-parts/blocks/tariffs-2.php` | нет |
| Хлебные крошки | `template-parts/blocks/breadcrumbs.php` | нет |

### Подключение в шаблоне страницы

```php
<?php get_template_part('template-parts/landing/hero-front-page'); ?>
<?php get_template_part('template-parts/landing/control-level'); ?>
<?php get_template_part('template-parts/landing/lead-capture'); ?>
<?php get_template_part('template-parts/blocks/tariffs-2'); ?>
<?php get_template_part('template-parts/landing/faq'); ?>
```

### Hero-wide с аргументами

```php
<?php
$hero_args = [
    'kicker'  => 'Сервис + консультации',
    'title'   => 'Заголовок страницы',
    'lead'    => get_the_excerpt() ?: 'Лид-текст',
    'primary'   => ['label' => 'Начать', 'url' => home_url('/register')],
    'secondary' => ['label' => 'Войти',  'url' => home_url('/login')],
    'media' => [
        'type'    => 'image',          // 'image' или 'placeholder'
        'ratio'   => 'wide',           // всегда 'wide' (16:9)
        'alt'     => 'Описание фото',
        'desktop' => get_template_directory_uri() . '/assets/img/hero/desktop-hero-XXX.jpg',
        'tablet'  => get_template_directory_uri() . '/assets/img/hero/tablet-hero-XXX.jpg',
        'mobile'  => get_template_directory_uri() . '/assets/img/hero/mobile-hero-XXX.jpg',
    ],
];
get_template_part('template-parts/landing/hero-wide', null, $hero_args);
?>
```

### Boilerplate новой секции

```php
<?php
/**
 * Секция: [Название]
 * Файл: template-parts/landing/[название].php
 */
if (!defined('ABSPATH')) exit;
?>

<section class="vf-[название] vf-section-pad-lg" id="[название]">
    <div class="container">
        <header class="vf-[название]__header">
            <h2 class="section-title vf-[название]__title">...</h2>
            <p class="section-subtitle vf-[название]__subtitle">...</p>
        </header>
        <div class="vf-[название]__body">
            ...
        </div>
    </div>
</section>
```

CSS в `landing.css`:
```css
/* === VF-[НАЗВАНИЕ] === */
.vf-[название] { }
.vf-[название]__header { }
.vf-[название]__body { }
```

---

## 5. Структура файлов

```
wp-content/themes/vashfindir/
├── assets/
│   ├── css/
│   │   ├── main.css          ← ТОКЕНЫ, типографика, кнопки, header, footer
│   │   ├── landing.css       ← ТОЛЬКО layout секций (hero, control, who, faq, lead)
│   │   ├── info.css          ← Страница "О нас"
│   │   └── blog.css          ← Блог (пустой, для будущего)
│   ├── js/
│   │   └── main.js           ← Мобильное меню, active-links
│   └── img/
│       └── hero/             ← desktop-hero-XXX.jpg, tablet-XXX.jpg, mobile-XXX.jpg
│
├── docs/
│   ├── DESIGN-SYSTEM.md      ← ЭТОТ ФАЙЛ (главный)
│   └── ui/rules.md           ← Layout rules (архитектурный базелайн)
│
├── inc/
│   ├── setup.php             ← theme supports, nav menus
│   ├── enqueue.php           ← подключение CSS/JS
│   ├── seo.php               ← SEO meta
│   └── metrika.php           ← Яндекс Метрика
│
├── template-parts/
│   ├── blocks/
│   │   ├── breadcrumbs.php
│   │   └── tariffs-2.php
│   ├── landing/
│   │   ├── _section-boilerplate.php  ← ШАБЛОН новой секции
│   │   ├── hero-front-page.php
│   │   ├── hero-wide.php
│   │   ├── control-level.php
│   │   ├── lead-capture.php
│   │   ├── faq.php
│   │   └── sections.php              ← оркестратор секций для service-страниц
│   ├── info/
│   │   └── content.php
│   └── layout/                       ← пустые, для будущего
│
├── header.php                        ← шапка + мобильное меню
├── footer.php                        ← подвал
├── front-page.php                    ← Главная страница
├── page-service.php                  ← Страница "Сервис"
├── page-service-findir.php           ← Страница "Сервис + Финдир"
├── page-about.php                    ← О нас
├── page-academy-finance.php          ← Академия
├── page-design-system.php           ← Sandbox (тестовый стенд)
└── index.php                         ← Fallback
```

---

## 6. Layout Rules (архитектурный контракт)

```
<body>
  <main>              ← НЕТ padding / margin
    .container         ← НЕТ vertical padding
      <section>        ← ЗДЕСЬ vf-section-pad-lg / vf-section-pad-md
        <header>       ← section header (H2 + subtitle)
        <div>          ← контент
```

**Запрещено:**
- `padding` на `<main>`, `.container`
- Вложенные `vf-section-pad-*` (на родителе И ребёнке одновременно)
- Произвольные `margin-top` / `padding-top` на layout-обёртках

---

## 7. Подключение стилей (enqueue.php)

| Страница | CSS файлы |
|---|---|
| Все страницы | `bootstrap.css` → `main.css` |
| Главная, service, service-findir, academy-finance | + `bootstrap-icons.css` + `landing.css` |
| О нас | + `info.css` |

Добавить новую страницу в условие:
```php
// inc/enqueue.php, строка ~26
if (is_front_page() || is_page(['service', 'service-findir', 'academy-finance', 'NEW-SLUG'])) {
```

---

## 8. Создание новой страницы — чеклист

1. **Создай файл шаблона** `page-[slug].php`:
```php
<?php
if (!defined('ABSPATH')) exit;
get_header();
$hero_args = [ /* ... */ ];
?>
<main class="main-content" role="main">
    <?php get_template_part('template-parts/landing/hero-wide', null, $hero_args); ?>
    <?php get_template_part('template-parts/landing/lead-capture'); ?>
    <?php get_template_part('template-parts/landing/faq'); ?>
</main>
<?php get_footer(); ?>
```

2. **Добавь slug в `enqueue.php`** если нужен `landing.css`

3. **Добавь hero-изображения** в `assets/img/hero/`:
    - `desktop-hero-[slug].jpg` (1200×675, 16:9)
    - `tablet-hero-[slug].jpg` (900×506)
    - `mobile-hero-[slug].jpg` (767×431)

4. **Создай страницу в WordPress** → выбери шаблон → назначь slug

5. **Если нужна новая секция** — создай `template-parts/landing/[секция].php` по boilerplate + CSS в `landing.css`

---

## 9. Промпты для Claude — копируй и используй

### Промпт 0 — Системный контекст (вставлять всегда первым)

```
Ты разрабатываешь WordPress-тему для сайта vashfindir.ru.

СТЕК: WordPress + Bootstrap 5 + кастомная тема vashfindir.
ФАЙЛОВАЯ СТРУКТУРА и ПРАВИЛА — в файле docs/DESIGN-SYSTEM.md.

Ключевые правила:
- CSS-переменные: только --vf-* из main.css
- Кнопки: только .vf-btn.vf-btn--primary / .vf-btn.vf-btn--secondary / .vf-cta-btn-secondary
- Заголовки секций: только .section-title и .section-subtitle
- Новая секция: файл template-parts/landing/[имя].php + CSS в landing.css с /* === VF-ИМЯ === */
- Layout: padding только на <section>, никогда на <main> или .container
- Нейминг: .vf-[секция]__[элемент] (BEM)
```

### Промпт 1 — Новая страница

```
[Системный промпт]

Создай новую страницу WordPress для: [ОПИСАНИЕ СТРАНИЦЫ]
Slug: [slug]
Цель: [что должен сделать посетитель]

Секции:
1. Hero-wide: kicker="...", title="...", lead="..."
2. [другие секции из готовых или новые]
3. lead-capture (всегда последней перед FAQ)
4. FAQ

Создай:
- page-[slug].php
- Добавление slug в enqueue.php
- Новые секции (если нужны): template-parts/landing/[имя].php + CSS в landing.css
```

### Промпт 2 — Новая секция

```
[Системный промпт]

Создай новый template part для секции: [ОПИСАНИЕ]
Имя файла: template-parts/landing/[имя].php
Секция будет использоваться на: [страницы]

Требования к секции:
- Root класс: .vf-[имя]
- Структура по boilerplate из docs/DESIGN-SYSTEM.md
- Использовать только --vf-* переменные
- Не создавать новых CSS-переменных

Создай файл секции + CSS блок для landing.css.
```

### Промпт 3 — Аудит стилей

```
[Системный промпт]

Проведи аудит landing.css на соответствие DESIGN-SYSTEM.md:

1. Найди все CSS-переменные НЕ из списка --vf-* (лишние алиасы)
2. Найди дублирующиеся стили между main.css и landing.css
3. Найди классы кнопок, которые не из канонической тройки
4. Найди inline стили font-size/color там, где должны быть .section-title / .section-subtitle
5. Проверь соблюдение layout rules (padding на <main> / .container)

Дай конкретный список проблем с предложением исправления.

[вставь содержимое landing.css]
```

### Промпт 4 — Рефакторинг front-page.php

```
[Системный промпт]

В front-page.php есть проблемы:
- Прямые Bootstrap классы (.card, .shadow-sm, .d-flex) вместо VF-компонентов
- Смешанные классы кнопок
- Некоторые секции не вынесены в template parts

Задача: рефакторинг front-page.php.
- Секцию "Кому это откликается" → вынести в template-parts/landing/who.php
- Секцию "Почему тревожно" → вынести в template-parts/landing/why.php
- Секцию "Интеграция" → вынести в template-parts/landing/integrations.php
- Секцию "С чем уйдёте" → вынести в template-parts/landing/outcome.php
- Заменить Bootstrap-классы на VF-компоненты
- front-page.php должен быть набором get_template_part() вызовов

Создай все новые файлы + обновлённый front-page.php.
```

### Промпт 5 — Конвертировать старый HTML-блок

```
[Системный промпт]

Конвертируй этот HTML-блок из _source/ в правильный WordPress template part:

Требования:
- Убрать Bootstrap-утилиты (.d-flex, .gap-3, .col-lg-4 и т.д.) → заменить на CSS в landing.css
- Использовать только --vf-* переменные
- Кнопки → каноническая тройка
- Заголовки → .section-title / .section-subtitle
- Именование → .vf-[секция]__[элемент]

[вставь HTML блок]
```

---

## 10. Известные технические долги

| # | Проблема | Приоритет | Файл |
|---|---|---|---|
| 1 | `--brand`, `--cta`, `--muted` дублируют `--vf-*` | Высокий | `landing.css` |
| 2 | `front-page.php` использует Bootstrap классы напрямую | Высокий | `front-page.php` |
| 3 | 7 классов кнопок вместо 3 | Высокий | `main.css`, `landing.css` |
| 4 | `sections.php` почти пустой — секции не подключены | Средний | `sections.php` |
| 5 | `blog.css` и `template-parts/blog/` пустые | Низкий | — |
| 6 | `template-parts/layout/` пустые | Низкий | — |
| 7 | `_source/` папка не нужна в продакшене | Низкий | `_source/` |
| 8 | `lead-capture.php` использует `.btn.btn-cta` (старый класс) | Средний | `lead-capture.php` |

---

## 11. Фото — технические требования

| Назначение | Соотношение | Размер desktop | Формат |
|---|---|---|---|
| Hero главная | 1:1 | 900×900px | .webp / .jpg |
| Hero сервисные страницы | 16:9 | 1200×675px | .webp / .jpg |
| Команда / основатель | 4:5 | 800×1000px | .webp / .jpg |
| Кейсы / обложки | 16:9 | 1200×675px | .webp / .jpg |

**Путь:** `assets/img/hero/[device]-hero-[slug].[ext]`  
**Устройства:** `desktop`, `tablet`, `mobile`  
**Стиль:** мягкий свет, натуральные цвета, без градиентов и оверлеев поверх лица

---

*Версия: 1.0 · Составлен на основе анализа кодовой базы · Обновлять при каждом архитектурном решении*