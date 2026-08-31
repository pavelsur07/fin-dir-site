# conwix.com — статический сайт

Лендинг Conwix. Сборки нет: `public/` раздаётся nginx'ом как есть.

## Где что лежит

| | |
|---|---|
| Контент | `public/` — html, `css/`, `js/`, `vendor/` (d3, topojson, карта) |
| Конфиг nginx | `docker/nginx/default.conf` |
| Сервис и маршруты | `../docker-compose.conwix.yml` |
| Выкатка | `../.github/workflows/deploy-conwix.yml`, на изменения в `conwix/**` |

Сайт живёт на общем хосте (`176.124.218.113`) рядом с `vashfindir.ru`, за общим
Traefik. Свой compose-файл и свой проект (`-p conwix`) — по конвенции из
`infra/traefik/README.md`: новый сайт не трогает ни прокси, ни соседей.

Не путать с `app.conwix.com` (`201.51.9.116`) — это отдельная машина и
отдельный репозиторий, отсюда не управляется.

## Локально

```bash
docker run --rm -p 8788:80 \
    -v "$PWD/conwix/public:/app/public:ro" \
    -v "$PWD/conwix/docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro" \
    nginx:1.27-alpine
```

Дальше http://127.0.0.1:8788 — та же конфигурация, что и в проде.

## Что наружу не отдаётся

`README.md`, `info.md` и `package.json` внутри `public/` приехали из шаблона, по
которому собран сайт: это руководство по сборке, а не контент. В git они нужны,
поэтому закрыты в nginx (`403`), а не удалены. `vendor/land-110m.json` при этом
отдаётся — это данные карты, и правило написано так, чтобы его не задеть.

## www

`www.conwix.com` **не настроен**: A-записи для него нет, поэтому и роутера в
compose нет. Роутер с `tls.certresolver` на нерезолвящийся домен заставил бы
Traefik по кругу проваливать ACME-проверку и жечь лимит Let's Encrypt.
Появится DNS — добавить блок по образцу `vf-www` из `docker-compose.prod.yml`.
