# Backup / restore

`make identity-backup IDENTITY_ENV=production IDENTITY_RUNTIME_DIR=/opt/identity/runtime/production`
или `python3 scripts/ops_host.py backup --environment production --runtime ...`.
Backup: pg_dump custom, проверка pg_restore--list, authenticated AES256GCM archive,
SHA256 и JSON manifest с точным release/vendor images. В архив входят БД, runtime
passwords, client credentials и private TLS materials; ключ шифрования backup_key
хранится отдельно. Plaintext dump удаляется в finally. Parent directory0700,
архив/manifest/key0600. В production не использовать realm export как backup.

Перед каждым upgrade — backup текущей БД, после успешного выпуска — новый backup.
CI реально decrypt/restore на отдельную БД, проверяет scoped admin, выдачу токена
и настоящий Symfony verifier; повреждённые checksum/tag отвергаются. Restore
никогда не направляется в production автоматически.

Порядок аварийного восстановления: создать изолированный экземпляр с той же
версией vendor image; проверить manifest/checksum/authenticated encryption;
восстановить dump и protected credentials; сохранить прежний issuer/подписи;
проверить token flow/consumer; отдельно согласовать DNS/cutover. Учитывать, что
старый backup может вернуть отозванные clients/keys: перед выходом в сеть
повторить актуальные revocations и сменить скомпрометированные credentials.
Изолированный стенд с production keys не должен иметь egress к приложениям.

Цель эксплуатации: RPO24ч/RTO4ч, не HA/SLA. Измеренный local recovery proof121.7с
включает bootstrap/tests/backup/restore; это не измерение аварии production.
Daily backup и retention7дней на хосте,30дней вне хоста — рекомендуемые настройки.
Off-host archive и backup_key передаются раздельно по SSH; не отправлять их в
чат, git или общедоступные CI artifacts. Для постоянной off-host репликации
требуется выбранное владельцем защищённое хранилище. Первая внешняя копия и её
проверка фиксируются в release report; не заявлять о настроенной ежедневной
репликации, если её нет. Старые backup keys сохранять для старых архивов.

Проверка конкретного внешнего encrypted backup без обращения к production:

```sh
make identity-restore-check IDENTITY_BACKUP_MANIFEST=/protected/backup.json IDENTITY_BACKUP_KEY_FILE=/protected/backup_key IDENTITY_CONSUMER_IMAGE=vf-check/php-cli:8.4.27
```

Команда допускает только identity-restore с internal network/tmpfsDB, проверяет
manifest/checksum/tag и exact vendor versions, сохраняет исходный issuer и signing
keys. Runtime/containers удаляются после проверки настоящим Symfony verifier.
Восстановление в произвольный target/production не поддерживается этим CLI.
