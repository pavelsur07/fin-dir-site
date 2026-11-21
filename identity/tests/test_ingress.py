import httpx, ssl


def test_public_admin_and_management_paths_are_not_exposed(api):
    for path in ["/admin/", "/admin/realms", "/metrics", "/health", "/health/ready"]:
        response = api.http.get(api.public_url + path)
        assert response.status_code in (403, 404)
    headers = {
        "X-Forwarded-For": "10.254.236.6",
        "X-Forwarded-Host": "identity-admin.test",
    }
    assert (
        api.http.get(api.public_url + "/admin/realms", headers=headers).status_code
        == 403
    )


def test_operator_can_reach_private_admin_console(api):
    assert api.http.get(api.admin_url + "/admin/").status_code in (200, 302, 303)
    assert (
        api.http.get(
            api.public_url + "/realms/projects/.well-known/openid-configuration"
        ).status_code
        == 200
    )


def test_tls_requires_explicit_smoke_ca():
    with httpx.Client(timeout=3, trust_env=False) as client:
        try:
            client.get(
                "https://identity.test/realms/master/.well-known/openid-configuration"
            )
        except httpx.ConnectError:
            return
    raise AssertionError("Untrusted smoke CA was accepted.")
