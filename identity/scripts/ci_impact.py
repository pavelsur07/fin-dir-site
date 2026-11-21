"""An always-running gate; unknown/cancelled required checks fail closed."""

import json, sys


def impacted(paths):
    roots = {
        "Makefile",
        "AGENTS.md",
        "docs/architecture/PATTERNS.md",
        ".github/CODEOWNERS",
        "scripts/check-documentation-mcp-http.php",
        "docker-compose.php-smoke.yml",
    }
    prefixes = (
        "identity/",
        ".github/workflows/identity-ci.yml",
        ".github/workflows/deploy-identity.yml",
        "site/src/Documentation/",
        "site/config/",
        "site/composer.",
        "site/docker/",
    )
    return any(path in roots or path.startswith(prefixes) for path in paths)


def gate(required, result):
    return result == "success" if required else result == "skipped"


if __name__ == "__main__":
    paths = sys.stdin.read().split("\0")
    print("true" if impacted(paths) else "false")
