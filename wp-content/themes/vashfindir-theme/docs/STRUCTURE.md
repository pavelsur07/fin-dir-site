# Структура проекта vashfindir-theme

## Основные папки
- `inc/`
    - `setup.php` — theme supports, menus
    - `enqueue.php` — подключение CSS/JS
    - `helpers.php` — маленькие хелперы

- `assets/`
    - `css/theme.css` — глобальные стили + дизайн-токены
    - `js/theme.js` — лёгкий UI JS
    - `img/` — изображения темы

- `template-parts/` (Component-Based Architecture)
    - `layout/` — header-nav, footer и общие layout части
    - `atoms/` — атомы
    - `molecules/` — молекулы
    - `organisms/` — секции лендингов/страниц
    - `pages/` (опционально) — page-specific layout parts

- `templates/`
    - `page-landing.php` — сборка лендинга (организмы)
    - `page-service.php` — landing заглушка
    - `page-cfo.php` — landing заглушка
    - `page-about.php` — информационная страница

## Правило сборки страниц
Шаблон страницы (`templates/*.php`) должен содержать только:
- `get_header()`, `get_footer()`
- `<main>...</main>`
- `get_template_part()` для организмов/компонентов
- минимум логики вывода контента
