import os, sys
from pathlib import Path
import pytest

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from keycloak_admin import KeycloakAdmin


@pytest.fixture(scope="session")
def api():
    api = KeycloakAdmin(
        os.environ.get("IDENTITY_PUBLIC_URL", "https://identity.test"),
        os.environ.get("IDENTITY_ADMIN_URL", "https://identity-admin.test"),
        os.environ.get("IDENTITY_CA", "/runtime/ca.crt"),
    )
    api.wait_ready()
    api.login("identity-bootstrap", Path("/runtime/bootstrap_secret"))
    existing = api.request("GET", "/admin/realms")
    if not any(r["realm"] == "projects" for r in existing):
        api.request(
            "POST",
            "/admin/realms",
            {
                "realm": "projects",
                "attributes": {"identity.managedBy": "app-service-identity"},
                "enabled": True,
                "registrationAllowed": False,
                "resetPasswordAllowed": False,
                "accessTokenLifespan": 300,
                "eventsEnabled": True,
                "adminEventsEnabled": True,
                "adminEventsDetailsEnabled": False,
                "bruteForceProtected": True,
                "permanentLockout": False,
            },
        )
    return api
