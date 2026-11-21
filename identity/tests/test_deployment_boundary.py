from pathlib import Path
import pytest
from deploy_receiver import parse_command
from ops_host import compose


@pytest.mark.parametrize(
    "command",
    [
        "",
        "sh",
        "release $(id) a b c",
        "release " + "a" * 40 + " ghcr.io/other/x@sha256:" + "0" * 64 + " b c",
        "release a b c d; id",
    ],
)
def test_receiver_rejects_commands_and_unapproved_image_names(command):
    with pytest.raises((RuntimeError, ValueError)):
        parse_command(command)


def test_receiver_accepts_exact_release_protocol():
    revision = "a" * 40
    kc = "ghcr.io/pavelsur07/identity-keycloak@sha256:" + "b" * 64
    tools = "ghcr.io/pavelsur07/identity-tools@sha256:" + "c" * 64
    assert parse_command(
        "release " + revision + " " + kc + " " + tools + " " + "d" * 64
    ) == (revision, kc, tools, "d" * 64)


def test_wrong_environment_and_local_production_image_rejected(tmp_path):
    with pytest.raises(RuntimeError):
        compose("site-production", tmp_path)
    (tmp_path / "release.env").write_text(
        "IDENTITY_RELEASE="
        + "a" * 40
        + "\nIDENTITY_KEYCLOAK_IMAGE=keycloak:latest\nIDENTITY_TOOLING_IMAGE=tools:latest\n"
    )
    with pytest.raises(RuntimeError, match="Digest-pinned"):
        compose("production", tmp_path)


def test_supplied_image_must_match_independent_revision_tag_inspection():
    from deploy_host import validate_image

    digest = "ghcr.io/pavelsur07/identity-tools@sha256:" + "a" * 64
    inspected = {
        "RepoDigests": [digest],
        "Config": {
            "Labels": {
                "org.opencontainers.image.revision": "b" * 40,
                "org.opencontainers.image.source": "https://github.com/pavelsur07/fin-dir-site",
            }
        },
    }
    validate_image(inspected, digest, "b" * 40)
    with pytest.raises(RuntimeError, match="provenance"):
        validate_image(inspected, digest, "c" * 40)
    with pytest.raises(RuntimeError, match="provenance"):
        validate_image(inspected, digest.replace("a" * 64, "d" * 64), "b" * 40)
