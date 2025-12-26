# Hero Service Pages (Hero3, wide 16:9)

**Статус:** утверждён  
**Применение:** service, service-findir

---

## Назначение

Hero3 используется на сервисных страницах и предназначен для:
- объяснения формата услуги
- представления сервиса или сопровождения
- перехода к регистрации или работе с финдиром

---

## Где используется

- `/service`
- `/service-findir`

Подключается через:
- `page-service.php`
- `page-service-findir.php`

---

## Техническая реализация

- Template-part:
# Hero Service Pages (Hero3, wide 16:9)

**Статус:** ✅ утверждён  
**Применение:** только `/service` и `/service-findir`

---

## Назначение
Hero3 используется на сервисных страницах и предназначен для:
- объяснения формата услуги
- презентации сервиса или сопровождения CFO
- перехода к регистрации / входу

---

## Где используется
- `/service` → `page-service.php`
- `/service-findir` → `page-service-findir.php`

---

## Реализация в теме
- Template-part:
    - `template-parts/landing/hero-wide.php`
- Стили:
    - `assets/css/landing.css`
    - строго scoped через `.vf-hero3`
- Медиа-формат:
    - wide 16:9

---

## Контент (настраиваемый)
Контент задаётся на уровне каждой страницы через `$args`:
- `kicker`
- `title`
- `lead`
- `primary` CTA (label + url)
- `secondary` CTA (label + url)
- `media` (desktop/tablet/mobile + alt)

---

## Изображения (реальные файлы)
Путь:
- `wp-content/themes/vashfindir/assets/img/hero/`

### Для `/service`
- `desktop-hero-service.jpg`
- `tablet-hero-service.jpg`
- `mobile-hero-service.jpg`

### Для `/service-findir`
- `desktop-hero-service-findir.jpg`
- `tablet-hero-service-findir.jpg`
- `mobile-hero-service-findir.jpg`

---

## UX правила CTA
- Desktop / Tablet: CTA в строку
- Mobile: CTA столбиком, `width: 100%`
- Высота кнопок: `min-height: 44px`

---

## Запрещено
- использовать `demo/test/tmp/draft` в именах, классах, id
- подключать этот hero на главную страницу
- изменять общий `template-parts/landing/hero.php` ради service-страниц
