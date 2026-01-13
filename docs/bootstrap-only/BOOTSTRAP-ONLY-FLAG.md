# VF_BOOTSTRAP_ONLY

## Как включить
Добавьте в `wp-config.php`:

```php
define('VF_BOOTSTRAP_ONLY', true);
```

## Ожидаемое поведение

### `VF_BOOTSTRAP_ONLY = true`
* Подключаются только Bootstrap 5.3.3 CSS и JS bundle.
* Кастомные стили и скрипты темы отключаются.
* Bootstrap Icons подключаются **только** на тех же страницах, где это было в legacy-режиме.

### `VF_BOOTSTRAP_ONLY = false` (или не задан)
* Поведение полностью соответствует текущему (AUDIT v1).
* Bootstrap 5.3.3 CSS и JS bundle подключаются, но кастомные стили могут их перекрывать.

## Какие ассеты отключаются при `true`
* `assets/css/main.css` (`vashfindir-main`)
* `assets/js/main.js` (`vashfindir-main`)
* `assets/css/landing.css` (`vashfindir-landing`)
* `assets/css/info.css` (`vashfindir-info`)
