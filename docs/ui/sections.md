# Реестр секций главной страницы

Источник: `wp-content/themes/vashfindir/front-page.php` и partial'ы из `template-parts/landing/`.

| Order | Section | Partial | Root class | CSS file |
| --- | --- | --- | --- | --- |
| 1 | Hero: «Финансовая система для роста, а не отчётов» | `wp-content/themes/vashfindir/template-parts/landing/hero-front-page.php` | `.vf-hero4` | `assets/css/landing.css` |
| 2 | «Выберите ваш уровень контроля» | `wp-content/themes/vashfindir/template-parts/landing/control-level.php` | `.vf-control` | `assets/css/landing.css` |
| 4 | «Готовы навести порядок в финансах?» | `wp-content/themes/vashfindir/template-parts/landing/lead-capture.php` | `.vf-lead` | `assets/css/landing.css` |

Sections container: `wp-content/themes/vashfindir/template-parts/landing/sections.php` — файл-контейнер, подключает дополнительные секции через `get_template_part`. Сам ничего не рендерит.

## Control Level: правила для карточек

Для карточек Control Level базовые фон/бордер/радиус задаёт только `vf-surface`, а все локальные отличия в этом блоке делаются через CSS-переменные `--vf-surface-*`. Например, у `.vf-control__card` и `.vf-control__card--pro` допускаются только overrides переменных (включая цвет бордера).

Локальный `box-shadow` можно задавать как свойство компонента, если того требует дизайн, но без переопределения базовых параметров поверхности вне `vf-surface`.
