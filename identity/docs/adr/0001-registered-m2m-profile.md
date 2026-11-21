# ADR 0001: registered M2M token profile

Принято для MVP: официальный Keycloak 26.8.2, PostgreSQL 17, оптимизированный
образ и Python tooling с hash lock. Версии и digests: `versions.env`.
Настоящий Symfony `DocMcpOAuthVerifier` проверяет выпущенный токен в CI;
его проверки issuer/audience/типов/TTL не ослабляются ради совместимости.

Один confidential client относится к одному API и одному окружению.
Fixed Audience mapper + `client_id` mapper; Full Scope Allowed выключен.
Разрешённые scopes заданы явно, password/implicit/browser grants выключены.
RFC 8707 resource-indicators, CIMD, token exchange, offline access не включены.
Параметр resource не выбирает audience: подмена проверяется, audience остаётся
закреплённой за клиентом. Это профиль сервисной интеграции, не универсальная
совместимость с desktop/chat MCP-клиентами и не пользовательский SSO.

В 26.8.2 custom Admin API client требует `kc-admin-api` scope. Provisioner
имеет manage-clients/view-clients/view-realm только realm projects, без
manage-realm/users/master. На уровне realm делегированы ровно два типа mapper:
oidc-audience-mapper и oidc-hardcoded-claim-mapper. Проверены реальные create/
repeat apply, запрет изменения signing keys и запрет доступа к master.
Эта credential доверенная для конфигурации всех application clients данного
экземпляра; приложения её не получают. Per-project delegated administration
не обещается. Bootstrap admin удаляется после проверки ограниченного доступа;
человек identity-operator должен сменить временный пароль и настроить TOTP.

Источники: [Keycloak hostname](https://www.keycloak.org/server/hostname),
[security changes 26.8.2](https://www.keycloak.org/docs/latest/upgrading/index.html#_migrating_to_26_8_2).
