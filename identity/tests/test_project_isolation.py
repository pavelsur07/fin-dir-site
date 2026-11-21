from pathlib import Path
import json
import jwt, pytest
from provision import reconcile
from test_token_profile import manifest, issue


def test_other_resource_cannot_become_documentation_audience(api):
    data = json.loads(Path("/identity/config/projects/example.json").read_text())
    data["environment"] = "smoke"
    reconcile(api, data, Path("/runtime/clients"), True)
    token = issue(
        api,
        "isolation-proof-worker",
        Path("/runtime/clients/isolation-proof-worker").read_text(),
        "proof:read",
        "https://vashfindir.ru/mcp/docs",
    )
    header = jwt.get_unverified_header(token)
    jwks = api.http.get(
        api.public_url + "/realms/projects/protocol/openid-connect/certs"
    ).json()
    key = jwt.PyJWK.from_dict(
        next(k for k in jwks["keys"] if k["kid"] == header["kid"])
    ).key
    with pytest.raises(jwt.InvalidAudienceError):
        jwt.decode(
            token,
            key,
            algorithms=["RS256"],
            issuer=api.public_url + "/realms/projects",
            audience="https://vashfindir.ru/mcp/docs",
        )
    Path("/runtime/consumer-other-token").write_text(token)
    Path("/runtime/consumer-other-token").chmod(0o644)


def test_extra_scope_and_password_grant_are_rejected(api):
    reconcile(api, manifest(), Path("/runtime/clients"), True)
    secret = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    for grant, scope in [("client_credentials", "admin"), ("password", "docs:read")]:
        response = api.http.post(
            api.public_url + "/realms/projects/protocol/openid-connect/token",
            data={
                "grant_type": grant,
                "client_id": "docs-mcp-reader-smoke",
                "client_secret": secret,
                "scope": scope,
            },
        )
        assert response.status_code == 400


def test_disabled_client_does_not_issue_tokens(api):
    data = manifest()
    reconcile(api, data, Path("/runtime/clients"), True)
    client = api.request(
        "GET", "/admin/realms/projects/clients?clientId=docs-mcp-reader-smoke"
    )[0]
    api.request(
        "PUT", "/admin/realms/projects/clients/" + client["id"], {"enabled": False}
    )
    try:
        response = api.http.post(
            api.public_url + "/realms/projects/protocol/openid-connect/token",
            data={
                "grant_type": "client_credentials",
                "client_id": "docs-mcp-reader-smoke",
                "client_secret": Path(
                    "/runtime/clients/docs-mcp-reader-smoke"
                ).read_text(),
            },
        )
        assert response.status_code in (400, 401)
    finally:
        reconcile(api, data, Path("/runtime/clients"), True)
