# Fin-dir WordPress theme

Проект разворачивает WordPress с кастомной темой `vashfindir`. Чтобы на продакшене тема точно попадала внутрь контейнера, используется собственный образ WordPress с предустановленными `wp-content/themes` и `wp-content/plugins`.

## Как запустить прод-окружение

1. Подготовьте `.env` с паролями БД (`VF_DB_PASSWORD`, `VF_DB_ROOT_PASSWORD`).
2. Соберите кастомный образ (тема окажется внутри контейнера, даже если на сервер не смонтирован `wp-content`):
   ```bash
   docker compose -f docker-compose.prod.yml build wordpress
   ```
3. Поднимите контейнеры:
   ```bash
   docker compose -f docker-compose.prod.yml up -d
   ```
4. Данные загрузок и языковые файлы сохраняются в именованных томах `wp_uploads` и `wp_languages`, поэтому они переживут пересборку образа.

Если WordPress не видел тему из-за пустого маунта `wp-content`, пересборка образа и запуск по шагам выше решают проблему: тема уже внутри образа и доступна ядру WordPress.
