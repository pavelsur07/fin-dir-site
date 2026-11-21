from pathlib import Path
import jwt, pytest
from provision import reconcile, IdentityOperationError
from rotate_client import rotate, exchange
from test_token_profile import manifest, issue


def test_owned_client_rotation_rejects_old_secret(api):
    data = manifest()
    reconcile(api, data, Path("/runtime/clients"), True)
    old = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    rotate(api, data, "docs-mcp-reader-smoke", Path("/runtime/clients"))
    new = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    changed = old != new
    assert changed
    assert exchange(api, data["clients"][0], old).status_code in (400, 401)
    assert exchange(api, data["clients"][0], new).status_code == 200


def test_failed_rotation_restores_previous_credential(api):
    data = manifest()
    client = data["clients"][0]
    old = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()

    class FailedVerification:
        public_url = api.public_url

        class Http:
            calls = 0

            def post(self, *args, **kwargs):
                self.calls += 1
                if self.calls == 2:
                    raise RuntimeError("Synthetic verification failure.")
                return api.http.post(*args, **kwargs)

        http = Http()
        request = api.request

    with pytest.raises(IdentityOperationError, match="previous credential restored"):
        rotate(FailedVerification(), data, client["clientId"], Path("/runtime/clients"))
    unchanged = Path("/runtime/clients/docs-mcp-reader-smoke").read_text() == old
    assert unchanged
    assert exchange(api, client, old).status_code == 200


def test_signing_key_rotation_preserves_old_token_verification(api):
    data = manifest()
    client = data["clients"][0]
    reconcile(api, data, Path("/runtime/clients"), True)
    secret = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    before = issue(api, client["clientId"], secret)
    realm = api.request("GET", "/admin/realms/projects")
    api.request(
        "POST",
        "/admin/realms/projects/components",
        {
            "name": "identity-rotation-proof",
            "providerId": "rsa-generated",
            "providerType": "org.keycloak.keys.KeyProvider",
            "parentId": realm["id"],
            "config": {
                "priority": ["200"],
                "enabled": ["true"],
                "active": ["true"],
                "algorithm": ["RS256"],
                "keySize": ["2048"],
            },
        },
    )
    after = issue(api, client["clientId"], secret)
    assert (
        jwt.get_unverified_header(before)["kid"]
        != jwt.get_unverified_header(after)["kid"]
    )
    keys = api.http.get(
        api.public_url + "/realms/projects/protocol/openid-connect/certs"
    ).json()["keys"]
    for token in [before, after]:
        key = jwt.PyJWK.from_dict(
            next(k for k in keys if k["kid"] == jwt.get_unverified_header(token)["kid"])
        ).key
        jwt.decode(
            token,
            key,
            algorithms=["RS256"],
            issuer=api.public_url + "/realms/projects",
            audience="https://vashfindir.ru/mcp/docs",
        )
