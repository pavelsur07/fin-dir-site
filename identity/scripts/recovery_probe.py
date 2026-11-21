from pathlib import Path
import argparse, os
from keycloak_admin import KeycloakAdmin

parser = argparse.ArgumentParser()
parser.add_argument("--client", default="docs-mcp-reader-smoke")
args = parser.parse_args()
try:
    api = KeycloakAdmin(
        os.environ["IDENTITY_PUBLIC_URL"],
        os.environ["IDENTITY_ADMIN_URL"],
        os.environ["IDENTITY_CA"],
    )
    api.wait_ready()
    api.login("identity-provisioner", Path("/runtime/provisioner_secret"), "projects")
    api.request("GET", "/admin/realms/projects/clients")
    response = api.http.post(
        api.public_url + "/realms/projects/protocol/openid-connect/token",
        data={
            "grant_type": "client_credentials",
            "client_id": args.client,
            "client_secret": (Path("/runtime/clients") / args.client)
            .read_text()
            .strip(),
            "scope": "docs:read",
            "resource": "https://vashfindir.ru/mcp/docs",
        },
    )
    if response.status_code != 200:
        raise RuntimeError("Restored client credential refused.")
    Path("/runtime/consumer-token").write_text(response.json()["access_token"])
    Path("/runtime/consumer-token").chmod(0o644)
    print("Restored database: scoped management and real token issuance passed.")
except Exception as error:
    print(
        "Recovery probe failed: " + type(error).__name__, file=__import__("sys").stderr
    )
    raise SystemExit(1)
