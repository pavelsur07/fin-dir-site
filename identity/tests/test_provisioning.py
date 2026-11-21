from pathlib import Path
from copy import deepcopy
import json, pytest
from provision import reconcile, IdentityOperationError, load_manifest
from test_token_profile import manifest


def test_plan_is_read_only_and_repeat_does_not_rotate(api):
    data = manifest()
    reconcile(api, data, Path("/runtime/clients"), True)
    secret = Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    assert reconcile(api, data, Path("/runtime/clients"), False) == [
        {"clientId": "docs-mcp-reader-smoke", "action": "unchanged"}
    ]
    assert (
        reconcile(api, data, Path("/runtime/clients"), True)[0]["action"] == "unchanged"
    )
    unchanged = secret == Path("/runtime/clients/docs-mcp-reader-smoke").read_text()
    assert unchanged


def test_unowned_client_is_not_adopted(api):
    name = "foreign-client"
    found = api.request("GET", "/admin/realms/projects/clients?clientId=" + name)
    if not found:
        api.request(
            "POST",
            "/admin/realms/projects/clients",
            {"clientId": name, "enabled": False},
        )
    data = manifest()
    data["clients"][0]["clientId"] = name
    with pytest.raises(IdentityOperationError, match="unowned"):
        reconcile(api, data, Path("/runtime/clients"), True)


def test_scope_conflict_stops_before_new_client_creation(api):
    data = manifest()
    data["projectId"] = "other-project"
    data["clients"][0]["clientId"] = "conflict-client"
    data["clients"][0]["secretRef"] = "conflict-client"
    with pytest.raises(IdentityOperationError, match="Scope ownership"):
        reconcile(api, data, Path("/runtime/clients"), True)
    assert not api.request(
        "GET", "/admin/realms/projects/clients?clientId=conflict-client"
    )


def test_partial_failure_has_safe_retry(api):
    data = manifest()
    data["clients"][0]["clientId"] = "retry-client"
    data["clients"][0]["secretRef"] = "retry-client"

    class Interrupted:
        def __init__(self):
            self.failed = False

        def request(self, method, path, body=None, statuses=(200, 201, 204)):
            if method == "POST" and path.endswith("/clients") and not self.failed:
                self.failed = True
                raise IdentityOperationError("Synthetic network failure.")
            return api.request(method, path, body, statuses)

    # A previous test run may already have completed the same idempotent operation.
    existing = api.request(
        "GET", "/admin/realms/projects/clients?clientId=retry-client"
    )
    if existing:
        assert (
            reconcile(api, data, Path("/runtime/clients"), True)[0]["action"]
            == "unchanged"
        )
        return
    with pytest.raises(IdentityOperationError):
        reconcile(Interrupted(), data, Path("/runtime/clients"), True)
    assert reconcile(api, data, Path("/runtime/clients"), True)[0]["action"] == "create"
    assert (
        reconcile(api, data, Path("/runtime/clients"), True)[0]["action"] == "unchanged"
    )
