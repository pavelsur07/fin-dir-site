# ADR 0002: independent service, shared repository and host

Пользователь выбрал монорепо и текущий production сервер сайта, добавил
DNS auth.vashfindir.ru и auth-admin.vashfindir.ru. Это явно согласованное
исключение из исходного предпочтения отдельной VM. Самописный OAuth server,
новая бизнес-модель Symfony и миграция Admin сайта на SSO не требуются.

Identity имеет собственную БД, volume, private network, secrets, Compose project,
CI и immutable releases вне checkout сайта. Root Makefile только делегирует.
Общий Traefik получает labels новых auth-hostnames, его сервис не обновляется.
Production artifact исключает site/conwix/untracked secrets. Consumer исходники
нужны исключительно CI и не являются runtime dependency Identity.

На хосте 2 ГБ RAM; Keycloak cache=local, heap384MB, container640MB, PostgreSQL192MB,
ingress192MB, connection pool10. Это один экземпляр без HA. Staging — transient
изолированный стенд до production, без постоянного второго IAM на этом хосте:
память, отдельный staging DNS и VPN владельцем не предоставлены. Стенд использует
другую БД, credentials и signing keys, не обращается к production.

Admin IP/VPN не указан: публичная админка закрыта. Оператор — только SSH и
временный trusted container в Identity network. Пользовательский browser login
допускается через private SSH tunnel после настройки локального DNS и доверия
операторскому CA. Публичный TLS — Let's Encrypt через существующий Traefik;
операторский CA не распространяется на внешних клиентов.
