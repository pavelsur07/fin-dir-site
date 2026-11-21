# Identity и Documentation MCP: выпуск 2026-11-21

Identity и защищённый HTTP MCP работают на текущем сервере сайта176.124.218.113.
Публичные адреса:

- Issuer: https://auth.vashfindir.ru/realms/projects
- Token endpoint: https://auth.vashfindir.ru/realms/projects/protocol/openid-connect/token
- MCP endpoint/audience: https://vashfindir.ru/mcp/docs
- Resource metadata: https://vashfindir.ru/.well-known/oauth-protected-resource/mcp/docs

Профиль: registered M2M, client_credentials, RS256/at+jwt/client_id, docs:read,
один audience на client и TTL≤300с. Клиент docs-mcp-reader-prod имеет local grant
в Symfony. Секрет хранится на сервере в protected runtime, не в git/образах/чате.
Текущий Admin сайта сохраняет свою Symfony session/CSRF authentication.

## Выпуски и проверка

| Часть | Подтверждённая версия / evidence |
|---|---|
| HTTP MCP foundation | PR255, merge49ca957495792dd2a683343f5babb43c41b5e08b, [deploy44900300808](https://github.com/pavelsur07/fin-dir-site/actions/runs/44900300808) |
| Identity | PR256 и recovery hotfixPR258; production5d4c554df2b4adf2bbbe25da82b80b04398e4a53, [deploy44904145402](https://github.com/pavelsur07/fin-dir-site/actions/runs/44904145402) |
| MCP integration | PR257, merge45da3376319c8d6029ea3b6a0b030e3872665ab8; [activation deploy44905798162](https://github.com/pavelsur07/fin-dir-site/actions/runs/44905798162) |
| Application checks | 443tests/3686assertions; lint, composer audit, static analysis, architecture checks и production-artifact smoke passed |
| Identity checks | 55tests после recovery hotfix; реальные tokens/provisioning/ownership/isolation/rotation/ingress/outage/backup/restore и actual HTTP SDK |
| Production acceptance | PHP mcp/sdk0.10.0; protocol2025-11-25 и2026-07-28; ровно6readtools, draft-search denied, no cookies/MCP sessions |
| Revocation | Выделенный временный smoke grant: обе текущие FPM200 до revoke и403 после. Smoke grant оставлен disabled, проверочный private proxy удалён |
| Recovery | Реальный encrypted production backup скопирован off-host; checksum/tag проверены, отдельный internal/tmpfs стенд восстановлен за38.1с; issuer/signing keys/client credentials и настоящий Symfony verifier подтверждены |

Точный Identity config/image revision и digests хранятся также в
`/opt/identity/runtime/production/deployment.json`, статус healthy. Все три
Identity контейнера healthy. Public issuer discovery200/TLS0, public admin404/TLS0.
MCP metadata200, запрос без bearer401 с WWW-Authenticate и без session/redirect.

## Исправления по проверкам и ревью

Независимое read-only review выявило и помогло закрыть:

- независимую привязку пакета к canonical Git tree и образов к revision-tagged
  GHCR digest/OCI labels; archive self-manifest не является источником доверия;
- общий лимит распаковки16MiB до tar parser, включая metadata headers;
- безопасный retry bootstrap с проверкой human admin role/ownership marker;
- доступ SSH tunnel с host bridge при сохранении запрета внешней админки.

GitHub runner UID1001 учтён без открытия прав private fixtures. Ingress ограничен
192MB с direct entrypoint/GOMEMLIMIT; это устранило startup page-reclaim при64MB.
Первый rollout остановился до создания БД из-за старых global DockerHub credentials;
отдельный Identity Docker config переносит только GHCR auth, global config сохранён.
Повторный первый запуск без данных разрешён, existing data требует backup/recovery.
Пустой repository flag не должен включать HTTP: явный opt-in только значением1.

## Эксплуатация и ограничения

На shared host2GB используются отдельные state/DB/network/releases/secrets.
Измеренная дополнительная память IAM около536MB; лимиты KC640MB (heap384), PG192MB,
ingress192MB. Website/Conwix/общий Traefik не пересоздаются Identity release.
Compose5.8.0 установлен только в `/opt/identity/bin`, global Compose не заменён.

Admin IP/VPN владелец не указал: auth-admin публично закрыт. Human operator
identity-operator должен сменить временный пароль и подключить TOTP через private
SSH access; до выполнения required actions admin login не завершается.
См. [доступ оператора](../../identity/docs/runbooks/network.md).

Off-host копия и recovery key хранятся раздельно в private directories текущей
операторской рабочей среды `identity/.runtime/production-recovery-20261121/`.
Автоматическая постоянная внешняя репликация/retention пока не настроены: для неё
нужен выбранный защищённый storage. RPO24ч/RTO4ч — целевые ориентиры, не SLA.
Текущий экземпляр без HA. Registered M2M не означает готовый пользовательский SSO
или универсальную совместимость с каждым desktop/chat MCP-клиентом.

Реальные Finance/Analytics интеграции не выполнялись. Переиспользование проверено
на втором synthetic API с отдельными audience/scopes/secrets без изменения IAM core.
