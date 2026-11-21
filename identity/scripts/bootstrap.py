"""One-time least-privilege provisioning identity + MFA-required human operator."""

from pathlib import Path
import os, secrets, json
from keycloak_admin import KeycloakAdmin, IdentityOperationError


def protected_secret(path: Path):
    path.parent.mkdir(parents=True, exist_ok=True, mode=0o700)
    if path.is_symlink():
        raise IdentityOperationError("Secret symlink refused.")
    if not path.exists():
        fd = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
        with os.fdopen(fd, "w") as out:
            out.write(secrets.token_urlsafe(40))
    return path.read_text().strip()


def bootstrap(api, runtime: Path, remove_temporary=True):
    api.login("identity-bootstrap", runtime / "bootstrap_secret")
    realms = api.request("GET", "/admin/realms")
    if not any(r["realm"] == "projects" for r in realms):
        api.request(
            "POST",
            "/admin/realms",
            {
                "realm": "projects",
                "attributes": {"identity.managedBy": "app-service-identity"},
                "enabled": True,
                "registrationAllowed": False,
                "resetPasswordAllowed": False,
                "accessTokenLifespan": 300,
                "eventsEnabled": True,
                "adminEventsEnabled": True,
                "adminEventsDetailsEnabled": False,
                "bruteForceProtected": True,
            },
        )
    root = "/admin/realms/projects"
    realm = api.request("GET", root)
    if realm.get("attributes", {}).get("identity.managedBy") != "app-service-identity":
        raise IdentityOperationError("Existing application realm is not owned.")
    # 26.8.2 restricts mapper administration by default. Delegate only our two
    # mapper types; the automation never receives manage-realm or signing keys.
    attrs = {
        **realm.get("attributes", {}),
        "allowedProtocolMapperTypesForClientAdministrators": "oidc-audience-mapper,oidc-hardcoded-claim-mapper",
    }
    api.request("PUT", root, {"attributes": attrs})
    existing = api.request("GET", root + "/clients?clientId=identity-provisioner")
    if not existing:
        secret = protected_secret(runtime / "provisioner_secret")
        api.request(
            "POST",
            root + "/clients",
            {
                "clientId": "identity-provisioner",
                "protocol": "openid-connect",
                "enabled": True,
                "publicClient": False,
                "serviceAccountsEnabled": True,
                "standardFlowEnabled": False,
                "directAccessGrantsEnabled": False,
                "fullScopeAllowed": False,
                "defaultClientScopes": ["service_account", "roles", "kc-admin-api"],
                "optionalClientScopes": [],
                "secret": secret,
                "attributes": {"identity.managedBy": "identity-bootstrap"},
            },
        )
        existing = api.request("GET", root + "/clients?clientId=identity-provisioner")
    elif (
        existing[0].get("attributes", {}).get("identity.managedBy")
        != "identity-bootstrap"
    ):
        raise IdentityOperationError("Existing provisioning identity is not owned.")
    elif not (runtime / "provisioner_secret").is_file():
        raise IdentityOperationError(
            "Provisioner credential unavailable; recover explicitly."
        )
    provisioner = existing[0]
    manager = next(
        c
        for c in api.request("GET", root + "/clients?clientId=realm-management")
        if c["clientId"] == "realm-management"
    )
    roles = [
        api.request("GET", root + "/clients/" + manager["id"] + "/roles/" + name)
        for name in ["manage-clients", "view-clients", "view-realm"]
    ]
    account = api.request(
        "GET", root + "/clients/" + provisioner["id"] + "/service-account-user"
    )
    api.request(
        "POST",
        root + "/users/" + account["id"] + "/role-mappings/clients/" + manager["id"],
        roles,
    )
    api.request(
        "POST",
        root
        + "/clients/"
        + provisioner["id"]
        + "/scope-mappings/clients/"
        + manager["id"],
        roles,
    )
    # Declare one admin-only managed user attribute. Keycloak's default user
    # profile drops undeclared custom attributes, which would break safe retries.
    profile_path = "/admin/realms/master/users/profile"
    profile = api.request("GET", profile_path)
    marker = next(
        (
            a
            for a in profile.get("attributes", [])
            if a.get("name") == "identity.managedBy"
        ),
        None,
    )
    permissions = {"view": ["admin"], "edit": ["admin"]}
    if marker is None:
        profile.setdefault("attributes", []).append(
            {
                "name": "identity.managedBy",
                "displayName": "Identity provisioning owner",
                "permissions": permissions,
                "multivalued": False,
            }
        )
        api.request("PUT", profile_path, profile)
    elif marker.get("permissions") != permissions:
        raise IdentityOperationError(
            "Human ownership attribute has unexpected permissions."
        )
    # Human admin cannot finish login before password change and MFA enrolment.
    human = api.request(
        "GET", "/admin/realms/master/users?username=identity-operator&exact=true"
    )
    if not human:
        password = protected_secret(runtime / "operator_initial_password")
        api.request(
            "POST",
            "/admin/realms/master/users",
            {
                "username": "identity-operator",
                "enabled": True,
                "requiredActions": ["UPDATE_PASSWORD", "CONFIGURE_TOTP"],
                "attributes": {"identity.managedBy": ["identity-bootstrap"]},
                "credentials": [
                    {"type": "password", "value": password, "temporary": True}
                ],
            },
        )
        human = api.request(
            "GET", "/admin/realms/master/users?username=identity-operator&exact=true"
        )
    elif human[0].get("attributes", {}).get("identity.managedBy") != [
        "identity-bootstrap"
    ]:
        raise IdentityOperationError("Existing human operator is not owned.")
    role_path = "/admin/realms/master/users/" + human[0]["id"] + "/role-mappings/realm"
    admin_role = api.request("GET", "/admin/realms/master/roles/admin")
    if not any(
        role["id"] == admin_role["id"] for role in api.request("GET", role_path)
    ):
        api.request("POST", role_path, [admin_role])
    if not human[0].get("enabled") or not any(
        role["id"] == admin_role["id"] for role in api.request("GET", role_path)
    ):
        raise IdentityOperationError(
            "Human administrator recovery access is not ready."
        )
    # Prove ordinary management works before removing the only bootstrap identity.
    limited = KeycloakAdmin(
        api.public_url, api.admin_url, os.environ.get("IDENTITY_CA")
    )
    limited.login("identity-provisioner", runtime / "provisioner_secret", "projects")
    limited.request("GET", root + "/client-scopes")
    denied = limited.http.get(
        limited.admin_url + "/admin/realms/master/users",
        headers={"Authorization": "Bearer " + limited.token},
    )
    if denied.status_code != 403:
        raise IdentityOperationError("Provisioner escaped its realm boundary.")
    if remove_temporary:
        temporary = next(
            c
            for c in api.request(
                "GET", "/admin/realms/master/clients?clientId=identity-bootstrap"
            )
            if c["clientId"] == "identity-bootstrap"
        )
        api.request("DELETE", "/admin/realms/master/clients/" + temporary["id"])
        (runtime / "bootstrap_secret").write_text("")
    return limited


if __name__ == "__main__":
    try:
        api = KeycloakAdmin(
            os.environ["IDENTITY_PUBLIC_URL"],
            os.environ["IDENTITY_ADMIN_URL"],
            os.environ.get("IDENTITY_CA"),
        )
        api.wait_ready()
        bootstrap(api, Path("/runtime"))
        print(
            "Bootstrap completed; temporary admin removed, human MFA required, provisioner realm-scoped."
        )
    except Exception as error:
        print(
            "Bootstrap failed: " + type(error).__name__, file=__import__("sys").stderr
        )
        raise SystemExit(1)
