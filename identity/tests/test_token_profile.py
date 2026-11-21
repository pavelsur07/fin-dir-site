from pathlib import Path
import json, time
import jwt
from provision import load_manifest, reconcile


def manifest():
    result = json.loads(
        Path("/identity/config/projects/documentation.json").read_text()
    )
    result["environment"] = "smoke"
    result["clients"] = [result["clients"][0]]
    result["clients"][0]["clientId"] = "docs-mcp-reader-smoke"
    result["clients"][0]["secretRef"] = "docs-mcp-reader-smoke"
    return result


def issue(
    api, client_id, secret, scope="docs:read", resource="https://vashfindir.ru/mcp/docs"
):
    response = api.http.post(
        api.public_url + "/realms/projects/protocol/openid-connect/token",
        data={
            "grant_type": "client_credentials",
            "client_id": client_id,
            "client_secret": secret,
            "scope": scope,
            "resource": resource,
        },
    )
    if response.status_code != 200:
        raise RuntimeError(f"Test token exchange failed ({response.status_code}).")
    return response.json()["access_token"]


def test_actual_keycloak_profile_and_repeat_apply(api):
    data = manifest()
    first = reconcile(api, data, Path("/runtime/clients"), True)
    secret = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    second = reconcile(api, data, Path("/runtime/clients"), True)
    assert second[0]["action"] == "unchanged"
    unchanged = secret == Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    assert unchanged
    token = issue(api, "docs-mcp-reader-smoke", secret)
    headers = jwt.get_unverified_header(token)
    assert headers["typ"] == "at+jwt"
    assert headers["alg"] == "RS256"
    jwks = api.http.get(
        api.public_url + "/realms/projects/protocol/openid-connect/certs"
    ).json()
    key = jwt.PyJWK.from_dict(
        next(k for k in jwks["keys"] if k["kid"] == headers["kid"])
    ).key
    claims = jwt.decode(
        token,
        key,
        algorithms=["RS256"],
        issuer=api.public_url + "/realms/projects",
        audience="https://vashfindir.ru/mcp/docs",
        options={"require": ["exp", "iat", "client_id", "scope"]},
    )
    assert claims["client_id"] == "docs-mcp-reader-smoke"
    assert claims["azp"] == claims["client_id"]
    assert "docs:read" in claims["scope"].split()
    assert claims["exp"] - claims["iat"] <= 300
    assert claims["aud"] in (
        "https://vashfindir.ru/mcp/docs",
        ["https://vashfindir.ru/mcp/docs"],
    )
    Path("/runtime/consumer-token").write_text(token)
    Path("/runtime/consumer-token").chmod(0o644)
    print("Real Keycloak access-token contract accepted (token not printed).")
