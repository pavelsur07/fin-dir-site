# Network and operator access

Public: auth.vashfindir.ru HTTPS. Разрешены только три точных пути realm projects:

- `/realms/projects/.well-known/openid-configuration`
- `/realms/projects/protocol/openid-connect/token`
- `/realms/projects/protocol/openid-connect/certs`

auth-admin.vashfindir.ru публично возвращает 404. `/admin`, management9000,
health/metrics и PostgreSQL не публикуются. Public ingress принимает тело до16KiB
и ограничивает частоту. Forwarded headers клиента не открывают admin ingress.

Private network10.254.235.0/28: db .2, Keycloak .3, ingress .4, untrusted tools .5,
operator .6. Operator .6 и host bridge .1 (SSH tunnel) разрешены на auth-admin:443. Сеть internal, host ports
отсутствуют. На traefik_web подключён только ingress; вход с неё обслуживает
public entrypoint8081, где административных маршрутов нет. Keycloak доверяет
proxy headers только .4. Управление не использует публичный Admin API.

Человек: SSH на сервер, затем tunnel 127.0.0.1:443 →10.254.235.4:443. Локальная
привязка auth.vashfindir.ru/auth-admin.vashfindir.ru к127.0.0.1 и доверие
`/opt/identity/runtime/production/ca.crt` действуют только в отдельном операторском
browser profile. Fingerprint CA сверить по доверенному SSH. Нужен локальный
доступ к порту443; при конфликте используйте отдельную VM оператора/VPN.
Открыть https://auth-admin.vashfindir.ru/admin/ и войти identity-operator.
Временный пароль хранится root-only в operator_initial_password, в чат не копировать.
Keycloak принудит сменить пароль и подключить TOTP до выдачи admin session.
После завершения удалить временный файл пароля. Зафиксировать выполнение MFA.

CLI на сервере (не требует GNU Make):

```sh
cd /opt/identity/current
python3 scripts/ops_host.py project-plan --environment production --runtime /opt/identity/runtime/production --project documentation
```

Нормальные up/down dev-only; production действия выполняются отдельным release.
CA для private ingress действует365дней; обновить до истечения, сохранить старый
CA на время перехода операторов. Публичные сертификаты продлевает общий Traefik.
