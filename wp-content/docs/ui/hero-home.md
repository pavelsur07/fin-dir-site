# Hero Home (Эталонный блок главной страницы)

**Статус:** ✅ утверждён, production-ready  
**Версия:** v1.0  
**Дата фиксации:** 26.12.2025  
**Применение:** только главная (`front-page.php`)

---

## Назначение
- Зафиксировать позиционирование: «финансовая система для роста»
- Разделить сценарии потребления:
    - SaaS: «Веду сам»
    - Сервис + CFO: «Доверить финансы профи»
- Дать премиальный B2B-первый экран без инфобиза

---

## Где используется
- `front-page.php` подключает:
    - `template-parts/landing/hero-front-page.php`

⚠️ Запрещено использовать этот hero на `/service`, `/service-findir`, `/academy-finance`.

---

## Файлы (канон)
- Шаблон:
    - `template-parts/landing/hero-front-page.php`
- Стили:
    - `assets/css/landing.css` (строго scoped через `.vf-hero4`)
- Изображения:
    - `assets/img/hero/desktop-hero-home.jpg`
    - `assets/img/hero/tablet-hero-home.jpg`
    - `assets/img/hero/mobile-hero-home.jpg`

---

## Контент (зафиксирован)
### H1
Финансовая система для роста, а не отчётов

### Подзаголовок
Устойчивый рост и порядок в финансах бизнеса —
за счёт системы, а не ручного контроля.

### CTA
- Primary: «Веду сам» → `/service`
- Secondary: «Доверить финансы профи» → `/service-findir`

---

## UX правила
- Desktop/Tablet: CTA в одну строку, primary+secondary
- Mobile: CTA столбиком, каждая кнопка `width: 100%`, `min-height: 44px`

---

## Performance
- Hero image = LCP
- `<img>` должен иметь:
    - `loading="eager"`
    - `fetchpriority="high"`

---

## Запрещено
- Любые `demo`/`test` артефакты
- Изменять `template-parts/landing/hero.php` ради главной
- Подключать hero-картинки из Media Library
