"""Explicit, non-destructive reconciliation of owned M2M clients."""

from pathlib import Path
import argparse, json, os, secrets
from urllib.parse import urlsplit
import jsonschema
from keycloak_admin import KeycloakAdmin, IdentityOperationError, segment

OWNER = "app-service-identity"


def load_manifest(path: Path, environment: str):
    data = json.loads(path.read_text())
    schema = json.loads(
        (
            Path(__file__).resolve().parent.parent
            / "config/schema/project-v1.schema.json"
        ).read_text()
    )
    jsonschema.validate(data, schema)
    if data["environment"] != environment:
        raise IdentityOperationError("Manifest/environment mismatch.")
    audience = data["resourceAudience"]
    if any(char.isspace() for char in audience):
        raise IdentityOperationError("Audience contains whitespace.")
    if audience.startswith("https://"):
        parsed = urlsplit(audience)
        if not parsed.hostname or parsed.username or parsed.password or parsed.fragment:
            raise IdentityOperationError("Exact HTTPS audience required.")
        if environment == "production" and (
            parsed.hostname == "localhost"
            or parsed.hostname.endswith((".test", ".invalid", ".example"))
            or parsed.hostname in {"example.com", "example.org", "example.net"}
        ):
            raise IdentityOperationError("Production audience placeholder refused.")
    ids = [c["clientId"] for c in data["clients"]]
    refs = [c["secretRef"] for c in data["clients"]]
    if len(set(ids)) != len(ids) or len(set(refs)) != len(refs):
        raise IdentityOperationError("Duplicate client ID/secret reference.")
    return data


def client_definition(manifest, client):
    return {
        "clientId": client["clientId"],
        "enabled": True,
        "protocol": "openid-connect",
        "publicClient": False,
        "clientAuthenticatorType": "client-secret",
        "serviceAccountsEnabled": True,
        "standardFlowEnabled": False,
        "implicitFlowEnabled": False,
        "directAccessGrantsEnabled": False,
        "fullScopeAllowed": False,
        "redirectUris": [],
        "webOrigins": [],
        "defaultClientScopes": ["service_account"],
        "optionalClientScopes": client["allowedScopes"],
        "attributes": {
            "identity.managedBy": OWNER,
            "identity.projectId": manifest["projectId"],
            "identity.environment": manifest["environment"],
            "access.token.header.type.rfc9068": "true",
            "access.token.lifespan": "300",
            "client_credentials.use_refresh_token": "false",
        },
        "protocolMappers": [
            {
                "name": "identity-audience",
                "protocol": "openid-connect",
                "protocolMapper": "oidc-audience-mapper",
                "config": {
                    "included.custom.audience": manifest["resourceAudience"],
                    "access.token.claim": "true",
                    "id.token.claim": "false",
                    "introspection.token.claim": "true",
                    "userinfo.token.claim": "false",
                },
            },
            {
                "name": "identity-client-id",
                "protocol": "openid-connect",
                "protocolMapper": "oidc-hardcoded-claim-mapper",
                "config": {
                    "claim.name": "client_id",
                    "claim.value": client["clientId"],
                    "jsonType.label": "String",
                    "access.token.claim": "true",
                    "id.token.claim": "false",
                    "introspection.token.claim": "true",
                    "userinfo.token.claim": "false",
                },
            },
        ],
    }


def owned(data, project, environment):
    attrs = data.get("attributes", {})
    return (
        attrs.get("identity.managedBy") == OWNER
        and attrs.get("identity.projectId") == project
        and attrs.get("identity.environment") == environment
    )


def reconcile(
    api: KeycloakAdmin, manifest, secret_dir: Path, apply=False, realm="projects"
):
    if realm == "master" or not realm.replace("-", "").isalnum():
        raise IdentityOperationError("Application realm required.")
    root = "/admin/realms/" + segment(realm)
    scopes = api.request("GET", root + "/client-scopes")
    # Preflight every ownership conflict before any mutation.
    entries = []
    for client in manifest["clients"]:
        desired = client_definition(manifest, client)
        candidates = api.request(
            "GET", root + "/clients?clientId=" + segment(client["clientId"])
        )
        existing = next(
            (c for c in candidates if c["clientId"] == client["clientId"]), None
        )
        if existing and not owned(
            existing, manifest["projectId"], manifest["environment"]
        ):
            raise IdentityOperationError("Refusing to adopt an unowned client.")
        for scope in client["allowedScopes"]:
            found = next((s for s in scopes if s["name"] == scope), None)
            if found and not owned(
                found, manifest["projectId"], manifest["environment"]
            ):
                raise IdentityOperationError("Scope ownership conflict.")
        mappings = (
            api.request(
                "GET", root + "/clients/" + existing["id"] + "/protocol-mappers/models"
            )
            if existing
            else []
        )
        if any(
            m["name"] not in [x["name"] for x in desired["protocolMappers"]]
            for m in mappings
        ):
            raise IdentityOperationError("Unexpected mapper on an owned M2M client.")
        drift = False
        if existing:
            for key, value in desired.items():
                if key == "protocolMappers":
                    drift = drift or any(
                        not any(
                            all(m.get(k) == v for k, v in mapper.items())
                            for m in mappings
                        )
                        for mapper in value
                    )
                elif key == "attributes":
                    drift = drift or any(
                        existing.get("attributes", {}).get(k) != v
                        for k, v in value.items()
                    )
                elif key in ("defaultClientScopes", "optionalClientScopes"):
                    drift = drift or set(existing.get(key, [])) != set(value)
                else:
                    drift = drift or existing.get(key) != value
        entries.append((client, desired, existing, drift, mappings))
    summary = [
        {
            "clientId": c["clientId"],
            "action": ("create" if e is None else "update" if d else "unchanged"),
        }
        for c, _, e, d, _ in entries
    ]
    if not apply:
        return summary
    secret_dir.mkdir(parents=True, exist_ok=True, mode=0o700)
    if secret_dir.is_symlink():
        raise IdentityOperationError("Secret directory cannot be a symlink.")
    for client, desired, existing, drift, mappings in entries:
        path = secret_dir / client["secretRef"]
        pending = secret_dir / (client["secretRef"] + ".pending.json")
        reservation = {
            "project": manifest["projectId"],
            "environment": manifest["environment"],
            "client": client["clientId"],
            "audience": manifest["resourceAudience"],
        }
        if existing is None:
            if path.exists():
                if (
                    path.is_symlink()
                    or not pending.exists()
                    or json.loads(pending.read_text()) != reservation
                ):
                    raise IdentityOperationError(
                        "Existing secret has no matching pending operation."
                    )
                secret = path.read_text()
            else:
                secret = secrets.token_urlsafe(40)
                fd = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
                with os.fdopen(fd, "w") as output:
                    output.write(secret)
                pending.write_text(json.dumps(reservation))
        elif path.is_symlink() or not path.is_file():
            raise IdentityOperationError(
                "Protected client credential unavailable; explicit rotation required."
            )
        if existing is not None and not drift:
            continue
        # All client scopes are declared; opaque defaults are not inherited.
        for scope in client["allowedScopes"]:
            found = next((s for s in scopes if s["name"] == scope), None)
            if not found:
                api.request(
                    "POST",
                    root + "/client-scopes",
                    {
                        "name": scope,
                        "protocol": "openid-connect",
                        "attributes": {
                            "include.in.token.scope": "true",
                            "identity.managedBy": OWNER,
                            "identity.projectId": manifest["projectId"],
                            "identity.environment": manifest["environment"],
                        },
                    },
                )
                scopes = api.request("GET", root + "/client-scopes")
        if existing is None:
            desired["secret"] = secret
            api.request("POST", root + "/clients", desired)
            existing = next(
                c
                for c in api.request(
                    "GET", root + "/clients?clientId=" + segment(client["clientId"])
                )
                if c["clientId"] == client["clientId"]
            )
        elif drift:
            update = {
                k: v
                for k, v in desired.items()
                if k
                not in (
                    "protocolMappers",
                    "defaultClientScopes",
                    "optionalClientScopes",
                )
            }
            update["attributes"] = {
                **existing.get("attributes", {}),
                **desired["attributes"],
            }
            api.request("PUT", root + "/clients/" + existing["id"], update)
        client_path = root + "/clients/" + existing["id"]
        actual = api.request("GET", client_path + "/protocol-mappers/models")
        for mapper in desired["protocolMappers"]:
            current = next((m for m in actual if m["name"] == mapper["name"]), None)
            if current is None:
                api.request("POST", client_path + "/protocol-mappers/models", mapper)
            elif any(current.get(k) != v for k, v in mapper.items()):
                api.request(
                    "PUT",
                    client_path + "/protocol-mappers/models/" + current["id"],
                    mapper,
                )
        for kind, wanted in [
            ("default", ["service_account"]),
            ("optional", client["allowedScopes"]),
        ]:
            endpoint = client_path + "/" + kind + "-client-scopes"
            assigned = api.request("GET", endpoint)
            for current in assigned:
                if current["name"] not in wanted:
                    api.request("DELETE", endpoint + "/" + current["id"])
            for name in wanted:
                found = next((s for s in scopes if s["name"] == name), None)
                if found is None:
                    raise IdentityOperationError(
                        "Required built-in client scope missing."
                    )
                if not any(a["id"] == found["id"] for a in assigned):
                    api.request("PUT", endpoint + "/" + found["id"])
        if pending.exists():
            pending.unlink()
    return summary


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("operation", choices=["plan", "apply"])
    parser.add_argument("--manifest", type=Path, required=True)
    parser.add_argument(
        "--environment",
        choices=["dev", "smoke", "staging", "production"],
        required=True,
    )
    parser.add_argument("--secret-dir", type=Path, required=True)
    args = parser.parse_args()
    data = load_manifest(args.manifest, args.environment)
    api = KeycloakAdmin(
        os.environ["IDENTITY_PUBLIC_URL"],
        os.environ["IDENTITY_ADMIN_URL"],
        os.environ.get("IDENTITY_CA"),
    )
    api.login(
        os.environ.get("IDENTITY_ADMIN_CLIENT", "identity-provisioner"),
        Path(os.environ["IDENTITY_ADMIN_SECRET_FILE"]),
        os.environ.get("IDENTITY_ADMIN_REALM", "projects"),
    )
    print(json.dumps(reconcile(api, data, args.secret_dir, args.operation == "apply")))


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        print(
            "Identity provisioning failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
