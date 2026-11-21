#!/usr/bin/env python3
"""Forced SSH command: fixed Identity releases only, bounded stdin, no shell evaluation."""
from pathlib import Path
import hashlib, os, re, shlex, sys, tempfile
from deploy_host import deploy


def parse_command(command):
    args = shlex.split(command)
    if len(args) != 5 or args[0] != "release":
        raise RuntimeError("Only the Identity release protocol is permitted.")
    _, revision, keycloak, tools, checksum = args
    if not re.fullmatch("[0-9a-f]{40}", revision) or not re.fullmatch(
        "[0-9a-f]{64}", checksum
    ):
        raise RuntimeError("Invalid revision/checksum.")
    for image, kind in [(keycloak, "keycloak"), (tools, "tools")]:
        if not re.fullmatch(
            "ghcr.io/pavelsur07/identity-" + kind + "@sha256:[0-9a-f]{64}", image
        ):
            raise RuntimeError("Invalid release image.")
    return revision, keycloak, tools, checksum


def github_json(path):
    import urllib.request, json

    request = urllib.request.Request(
        "https://api.github.com/repos/pavelsur07/fin-dir-site/" + path,
        headers={
            "User-Agent": "identity-release/1",
            "Accept": "application/vnd.github+json",
        },
    )
    with urllib.request.urlopen(request, timeout=15) as response:
        from urllib.parse import urlsplit

        url = urlsplit(response.geturl())
        if url.scheme != "https" or url.hostname != "api.github.com":
            raise RuntimeError("Unexpected canonical source origin.")
        payload = response.read(8 * 1024 * 1024 + 1)
        if len(payload) > 8 * 1024 * 1024:
            raise RuntimeError("Canonical tree exceeds size limit.")
    return json.loads(payload)


def main():
    revision, keycloak, tools, checksum = parse_command(
        os.environ.get("SSH_ORIGINAL_COMMAND", "")
    )
    # Source is authenticated independently of the deployment key/archive.
    master = github_json("commits/master")
    if master["sha"] != revision:
        raise RuntimeError("Release must equal current master HEAD.")
    listing = github_json(
        "git/trees/" + master["commit"]["tree"]["sha"] + "?recursive=1"
    )
    if listing.get("truncated") or not isinstance(listing.get("tree"), list):
        raise RuntimeError("Canonical Git tree listing incomplete.")
    expected = {}
    for entry in listing["tree"]:
        if not entry["path"].startswith("identity/"):
            continue
        if entry["type"] == "tree":
            continue
        if entry["type"] != "blob" or entry["mode"] not in {"100644", "100755"}:
            raise RuntimeError("Canonical release tree has unsupported entry.")
        expected[entry["path"][len("identity/") :]] = {
            key: entry[key] for key in ["sha", "mode", "size"]
        }
    if not expected:
        raise RuntimeError("Canonical Identity tree is empty.")
    incoming = Path("/opt/identity/incoming")
    incoming.mkdir(mode=0o700, parents=True, exist_ok=True)
    fd, name = tempfile.mkstemp(prefix="release-", suffix=".tar.gz", dir=incoming)
    path = Path(name)
    try:
        with os.fdopen(fd, "wb") as output:
            payload = sys.stdin.buffer.read(16 * 1024 * 1024 + 1)
            if len(payload) > 16 * 1024 * 1024:
                raise RuntimeError("Release exceeds size limit.")
            output.write(payload)
        deploy(revision, keycloak, tools, path, checksum, expected)
    finally:
        path.unlink(missing_ok=True)


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        print("Identity deployment failed: " + type(error).__name__, file=sys.stderr)
        raise SystemExit(1)
