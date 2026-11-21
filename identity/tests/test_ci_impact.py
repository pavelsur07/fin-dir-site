import pytest
from ci_impact import impacted, gate


@pytest.mark.parametrize(
    "path",
    [
        "identity/README.md",
        "site/src/Documentation/Adapter/DocMcpOAuthVerifier.php",
        "site/config/services.yaml",
        "site/composer.lock",
        "site/docker/production/php-cli/Dockerfile",
        "Makefile",
        "AGENTS.md",
        "docs/architecture/PATTERNS.md",
        ".github/workflows/identity-ci.yml",
        ".github/workflows/deploy-identity.yml",
    ],
)
def test_critical_contract_changes_run_identity_checks(path):
    assert impacted([path])


def test_unrelated_website_change_is_explicitly_skipped():
    assert not impacted(["site/templates/website/pages/marketing/home.html.twig"])
    assert gate(False, "skipped")


@pytest.mark.parametrize("result", ["failure", "cancelled", "skipped", ""])
def test_required_job_cannot_be_skipped_or_failed(result):
    assert not gate(True, result)


def test_successful_required_job_passes():
    assert gate(True, "success")
