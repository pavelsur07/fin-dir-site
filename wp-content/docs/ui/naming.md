# Naming rules (классы / файлы / элементы)

## Основное правило
В прод-коде **никогда** не должно встречаться:
- `demo`
- `test`
- `tmp`
- `draft`
- `final-final`
- `old/new` в именах классов и id

## Стили: scoped и предсказуемо
- Для блоков: префикс `vf-` и BEM-подход:
    - `.vf-hero4`, `.vf-hero4__title`, `.vf-hero4__actions`
    - `.vf-hero3`, `.vf-hero3__title`, `.vf-hero3__actions`
- Для общих компонентов используются существующие классы:
    - `.btn`, `.btn-cta`, `.btn-cta-secondary`

## Файлы шаблонов (theme templates)
- Общие части: `template-parts/<scope>/<name>.php`
- Специализации страниц (hero):
    - `template-parts/landing/hero-front-page.php` — только для главной (Hero4)
    - `template-parts/landing/hero-wide.php` — только для `/service` и `/service-findir` (Hero3, wide 16:9)
    - `template-parts/landing/hero.php` — общий hero (не менять под конкретную страницу)

## Изображения hero
Hero-изображения храним в теме (не в Media Library):
- `wp-content/themes/vashfindir/assets/img/hero/`

### Канонический шаблон имени
`<device>-hero-<page>.jpg`

Где:
- `<device>`: `desktop` | `tablet` | `mobile`
- `<page>`: `home` | `service` | `service-findir`

### Примеры (home)
- `desktop-hero-home.jpg`
- `tablet-hero-home.jpg`
- `mobile-hero-home.jpg`

### Примеры (service)
- `desktop-hero-service.jpg`
- `tablet-hero-service.jpg`
- `mobile-hero-service.jpg`

### Примеры (service-findir)
- `desktop-hero-service-findir.jpg`
- `tablet-hero-service-findir.jpg`
- `mobile-hero-service-findir.jpg`
