# UI Change Log

Фиксируем изменения UI-эталонов, чтобы команда (и будущий ты) понимали, что было принято и почему.

## 2025-12-27 — Control Level v1.0
- Зафиксирован блок выбора сценария на главной: `vf-control-level`
- Шаблон блока вынесен в отдельный template-part:
    - `template-parts/landing/control-level.php`
- Иконки реализованы через Bootstrap Icons (`bi-*`)
- Иконка и заголовок выровнены в одну линию через `.vf-control__top`
- Стили блока изолированы через `.vf-control` и не влияют на другие страницы

## 2025-12-26 — Hero Home v1.0
- Добавлен отдельный hero только для главной:
    - `template-parts/landing/hero-front-page.php`
- Hero использует реальные изображения через `<picture>`:
    - desktop/tablet/mobile
- CTA в мобильной версии:
    - full-width
    - `min-height: 44px`
- Стили hero изолированы через `.vf-hero4` и не влияют на другие страницы
- Полностью исключены любые “demo” артефакты

## 2025-12-26 — Hero Service Pages v1.0
- Добавлен hero для страниц:
    - `/service` (`page-service.php`)
    - `/service-findir` (`page-service-findir.php`)
- Используется template-part:
    - `template-parts/landing/hero-wide.php`
- Контент на каждой странице настраивается через `$args` (kicker/title/lead/CTA/media)
- Подключены реальные изображения (wide 16:9) по неймингу:
    - `assets/img/hero/*-hero-service.jpg`
    - `assets/img/hero/*-hero-service-findir.jpg`
- Стили изолированы через `.vf-hero3`
- Полностью исключены любые “demo” артефакты
