"""Generate real issuer credentials for the existing SDK HTTP contract check."""

from pathlib import Path
import json, os
from keycloak_admin import KeycloakAdmin

api = KeycloakAdmin(
    os.environ["IDENTITY_PUBLIC_URL"],
    os.environ["IDENTITY_ADMIN_URL"],
    os.environ["IDENTITY_CA"],
)
try:
    secret = Path("/runtime/clients/docs-mcp-reader-smoke").read_text().strip()

    def issue(scope):
        data = {
            "grant_type": "client_credentials",
            "client_id": "docs-mcp-reader-smoke",
            "client_secret": secret,
        }
        if scope:
            data["scope"] = scope
        response = api.http.post(
            api.public_url + "/realms/projects/protocol/openid-connect/token", data=data
        )
        if response.status_code != 200:
            raise RuntimeError("Real issuer contract token exchange failed.")
        return response.json()["access_token"]

    directory = Path("/runtime/http-contract")
    directory.mkdir(mode=0o700, exist_ok=True)
    tokens = {
        "client_id": "docs-mcp-reader-smoke",
        "valid": issue("docs:read"),
        "no_scope": issue(""),
        "wrong_audience": Path("/runtime/consumer-other-token").read_text().strip(),
    }
    path = directory / "tokens.json"
    path.write_text(json.dumps(tokens))
    path.chmod(0o600)
    print("Real issuer HTTP contract credentials prepared; values not printed.")
except Exception as error:
    print(
        "HTTP contract credentials failed: " + type(error).__name__,
        file=__import__("sys").stderr,
    )
    raise SystemExit(1)
