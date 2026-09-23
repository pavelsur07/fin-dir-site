# Stage 7 — Мини-CRM: обращения с форм сайта

## Контекст

Лид-форма на главной — демо: кнопка `type="button"`, данные никуда не уходят, рядом пометка
«работает в демо-режиме» (`site/templates/website/sections/_lead_form.html.twig`,
`pages/home.html.twig:123`). В `site/assets/scripts/website/navigation.js:231` остался
обработчик `.js-lead-form`, который показывает «Спасибо» даже при ошибке отправки.
Сейчас он не используется, но это фейковый успех.

**Цель:** каждое обращение сохраняется в базе, менеджер получает уведомление в Telegram и
ведёт обращения в админке. Модуль `Lead` — первый кирпич полной CRM из
`docs/plan/my_crm_plan.md` (Компания → Контакт → Сделка): обращение позже можно будет
конвертировать в сделку.

**Решения пользователя:**
- уведомления в Telegram, **синхронно**, **без персональных данных**: номер обращения, форма, источник, ответы квалификации и ссылка на карточку в админке;
- формы — **виджеты**: сейчас на главной, позже в статьях и других местах;
- два вида форм: **простая** (текущие поля: имя, контакт, задача, согласие) и **квалификационная** (плюс вопросы). В этом этапе — механизм и форма-пример «Диагностика», её размещение — отдельная задача;
- секреты Telegram в prod compose и deploy — **подтверждено** (Production Gate);
- срок хранения решается позже, сейчас только ручное удаление обращения из админки.

## Архитектура — модуль `site/src/Lead/`

### Каталог форм (в коде, без таблиц)

- `ValueObject/LeadFormCatalog` — реестр определений `LeadFormDefinition(key, title, questions[])`, `LeadQuestion(key, label, options[key => label])`:
  - `consultation` — простая форма, на главной;
  - `diagnostics` — квалификационная (пример, вопросы уточняются на ревью):
    - «Где продаёте?»: Wildberries / Ozon / Яндекс Маркет / Свой сайт / Несколько каналов;
    - «Оборот в месяц»: до 1 млн ₽ / 1–5 млн ₽ / 5–20 млн ₽ / больше 20 млн ₽;
    - «Что нужно?»: Разобраться в финансах / Финдиректор на аутсорсе / Учёт в SaaS / Пока не знаю.
- `Twig/LeadFormExtension` — функция `lead_form(key)` отдаёт определение виджету. Одно определение используется и в разметке, и при серверной валидации, без дублирования.

### Данные

- **`Entity/Lead`** (`lead_lead`):
  - `submissionId` uuid, unique;
  - `formKey`, `name`, `contact`, `contactNormalized`, `contactType` (email / phone / telegram / other), `task`;
  - `answers` json — снимок `[{question, questionLabel, answer, answerLabel}]`: ответ читается, даже если вопрос потом изменят;
  - `pageUrl`, `referrer`, `utm` json;
  - `consentAt`, `consentVersion`;
  - `status`: NEW / IN_PROGRESS / QUALIFIED / SPAM / CLOSED, `spamReason`, `nextContactAt`;
  - `notifiedAt`, `notificationError`, `createdAt`, `updatedAt`, `#[Version]`.
- IP и User-Agent **не храним** (минимизация ПД). IP нужен только лимитеру.
- **`Entity/LeadNote`** (`lead_note`): текст и время, FK на lead с `ON DELETE CASCADE`.
- Методы Entity: `changeStatus()`, `scheduleNextContact()`, `addNote()`, `markNotified()`, `markNotificationFailed()`.

### Приём обращения

1. `POST /lead` (`Controller/LeadSubmitController`), вход — `DTO/LeadSubmission` + Validator:
   - поля формы, `answers` (проверка по каталогу), `consent`;
   - `submission_id` (UUID, генерирует JS);
   - honeypot `website`, `started_at`;
   - `page_url`, `referrer`, `utm_*`.
2. **CSRF без сессии:** `framework.csrf_protection.stateless_token_ids: [lead_submit]` → `SameOriginCsrfTokenManager` сверяет `Origin`/`Referer`. Stage 4 не затрагивается: `authenticate` и `logout` остаются на сессии.
3. **Лимит:** `rate_limiter.lead_submit` — 5 заявок за 10 минут с одного IP → 429. Счётчик у каждой реплики свой, это приемлемо.
4. **`Service/LeadRegistrar`:**
   - повтор `submissionId` → возвращается уже созданное обращение (идемпотентность, дубля нет);
   - антиспам: заполненный honeypot или заполнение быстрее 3 секунд → статус `SPAM` с причиной, без уведомления, ответ как у успеха;
   - `ContactNormalizer`: email в нижнем регистре; телефон → `+7XXXXXXXXXX`; `@user` → telegram;
   - `flush()`, затем **после коммита** `Adapter/TelegramLeadNotifier`.
5. **`TelegramLeadNotifier`:**
   - `symfony/http-client`, таймаут 3 секунды, текст без ПД;
   - результат записывается в `notifiedAt` или `notificationError` вторым коротким `flush()`. Это осознанное отступление от «одного flush» (PATTERNS §5.2: побочный эффект после коммита, его результат сохраняется отдельно);
   - пустые `TELEGRAM_BOT_TOKEN` / `TELEGRAM_LEAD_CHAT_ID` → уведомления выключены, заявка всё равно сохраняется.
6. **Ответы:**

   | Ситуация | JSON | Без JS |
   |---|---|---|
   | принято, в том числе повтор и спам | `201 {ok}` | `website/pages/lead_result.html.twig`, `noindex` |
   | ошибка валидации | `422 {errors: {поле: текст}}` | та же страница |
   | чужой Origin | `403` | — |
   | лимит | `429` | — |

7. **Повтор уведомлений:** консольная команда `app:lead:notify-pending` (не спам, без `notifiedAt`, за последние 7 дней) и кнопка «Отправить повторно» в карточке. Cron — отдельно.

### Виджет формы (Public Website, по SITE_RULES)

- **`sections/_lead_form.html.twig` становится боевым**, параметр `form: 'consultation' | 'diagnostics'`:
  - `method="post" action="/lead"`, `type="submit"`;
  - вопросы квалификации через существующий `_form_select`, новых компонентов нет;
  - скрытые поля: honeypot скрыт и от доступности (`aria-hidden`, `tabindex="-1"`, `autocomplete="off"`);
  - блоки состояния `role="status"` и `role="alert"`, запасной контакт в Telegram;
  - `csrf_token('lead_submit')`: для stateless id это константа, кеш страницы не ломается.
- **JS:** обработчик в `navigation.js` переписывается, отдельный файл и изменения Makefile не нужны. Он:
  - ставит `submission_id`, `started_at`, `page_url`, `referrer`, UTM;
  - отправляет через `fetch` с `Accept: application/json`;
  - блокирует кнопку и показывает «Отправляем…»;
  - при 422 выводит ошибки у полей (`aria-invalid` и текст); при другой ошибке — сообщение и контакт в Telegram;
  - цель Метрики `lead_form_submit` — **только после 201**.
- **Главная:** `form: 'consultation'`, пометка о демо-режиме убирается.
- **`SITE_RULES.md` §7.3:** контракт Lead Form обновляется (боевая форма, `form`-ключ, состояния), удаляется запрет «Lead demo использует type=button». В `/ui-kit/sections` показываются оба вида формы; внутри каталога у них `data-vf-demo-form` без реальной отправки.
- **Ассеты:** после правки `navigation.js` — `make assets` и новый `vf_asset_version`.

### Админка `/admin/leads`

- **Список:**
  - фильтр по статусу и форме, поиск по контакту (по нормализованному значению);
  - сортировка по дате (новые сверху), по 20 на страницу;
  - значок «уведомление не отправлено»;
  - ссылка «Обращения» в шапке и на дашборде.
- **Карточка:**
  - все поля, ответы квалификации, источник, UTM, согласие;
  - статус (форма + CSRF + version), дата следующего контакта;
  - заметки (лента и добавление);
  - «Другие обращения с этим контактом»;
  - «Отправить уведомление повторно»;
  - «Удалить обращение» (POST + CSRF + подтверждение) — для запросов субъекта ПД.
- Реализация по образцу Stage 5: Query + DTO + Pagerfanta, Application Services, общий `DomainExceptionListener`.

### Конфиг и инфраструктура

- Пакет `symfony/http-client`.
- `framework.yaml`: `csrf_protection.stateless_token_ids: [lead_submit]`, `rate_limiter.lead_submit`.
- `services.yaml`: `env(TELEGRAM_BOT_TOKEN)` и `env(TELEGRAM_LEAD_CHAT_ID)` по умолчанию `''`, поэтому CI и тесты работают без изменений.
- `docker-compose.yml`: пустые значения для dev.
- **Production Gate (подтверждено):**
  - `docker-compose.prod.yml` (fpm и cli): `TELEGRAM_BOT_TOKEN: ${VF_TELEGRAM_BOT_TOKEN:-}`, `TELEGRAM_LEAD_CHAT_ID: ${VF_TELEGRAM_LEAD_CHAT_ID:-}`;
  - `deploy-vashfindir.yml`: два `export` из GitHub secrets.
- `doctrine.yaml`: mapping `Lead`. `routes.yaml`: resource `src/Lead/Controller/`. `deptrac.yaml`: слой `Twig`.
- Миграция через `make diff`: только `CREATE TABLE lead_lead` и `lead_note`.

## Шаги

1. `docs/plan/Stage_7_Leads.md`.
2. Каталог форм и `ContactNormalizer` + unit-тесты.
3. Entity, миграция, репозитории.
4. `LeadRegistrar`, `TelegramLeadNotifier`, команда `notify-pending` + integration-тесты (`MockHttpClient`).
5. `POST /lead`: DTO, CSRF, лимит, ответы JSON и HTML + functional-тесты.
6. Виджет, JS, главная, `/ui-kit`, `SITE_RULES.md`, ассеты.
7. Админка: список, карточка, статус, заметки, удаление, повтор уведомления + functional-тесты.
8. Env, prod compose, deploy workflow.
9. `make ci`, E2E, Playwright, self-review, Claude Code review, отчёт.

## Тесты

| Уровень | Что |
|---|---|
| Unit | `ContactNormalizer` (email, телефон 8/+7/10 цифр, telegram, прочее); каталог (неизвестная форма, чужой ответ, снимок ответов); `Lead` (статусы, уведомление); текст Telegram **не содержит имени, контакта и задачи**, HTML экранирован |
| Integration | `LeadRegistrar` на `site_test`: сохранение, идемпотентность `submissionId`, спам по honeypot и по времени, сбой Telegram записан, а обращение сохранено; `notify-pending` досылает; админский Query (фильтры, поиск, пагинация) |
| Functional | `POST /lead`: 201 и запись; 422 с ошибками полей; нет согласия → 422; неизвестная форма и ответ вне каталога → 422; honeypot → 201 + SPAM; чужой Origin → 403; лимит → 429; без JS — HTML-страница результата. Главная: форма боевая, пометки «демо» нет. Публичные страницы по-прежнему без cookie (`SmokeTest`). Админка: вход обязателен, список, карточка, статус с CSRF, заметки, удаление, повторная отправка |
| E2E | Playwright в dev: заполнить форму на главной, увидеть «Спасибо», обращение появилось в админке. Ошибка валидации показывается у поля. Скриншоты состояний на 375/1440. Отправка в Telegram — на тестовом боте, если дашь токен; иначе через `MockHttpClient` в тестах, с честной пометкой в отчёте |

## Проверка

`make ci`; `doctrine:migrations:diff` → «No changes»; Playwright-сценарий; curl: 201/422/403/429.

## Вне объёма

- размещение формы «Диагностика» и форм в статьях;
- автоматическое обезличивание по сроку;
- cron для `notify-pending`;
- конвертация обращения в Компанию и Сделку (полная CRM);
- email-уведомления, ответственные, воронки.
