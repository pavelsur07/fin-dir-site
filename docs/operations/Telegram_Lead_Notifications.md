# Уведомления о заявках в Telegram — настройка

**Статус на 2026-10-13:** уведомления работают через релей бота. Сайт на RU-хостинге
не достаёт до api.telegram.org (блокировка), поэтому отправляет готовый текст
релею бота `vf_materials_bot`, а тот пересылает его в Telegram с NL-хостинга.

## Как это устроено

- сайт: `site/src/Lead/Adapter/TelegramLeadNotifier.php` — POST `{text}` на
  `LEAD_RELAY_URL` с заголовком `X-Lead-Token: LEAD_RELAY_TOKEN`, таймаут 3 секунды.
  Заявка сохраняется до отправки, поэтому недоступный релей не теряет обращение;
- релей: `POST /api/leads` админки бота проверяет токен и пересылает текст в
  группу. В админке бота (`/admin/`) есть кнопка теста релея;
- переменные окружения: `docker-compose.yml` / `docker-compose.prod.yml`
  (`LEAD_RELAY_URL`, `LEAD_RELAY_TOKEN`), значения — из GitHub secrets
  `VF_LEAD_RELAY_URL`, `VF_LEAD_RELAY_TOKEN` (см. `deploy-vashfindir.yml`);
- по умолчанию значения пустые (`site/config/services.yaml`): без них сайт
  работает и заявки сохраняются, уведомления выключены.

Переменные `TELEGRAM_BOT_TOKEN` / `TELEGRAM_LEAD_CHAT_ID` упразднены:
токен Telegram-бота на сайте больше не хранится.

## Что внести в GitHub secrets

| Secret | Что это | Пример |
|---|---|---|
| `VF_LEAD_RELAY_URL` | URL релея бота | `https://mybots.vashfindir.ru/api/leads` |
| `VF_LEAD_RELAY_TOKEN` | Общий секрет сайта и бота (`LEAD_API_TOKEN` в `.env` бота) | случайная строка |

Оба секрета нужны вместе: если задан только один, уведомления остаются выключенными.

Где заводить: GitHub → репозиторий → **Settings → Secrets and variables → Actions → New repository secret**.

Секреты подставляются в контейнеры при деплое, поэтому после их добавления нужен
новый деплой: **Actions → Deploy vashfindir to production → Run workflow** (коммит
не обязателен) или re-run последнего успешного запуска.

## Проверка

1. Тест релея без сайта: в админке бота кнопка «Отправить тестовое сообщение в
   группу» или `curl -X POST $VF_LEAD_RELAY_URL -H "X-Lead-Token: …" -d '{"text":"test"}'`;
2. Отправить тестовую заявку с главной сайта. В чат приходит «Новое обращение №…»
   со ссылкой «Открыть в админке»;
3. В карточке заявки — «Уведомление: отправлено …». Удалить тестовую заявку.

## Если сообщение не пришло

Причина видна в карточке заявки:

- `Lead relay responded 401` — неверный `VF_LEAD_RELAY_TOKEN` (должен совпадать
  с `LEAD_API_TOKEN` в `.env` бота);
- `Lead relay responded 503` — релей выключен на стороне бота: не заданы
  `LEAD_API_TOKEN` / `LEADS_CHAT_ID` в `.env` бота или не перезапущена админка;
- `Lead relay responded 502` — Telegram отклонил сообщение: бот не в группе или
  неверный `LEADS_CHAT_ID` / `LEADS_TOPIC_ID` в `.env` бота;
- `Lead relay request failed: …` — сайт не достучался до релея (сеть/DNS/nginx).

После исправления секретов и нового деплоя дослать пропущенное:

- кнопка **«Отправить повторно»** в карточке заявки;
- или `make console CMD="app:lead:notify-pending"` — досылает заявки, кроме
  спама, за последние 7 дней. Перед запуском экспортировать те же `VF_*`,
  что и при деплое (compose остановится на проверке `:?` без них).

## Что уходит в Telegram (152-ФЗ)

Только номер заявки и ссылка на её карточку в админке. Ни данных формы, ни
названия формы, ни страницы, ни ответов на вопросы, ни UTM — их видно только в
админке. Трансграничной передачи персональных данных нет, это совпадает с
согласием на обработку данных (`/consent`, пункт 4).

## Если секрет утёк

Сгенерировать новый `LEAD_API_TOKEN`, вписать его в `.env` бота и в
`VF_LEAD_RELAY_TOKEN`, перезапустить админку бота и перевыкатить сайт.

## Где это в коде

- отправка: `site/src/Lead/Adapter/TelegramLeadNotifier.php` (таймаут 3 секунды),
  текст сообщения: `LeadNotification.php`;
- окружение: `site/config/services.yaml`, `docker-compose.yml`,
  `docker-compose.prod.yml`, `.github/workflows/deploy-vashfindir.yml`;
- сторона релея: репозиторий `vf_materials_bot` — `bot/admin/routes/leads.py`,
  `bot/modules/leads/`, кнопка теста в дашборде админки.
