# New API

Владелец API задаёт exact audience, нужные scopes, клиента каждого окружения,
место protected credentials, callback/revocation policy и ответственного.
Для M2M скопировать config/projects/example.json, указать собственный projectId,
environment, audience, clientId/secretRef. Literal secrets в manifest запрещены.
Plan только читает; apply меняет лишь помеченные owned resources, не присваивает
совпавшие чужие имена и не ротирует secret при повторе. Сначала plan, затем apply.

Потребитель получает только свой client secret, issuer, audience и scopes.
Ни Admin API credential, ни DB IAM access ему не нужны. Валидатор проверяет
RS256/at+jwt, exact issuer/aud, TTL/exp/iat, client_id и scopes. Бизнес-права на
объекты и emergency revoke остаются в приложении. Токен API A не принимается
API B; это доказано на synthetic втором API без изменения provisioning core.

Production TLS проверяется системным CA. Запрашивать новый access token после
истечения, не сохранять его в browser storage/логи и не использовать password
grant. Для обычного пользовательского входа нужен отдельный OIDC/PKCE проект:
state/nonce, (iss,sub), server sessions, MFA/recovery и модель пользователей.
Реальные Finance/Analytics интеграции и HA не входят в этот bootstrap.
