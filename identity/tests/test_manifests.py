from pathlib import Path
import json
import pytest
from provision import load_manifest, IdentityOperationError


def test_wrong_environment_fails_before_any_network():
    with pytest.raises(IdentityOperationError):
        load_manifest(Path("/identity/config/projects/documentation.json"), "staging")
