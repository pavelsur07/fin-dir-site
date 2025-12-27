# Control Level (vf-control-level)

**Статус:** ✅ утверждён, production-ready  
**Версия:** v1.0  
**Дата фиксации:** 27.12.2025  
**Применение:** только главная (`front-page.php`)

---

## Назначение
- Дать пользователю выбор сценария:
    - «Я веду сам»
    - «Доверить финансы профи»
- Показать 2 режима сервиса без усложнения интерфейса

---

## Где используется
- `front-page.php` подключает блок отдельным template-part (не через общий sections-реестр страниц).

⚠️ Запрещено подключать этот блок на `/service`, `/service-findir`, `/academy-finance`.

---

## Файлы (канон)
- Шаблон:
    - `template-parts/landing/control-level.php`
- Стили:
    - `assets/css/landing.css` (строго scoped через `.vf-control`)
- Зависимость:
    - Bootstrap Icons (для классов `bi ...`)

---

## Структура (зафиксировано)
- Блок имеет id: `vf-control-level`
- В карточке иконка и заголовок находятся в одной строке через контейнер:
    - `.vf-control__top`

---

## UX правила (обязательные)
- Без “demo/test/tmp/draft” артефактов в названиях (см. `naming.md`)
- Стили изолированы и не ломают другие страницы (см. `README.md`)
- Mobile поведение проверено отдельно (см. `CONTRIBUTING.md`)
- Радиусы и тени соответствуют reference v21 (см. `CONTRIBUTING.md`)

---

## Иконки (Bootstrap Icons)
- Иконки заголовка (brand-color):
    - `bi-cpu`
    - `bi-person-badge`
- Иконки списка:
    - `bi-check-circle-fill`

⚠️ Если Bootstrap Icons не подключены — иконки не отображаются.
