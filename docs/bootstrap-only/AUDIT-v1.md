# AUDIT v1 — кастомные стили/классы и точки подключения

## 1. Подключение CSS/JS через `inc/enqueue.php`

Источник: `wp-content/themes/vashfindir/inc/enqueue.php`.

### Базовые подключения (всегда)
- `assets/css/main.css` — `wp_enqueue_style('vashfindir-main', ...)` (подключается всегда).
- `assets/js/main.js` — `wp_enqueue_script('vashfindir-main', ...)` (подключается всегда, в footer).

### Условные подключения (landing/DS страницы)
Подключается **одновременно** на страницах:
- `is_front_page()`
- `is_page(['service', 'service-findir', 'academy-finance', 'design-system'])`
- **или** если шаблон страницы равен `page-design-system.php` (через `get_page_template_slug(get_queried_object_id())`).

При выполнении условия подключаются:
- `bootstrap-icons` с CDN (`https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css`).
- `assets/css/landing.css` — зависит от `vashfindir-main` и `bootstrap-icons`.

### Условные подключения (about)
- Только на `is_page('about')` подключается `assets/css/info.css` (зависит от `vashfindir-main`).

## 2. PHP-файлы темы с префиксом классов `vf-`

Ниже перечислены файлы, где реально встречаются классы с префиксом `vf-`:

- `wp-content/themes/vashfindir/page-design-system.php`
- `wp-content/themes/vashfindir/footer.php`
- `wp-content/themes/vashfindir/header.php`
- `wp-content/themes/vashfindir/template-parts/landing/faq.php`
- `wp-content/themes/vashfindir/template-parts/landing/hero.php`
- `wp-content/themes/vashfindir/template-parts/landing/_section-boilerplate.php`
- `wp-content/themes/vashfindir/template-parts/landing/lead-capture.php`
- `wp-content/themes/vashfindir/template-parts/landing/hero-wide.php`
- `wp-content/themes/vashfindir/template-parts/landing/hero-front-page.php`
- `wp-content/themes/vashfindir/template-parts/landing/control-level.php`
- `wp-content/themes/vashfindir/template-parts/blocks/breadcrumbs.php`
- `wp-content/themes/vashfindir/template-parts/blocks/tariffs-2.php`
- `wp-content/themes/vashfindir/template-parts/info/content.php`

## 3. JS-зависимости от DOM-элементов `vf-*` (из `assets/js/main.js`)

В `wp-content/themes/vashfindir/assets/js/main.js` используются следующие селекторы:

- `[data-vf-mobile-open]` — кнопка открытия мобильного меню.
- `#vfMobileNav` — контейнер мобильного меню.
- `[data-vf-mobile-close]` — кнопка закрытия мобильного меню.
- `.vf-mobile__backdrop` — оверлей мобильного меню.
- `.vf-nav__link` — ссылки основной навигации (для `aria-current`).
- `.vf-mobile__link` — ссылки мобильного меню (для `aria-current`, автофокуса и закрытия меню при клике).
