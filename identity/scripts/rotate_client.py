"""Explicit owned-client secret rotation; write-ahead delivery, verify, then commit."""

from pathlib import Path
import argparse, json, os, secrets
from keycloak_admin import KeycloakAdmin, IdentityOperationError, segment
from provision import load_manifest, owned


def exchange(api, client, secret):
    return api.http.post(
        api.public_url + "/realms/projects/protocol/openid-connect/token",
        data={
            "grant_type": "client_credentials",
            "client_id": client["clientId"],
            "client_secret": secret,
            "scope": " ".join(client["allowedScopes"]),
        },
    )


def rotate(api, manifest, client_id, secret_dir):
    matches = [c for c in manifest["clients"] if c["clientId"] == client_id]
    if len(matches) != 1:
        raise IdentityOperationError("Client must be declared in this manifest.")
    client = matches[0]
    actual = api.request(
        "GET", "/admin/realms/projects/clients?clientId=" + segment(client_id)
    )
    actual = next((c for c in actual if c["clientId"] == client_id), None)
    if actual is None or not owned(
        actual, manifest["projectId"], manifest["environment"]
    ):
        raise IdentityOperationError("Rotation requires an existing owned client.")
    path = secret_dir / client["secretRef"]
    if path.is_symlink() or not path.is_file() or secret_dir.is_symlink():
        raise IdentityOperationError("Protected existing secret required.")
    previous = path.read_text().strip()
    if exchange(api, client, previous).status_code != 200:
        raise IdentityOperationError(
            "Existing credential check failed; refusing rotation."
        )
    pending = secret_dir / (client["secretRef"] + ".rotation-pending")
    # Never silently start another rotation over a partially completed operation.
    fd = os.open(pending, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    new = secrets.token_urlsafe(40)
    with os.fdopen(fd, "w") as output:
        output.write(new)
        output.flush()
        os.fsync(output.fileno())
    endpoint = "/admin/realms/projects/clients/" + actual["id"]
    try:
        api.request("PUT", endpoint, {"secret": new})
        if exchange(api, client, new).status_code != 200:
            raise IdentityOperationError("New credential verification failed.")
        if exchange(api, client, previous).status_code not in (400, 401):
            raise IdentityOperationError(
                "Old credential still accepted; revoke overlap explicitly."
            )
        os.replace(pending, path)
    except Exception:
        try:
            api.request("PUT", endpoint, {"secret": previous})
            if exchange(api, client, previous).status_code != 200:
                raise RuntimeError()
            pending.unlink()
        except Exception:
            raise IdentityOperationError(
                "Rotation interrupted; preserve both protected credentials and recover explicitly."
            ) from None
        raise IdentityOperationError(
            "Rotation failed; previous credential restored."
        ) from None
    return {"clientId": client_id, "rotated": True, "oldCredentialRejected": True}


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", required=True, type=Path)
    parser.add_argument("--environment", required=True)
    parser.add_argument("--client", required=True)
    args = parser.parse_args()
    try:
        data = load_manifest(args.manifest, args.environment)
        api = KeycloakAdmin(
            os.environ["IDENTITY_PUBLIC_URL"],
            os.environ["IDENTITY_ADMIN_URL"],
            os.environ.get("IDENTITY_CA"),
        )
        api.login(
            "identity-provisioner", Path("/runtime/provisioner_secret"), "projects"
        )
        print(json.dumps(rotate(api, data, args.client, Path("/runtime/clients"))))
    except Exception as error:
        print(
            "Client rotation failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
