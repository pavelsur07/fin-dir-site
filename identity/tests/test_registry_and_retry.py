from pathlib import Path
import json, pytest
from deploy_host import (
    scoped_registry_config,
    prepare_registry_config,
    needs_database_backup,
)


def test_component_keeps_ghcr_and_omits_unrelated_registry_auth(tmp_path):
    source = {
        "auths": {
            "ghcr.io": {"auth": "synthetic-ghcr"},
            "https://index.docker.io/v1/": {"auth": "synthetic-obsolete-hub"},
        },
        "credsStore": "example-helper",
        "credHelpers": {"unrelated.example": "other-helper"},
    }
    original = json.dumps(source)
    global_path = tmp_path / "global.json"
    global_path.write_text(original)
    directory = tmp_path / "component"
    prepare_registry_config(directory, global_path)
    actual = json.loads((directory / "config.json").read_text())
    assert set(actual["auths"]) == {"ghcr.io"}
    assert actual["credHelpers"] == {"ghcr.io": "example-helper"}
    assert global_path.read_text() == original
    assert directory.stat().st_mode & 0o777 == 0o700
    assert (directory / "config.json").stat().st_mode & 0o777 == 0o600


def test_first_pull_retry_does_not_require_nonexistent_database_backup():
    assert not needs_database_backup("previous-pending-config", False, False, False)
    assert not needs_database_backup(None, False, False, False)
    assert needs_database_backup("owned-config", True, True, True)
    assert needs_database_backup("pending-config", False, True, True)


@pytest.mark.parametrize(
    "previous,current,container,volume",
    [
        (None, False, True, True),
        ("owned", True, False, False),
        ("pending", False, False, True),
    ],
)
def test_existing_data_or_unknown_database_cannot_be_silently_skipped(
    previous, current, container, volume
):
    with pytest.raises(RuntimeError):
        needs_database_backup(previous, current, container, volume)
