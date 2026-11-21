# Rotation

Client secret: `make identity-rotate-client IDENTITY_ENV=production
IDENTITY_PROJECT=documentation IDENTITY_CLIENT=docs-mcp-reader-prod
IDENTITY_RUNTIME_DIR=/opt/identity/runtime/production`.
Команда проверяет ownership и старый credential, сохраняет новый в protected
write-ahead file, меняет secret, проверяет new200/old401, атомарно публикует файл.
При ошибке проверки возвращает старый secret. Interrupted .rotation-pending
запрещает повторную ротацию до ручного сопоставления состояния по SSH.
Потребителю передать новый secret защищённым каналом. Запланировать короткий
перерыв client authentication; dual-secret overlap в MVP не обещается.

Secret rotation не отзывает уже выданные JWT: они действуют до300с+skew.
Для MCP немедленно отключить локальный grant в Symfony, затем client в IAM.
Проверять обе FPM replicas; offlineJWT нельзя объявлять мгновенно отозванным.

Signing key: human operator с MFA создаёт новый rsa-generated provider RS256,
priority выше активного. Старый provider оставляется enabled, public key остаётся
в JWKS. Проверить разные kid и приём обоих tokens настоящими consumers.
После TTL300с+skew30с+consumer JWKS cache (600с) и проверки остальных API отдельно
сделать старый provider inactive; удаление — отдельная операция после recovery
backup. CI проверяет rotation, публикацию обоих ключей и обе подписи.
Provisioner не имеет права менять signing keys.

При компрометации отключить local grants, запретить client, обновить секреты/keys,
учесть cached JWKS и tokens. Проверить аудит. Не ждать обычный overlap, если
скомпрометирован signing key; каждый consumer должен иметь аварийный deny path.
