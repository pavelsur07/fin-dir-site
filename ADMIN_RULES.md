# Правила административного раздела «Ваш Финдир»

## Граница и ресурсы

До появления React Admin действует временное исключение Stage 4: Symfony/Twig,
`site/templates/admin/`, серверная session authentication и CSRF. Эта задача
не вводит React и не меняет бизнес-логику или security.

Admin и Public Website имеют независимые CSS/JS bundles. Admin подключает
только `/assets/admin/`, Website — только `/assets/website/`. Общими могут быть
локальные шрифты, логотип и неизменяемые основания дизайн-системы:
`site/assets/styles/shared/vf-fonts.css`, `vf-theme.css`, `vf-palette.css`.
Импорт entrypoint, JS или compiled CSS другой UI-зоны запрещён. Tailwind source
сканирует только свою зону; для логотипа и Markdown preview разрешены точечные
общие источники. Общие токены не редактировать в задачах оформления Admin.

Tailwind CSS 4.3.3 работает только build-time. Исходники Admin:
`site/assets/styles/admin/app.css`, `site/assets/scripts/admin/`; результат:
`site/public/assets/admin/`. Использовать существующие токены. Собственные
правила `.admin-*` разрешены только в admin entrypoint для геометрии, поведения,
адаптивности и доступности; они не попадают в bundle Website.

## Меню и слои

Меню действий таблиц использует `admin/_row_menu.html.twig`. С JS и поддержкой
Popover API это `popover="auto"`, нативная кнопка с `popovertarget`, верхний
слой браузера, закрытие по Escape и клику снаружи. Одновременно открыто одно
меню. Прокрутка страницы/таблицы, изменение viewport или мобильной навигации
закрывают меню; внутренняя прокрутка самого меню сохраняет его открытым.
Меню ограничивается viewport и при нехватке места снизу открывается сверху.
Без JS/API остаётся нативный details с действиями в потоке таблицы.

Действия внутри раскрывающихся меню оформлять через `admin/_menu_item.html.twig`:
строка без рамки, выравнивание слева, общий hover и видимый focus. Переходы
остаются ссылками, изменения состояния — submit-кнопками внутри POST-форм
с CSRF. Удаление выделяется семантическим цветом ошибки и сохраняет подтверждение.
Основные действия вне меню используют `admin/_button.html.twig`.

Не помещать absolute dropdown внутрь overflow-контейнера таблицы и не пытаться
устранить clipping увеличением z-index. Не назначать role="menu" обычному
набору ссылок и форм: они сохраняют стандартную клавиатурную навигацию.

Шкала обычного документа задаётся централизованно в admin CSS:
контент — auto, навигация — `--admin-layer-navigation: 10`, skip link —
`--admin-layer-skip-link: 20`. Popover принадлежит top layer, числовой z-index
ему не нужен. Новые произвольные z-index не добавлять.

JS не оформляет интерфейс. Единственное исключение — вычисленные `left/top`
меню относительно кнопки с учётом viewport; размеры и внешний вид задаёт CSS.

## Формы и адаптивность

Вход — ограниченная по ширине карточка по центру экрана. Поля имеют видимые
labels, ошибки и focus; минимальная высота интерактивных элементов — 44px.
На узком экране уменьшать внутренние отступы. Длинные URL, контакты и тексты
переносятся; горизонтальная прокрутка допускается внутри таблицы, но не всей
страницы. Sidebar и мобильное меню учитывают динамическую высоту viewport,
длинная навигация прокручивается внутри своей области.

## Сборка и проверка

- `make admin-assets` — сборка только Admin; `make admin-assets-watch` — watch.
- `make admin-asset-version` — хеш admin bundle, значение переносится в
  `admin_asset_version` в `site/templates/admin/layout.html.twig`.
- `make admin-assets-check` — проверка свежести только Admin.
- `make assets` / `make assets-check` — обе UI-зоны; `make ds-check` проверяет
  каждую зону по её собственному CSS. При изменении shared пересобрать обе.
- `make admin-browser-check` — Chromium на HTML из откатываемой тестовой БД;
  требует локальный dev nginx на порту 8001. Зависимость Playwright изолирована
  в одноразовом Docker-контейнере, формы не отправляются.
- Проверить Twig, затронутые functional tests и браузерное поведение на ширинах
  320, 375, 768, 1024, 1440px, включая низкий экран, клавиатуру, последнюю строку
  таблицы, длинные значения и режим без JS. Команды определяются Makefile.

Источники поведения: [Popover API](https://developer.mozilla.org/en-US/docs/Web/API/Popover_API/Using)
и [CSS overflow](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/overflow).
