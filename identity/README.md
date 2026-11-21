# Identity

Самостоятельный Keycloak в монорепо. Первый потребитель — Documentation MCP;
готовый профиль — registered M2M / `client_credentials`, `RS256`, `at+jwt`,
`client_id`, один точный `aud`, scope `docs:read`, TTL не более 300 секунд.
Бизнес-права и немедленный отзыв client grant остаются в Symfony.

Из корня репозитория:

```sh
make identity-build
make php-production-build
make identity-ci IDENTITY_CONSUMER_IMAGE=vf-check/php-cli:8.4.27
```

Проверка создаёт и удаляет только `identity-smoke`: реальный issuer, две аудитории,
rotation, приватный Admin API, отказ при outage, encrypted backup и реальное восстановление.
Docker должен поддерживать Compose `!override`. Тестовая сеть `10.254.236.0/28`
зарезервирована этим компонентом; конфликт завершается ошибкой, сети других проектов не удаляются.

Dev: `make identity-up`, `make identity-bootstrap`, `make identity-down`.
Dev endpoints не публикуются на хост. TLS и credentials находятся в
`identity/.runtime/dev/`, parent mode 0700, в git не попадают. Для создания своего
проекта используйте manifest с `environment=dev`; production manifest нельзя
применить к dev по ошибке.

```sh
make identity-project-plan IDENTITY_ENV=dev IDENTITY_PROJECT=example
make identity-project-apply IDENTITY_ENV=dev IDENTITY_PROJECT=example
```

Production — отдельный Compose project `identity-production` на текущем сервере
сайта, `/opt/identity/releases/<sha>`, secrets `/opt/identity/runtime/production`.
Пакет содержит только `identity/` из точного git tree. Site, Conwix и общий
Traefik не пересоздаются. Вендорная Console не меняет текущий вход в Admin сайта.
Все эксплуатационные команды реализованы в компонентном Makefile/его scripts;
root Makefile предоставляет wrappers.

См. [решения](docs/adr/0001-registered-m2m-profile.md),
[сеть](docs/runbooks/network.md), [выпуск](docs/runbooks/release.md),
[восстановление](docs/runbooks/backup-restore.md),
[ротация](docs/runbooks/rotation.md), [подключение API](docs/integrations/new-project.md).
