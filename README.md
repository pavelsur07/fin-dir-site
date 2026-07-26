# Ваш ФинДиректор

Сайт на Symfony с Nginx и PHP-FPM.

## Локальный запуск

```bash
make init
```

После запуска сайт доступен на <http://localhost:8001>.

Основные команды:

```bash
make up
make check
make logs
make down
```

## Production

Production-окружение описано в `docker-compose.prod.yml`. Для запуска нужен
`VF_SITE_APP_SECRET`; PHP-образы публикуются workflow
`.github/workflows/deploy-vashfindir.yml`.
