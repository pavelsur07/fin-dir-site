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
- Для общих компонентов оставляем существующие:
    - `.btn`, `.btn-cta`, `.btn-cta-secondary` и т.д.

## Файлы шаблонов
- Общие части: `template-parts/<scope>/<name>.php`
- Специализации страниц:
    - `hero-front-page.php` — только для главной
    - `hero.php` — общий (не трогать без осознанной необходимости)

## Изображения
- Храним в теме, не в медиа-библиотеке:
    - `assets/img/hero/desktop-hero-home.jpg` и т.д.
- Название включает:
    - где используется (hero-home)
    - устройство (desktop/tablet/mobile)
