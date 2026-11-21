from pathlib import Path
import os, pytest
from bootstrap import bootstrap
from keycloak_admin import KeycloakAdmin, IdentityOperationError


def test_bootstrap_removes_temporary_identity_and_limits_automation(api):
    class InterruptedHumanCreation:
        def __getattr__(self, name):
            return getattr(api, name)

        def request(self, method, path, body=None, statuses=(200, 201, 204)):
            result = api.request(method, path, body, statuses)
            if method == "POST" and path == "/admin/realms/master/users":
                raise IdentityOperationError(
                    "Synthetic interruption after human creation."
                )
            return result

    with pytest.raises(IdentityOperationError, match="Synthetic interruption"):
        bootstrap(InterruptedHumanCreation(), Path("/runtime"))
    human = api.request(
        "GET", "/admin/realms/master/users?username=identity-operator&exact=true"
    )[0]
    assert not any(
        r["name"] == "admin"
        for r in api.request(
            "GET", "/admin/realms/master/users/" + human["id"] + "/role-mappings/realm"
        )
    )
    limited = bootstrap(api, Path("/runtime"), remove_temporary=False)
    assert any(
        r["name"] == "admin"
        for r in api.request(
            "GET", "/admin/realms/master/users/" + human["id"] + "/role-mappings/realm"
        )
    )
    limited = bootstrap(api, Path("/runtime"))
    limited.request("GET", "/admin/realms/projects/clients")
    from provision import reconcile
    from test_token_profile import manifest

    data = manifest()
    data["clients"][0].update(clientId="limited-proof", secretRef="limited-proof")
    assert (
        reconcile(limited, data, Path("/runtime/clients"), True)[0]["action"]
        == "create"
    )
    assert (
        reconcile(limited, data, Path("/runtime/clients"), True)[0]["action"]
        == "unchanged"
    )
    denied = limited.http.post(
        limited.admin_url + "/admin/realms/projects/components",
        json={"name": "not-allowed"},
        headers={"Authorization": "Bearer " + limited.token},
    )
    assert denied.status_code == 403
    response = limited.http.get(
        limited.admin_url + "/admin/realms/master/users",
        headers={"Authorization": "Bearer " + limited.token},
    )
    assert response.status_code == 403
    response = limited.http.post(
        limited.public_url + "/realms/master/protocol/openid-connect/token",
        data={
            "grant_type": "client_credentials",
            "client_id": "identity-bootstrap",
            "client_secret": "deleted",
        },
    )
    assert response.status_code in (400, 401)
    assert Path("/runtime/bootstrap_secret").read_text() == ""
