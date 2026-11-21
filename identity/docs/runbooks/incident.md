# Incident / break glass

Issuer outage: новые token exchanges не работают. MCP verifier при невозможности
получить необходимые discovery/JWKS закрывает доступ с503; никакого auth bypass.
Уже закешированные public keys допускают проверку ещё действующего JWT.
Текущий Admin сайта со своей Symfony session не зависит от Identity.

Проверить состояние только identity-production, память/health/private readiness,
сроки сертификатов, DBconnections и audit. Не выводить env/tokens/request bodies.
Не перезапускать site/Conwix/общий Traefik для лечения IAM.

Human operator (MFA) — основной recovery доступ. Bootstrap credential после
инициализации удалён. При потере MFA использовать отдельную SSH-only процедуру
vendor `bootstrap-admin service` в остановленном IAM с backup, временным
protected secret и последующим удалением временного account; не включать
password grant/публичную Console и не редактировать таблицы Keycloak вручную.
Это отдельная security-sensitive операция по согласованному incident scope.

Deployment status pending означает неполный релиз; проверять deployment.json и
предыдущий release.env. Автоматического отката образа после DB migration нет.
