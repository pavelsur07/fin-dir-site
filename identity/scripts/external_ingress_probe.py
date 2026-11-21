import os, ssl, httpx

ctx = ssl.create_default_context(cafile=os.environ["IDENTITY_CA"])
with httpx.Client(
    verify=ctx, trust_env=False, timeout=5, follow_redirects=False
) as client:
    for headers in [
        {},
        {"X-Forwarded-For": "10.254.236.6", "X-Forwarded-Host": "identity-admin.test"},
    ]:
        response = client.get(
            os.environ["IDENTITY_ADMIN_URL"] + "/admin/realms", headers=headers
        )
        if response.status_code != 403:
            raise RuntimeError("Private admin ingress bypass.")
    for host in ["identity.test", "identity-admin.test"]:
        for path in [
            "/admin/",
            "/admin/realms",
            "/metrics",
            "/health/ready",
            "/realms/master/.well-known/openid-configuration",
        ]:
            response = client.get(
                "http://ingress:8081" + path,
                headers={"Host": host, "X-Forwarded-For": "10.254.236.6"},
            )
            if response.status_code != 404:
                raise RuntimeError("Public ingress exposed a private endpoint.")
    response = client.get(
        "http://ingress:8081/realms/projects/.well-known/openid-configuration",
        headers={"Host": "identity.test"},
    )
    if response.status_code != 200:
        raise RuntimeError("Public discovery inaccessible.")
    if response.json()["issuer"] != "https://identity.test/realms/projects":
        raise RuntimeError("Issuer altered by proxy.")
print("Public path isolation, private admin and forwarding spoof checks passed.")
