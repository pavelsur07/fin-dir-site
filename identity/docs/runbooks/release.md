# Release

1. PR: site checks + always-running identity-gate. IAM/consumer выполняются при
изменении identity, workflows, Documentation security/config/dependencies/images,
Makefile/AGENTS/PATTERNS. Unrelated diff явно skip, required failure/cancelled
не принимаются. Read-only independent security review обязателен перед merge.
2. Merge после CI. `Deploy Identity` запускается явно по current master fullSHA;
workflow требует защищённый master ref, identity-production environment и полную
проверку именно этого SHA, даже если PR impact пропускал IAM.
3. Выпуск публикует собственные digest-pinned Keycloak/tools images и пакет только
identity из git tree. Перед распаковкой проверяются SHA/manifest и все entries,
symlinks/выход за каталог/secret files запрещены. Никакого shared site checkout.
4. Host receiver принимает только release-протокол с current masterSHA, fixed
image namespaces, checksum и bounded stdin. Файлы, Git blob hashes, modes и полный набор путей
пакета сверяются с canonical GitHub tree точного commit; image digest/OCI labels —
с независимо полученным GHCR sha-<revision> tag. Package manifest не является
источником доверия. Registry writers и Git writers считаются доверенными. Отдельный SSH key forced-command,
restrict/no forwarding/PTY, только identity-production environment protected branch.
Это ограничивает интерфейс ключа; root+Docker всё ещё привилегированны на общем
хосте. Не считать это VM/container security boundary от злонамеренного release.
5. `/opt/identity/releases/<sha>` + runtime вне artifact. Сначала backup существующей
БД. После старта — private bootstrap/provision, public system-CA discovery/JWKS/
real token/private-path smoke, encrypted backup. Только затем current symlink.
При ошибке status pending, автоматического schema rollback нет.
6. Записать revision/digests/CI/deploy URL, доступность обоих сайтов, отсутствие OOM,
backup/restore и внешнюю копию. Operator обязан завершить MFA через private access.

Compose standalone для Identity хранится в /opt/identity/bin и не заменяет
системный legacy docker-compose сайта. Установочный artifact Docker Compose5.8.0
linux-x86_64 проверяется по официальному SHA256
b8840f5008c447f41fce0a57ae3adeab42c89b1eb6e6012524bcabbfb4ffbaf6.
Make на production не нужен; host operations используют stdlib Python3.

Локальное создание пакета: `make identity-package IDENTITY_RELEASE=<sha>
IDENTITY_PACKAGE=/tmp/identity-<sha>.tar.gz`. После распаковки работает собственный
Makefile/scripts без монорепо. Consumer source требуется только для CI.
