# Documentation MCP

Issuer: https://auth.vashfindir.ru/realms/projects.
Token endpoint: issuer + `/protocol/openid-connect/token`.
MCP audience и endpoint: https://vashfindir.ru/mcp/docs.
Grant client_credentials, scope docs:read, client docs-mcp-reader-prod.
Точный endpoint брать из проверенного discovery; отправлять secret только по TLS.
Хранение: secret manager внешнего сервиса, не chat/git/browser/localStorage.

После получения token обращаться к MCP с Authorization Bearer и MCP SDK,
поддерживающим Streamable HTTP и service client credentials. Native
resource-indicators/CIMD выключены, один client уже закреплён за одним audience.
Конкретные desktop/chat клиенты требуют отдельной проверки совместимости.
Тестовый consumer — настоящий PHP mcp/sdk0.10.0, не обещание поддержки всех клиентов.

Интеграция сайта отдельным PR/release: одинаковый DOCS_MCP_OAUTH_ISSUER у FPM/CLI,
сначала HTTP flag0, штатный migration с verified backup, local client grant;
после проверок включить HTTP. Ровно шесть published read tools, без drafts/writes,
без cookie/session. Stdio/Admin с sessions/CSRF работают независимо.
Отключение local grant немедленно отсекает уже выданный token на обеих FPM;
один лишь client disable/secret rotation в IAM не отзывает JWT мгновенно.

Production issuer deployment сам по себе не означает включённый HTTP MCP.
Фактические SHA, smoke и включение фиксируются в release report.

Первый production выпуск проверен2026-11-21: public discovery/TLS, оба протокола,
ровно шесть read tools, запрет draft search, отсутствие сессий, немедленный revoke
на двух FPM. Рабочий client secret находится только на сервере в protected
`/opt/identity/runtime/production/clients/docs-mcp-reader-prod`; передавать
потребителю защищённым каналом. API требует local grant именно для этого issuer.
Включение website deployment: repository variable VF_DOCS_MCP_HTTP_ENABLED=1.
Глобальное выключение: значение0 и обычный redeploy; emergency per-client revoke
в Symfony действует немедленно и сохраняется при последующих deploy.
