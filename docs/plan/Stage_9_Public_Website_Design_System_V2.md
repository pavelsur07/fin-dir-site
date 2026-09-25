# Stage 9 — Public Website design system v2

## Контракт перехода

Одним выпуском перевести все публичные Twig-страницы на v2 из `design-system-inbox/handoff`. Symfony, маршруты, опубликованные тексты, SEO, формы и Admin остаются владельцами прежних контрактов. Hero главной — светлый, текстовый, без фотографии. Макетные цены и возможности кабинета не публикуются.

## Исходный инвентарь

| Маршрут | Страница и секции |
|---|---|
| `/` | home: hero, problem, benefits, steps, cases, FAQ, lead form, CTA |
| `/services`, `/cases`, `/about`, `/partners` | маркетинговые секции из `website/sections` |
| `/gazeta`, `/gazeta/{slug}` | список публикаций, пагинация, статья Markdown |
| `/privacy`, `/offer`, `/consent` | юридический контент |
| `/lead/result` | результат отправки формы |
| `/ui-kit`, `/ui-kit/sections` | каталог production components и sections, noindex |
| ошибки 404 и 500 | Twig страницы с общим website layout |

Общий layout хранит title, description, robots, canonical, Open Graph, Twitter и Organization/WebSite JSON-LD. Страницы переопределяют нужные блоки. Смена стилей не меняет тексты метаданных, canonical URL и schema type. OG-asset получает новый URL из-за immutable cache.

Форма заявки сохраняет `POST /lead`, поля `name`, `contact`, `task`, `answers[*]`, `agreement`, `_token`, `submission_id`, `fill_ms`, `page_url`, `referrer` и honeypot `website`. JS использует `data-vf-lead-form`, `data-vf-lead-success`, `data-vf-lead-error`; навигация использует `data-vf-menu-*`. Согласие изначально не отмечено.

## Реализация

1. Заменить foundations в единственном website `app.css`, сверив JSON и CSS из handoff. CSS пакета дополняет JSON тёмной темой и компонентными переменными. Tailwind 4.3.3 и `Makefile` остаются.
2. Перевести components и sections на семантические utilities; сохранить DOM hooks, формы, маршруты и доступность.
3. Пересобрать страницы и `/ui-kit` на production компонентах. Удалить используемые v1 aliases и Onest.
4. Обновить локальные fonts, logo, favicon, OG asset, документацию и тесты.
5. Собрать assets, обновить release hash, выполнить проверки, браузерный просмотр и независимое ревью.

## Проверки по уровням

- Unit: контрастность ключевых пар токенов и исходный guard запрещённых v1 aliases.
- Integration: N/A, persistence и внешние адаптеры не меняются.
- Functional: публичные маршруты, форма, блог и пагинация, SEO/JSON-LD, cookie, UI-kit.
- E2E: меню → форма → результат в браузере на 320/375/768/1024/1440 px.

## Release

Выпускать все страницы вместе с новым compiled CSS. Админ, `conwix/`, Traefik и исходный дизайн-пакет не входят в runtime-переход.
