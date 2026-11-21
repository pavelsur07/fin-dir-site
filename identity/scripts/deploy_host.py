"""Host-side immutable release; callable only through the restricted receiver."""

from pathlib import Path
import fcntl, hashlib, json, os, re, subprocess, sys, tempfile, time
from package_release import verify

BASE = Path("/opt/identity")
RUNTIME = BASE / "runtime/production"


def validate_image(inspection, supplied, revision):
    labels = inspection["Config"].get("Labels", {})
    if (
        supplied not in inspection.get("RepoDigests", [])
        or labels.get("org.opencontainers.image.revision") != revision
        or labels.get("org.opencontainers.image.source")
        != "https://github.com/pavelsur07/fin-dir-site"
    ):
        raise RuntimeError("Image digest/provenance differs from trusted revision tag.")


def deploy(revision, keycloak, tools, archive, checksum, expected_tree):
    if not re.fullmatch("[0-9a-f]{40}", revision) or not re.fullmatch(
        "[0-9a-f]{64}", checksum
    ):
        raise RuntimeError("Invalid release identifiers.")
    for image, kind in [(keycloak, "keycloak"), (tools, "tools")]:
        if not re.fullmatch(
            "ghcr.io/pavelsur07/identity-" + kind + "@sha256:[0-9a-f]{64}", image
        ):
            raise RuntimeError("Invalid release image.")
    if hashlib.sha256(archive.read_bytes()).hexdigest() != checksum:
        raise RuntimeError("Release transfer checksum mismatch.")
    BASE.mkdir(mode=0o755, parents=True, exist_ok=True)
    with (BASE / "deploy.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        destination = BASE / "releases" / revision
        if destination.exists():
            # Reattempts must match all exact files, not trust a directory name.
            manifest = verify(archive, revision, expected_tree=expected_tree)
            for name, digest in manifest["files"].items():
                if (
                    hashlib.sha256((destination / name).read_bytes()).hexdigest()
                    != digest
                ):
                    raise RuntimeError("Existing release differs.")
        else:
            verify(archive, revision, destination, expected_tree)
        env = {
            **os.environ,
            "IDENTITY_RUNTIME_DIR": str(RUNTIME),
            "IDENTITY_RELEASE": revision,
        }

        def run(args, **kw):
            return subprocess.run(args, cwd=destination, env=env, check=True, **kw)

        # Preflight never pulls a full site checkout or changes other Compose projects.
        available = int(
            next(
                line.split()[1]
                for line in Path("/proc/meminfo").read_text().splitlines()
                if line.startswith("MemAvailable:")
            )
        )
        if not (RUNTIME / "release.env").exists() and available < 1000 * 1024:
            raise RuntimeError("Insufficient shared-host available memory.")
        RUNTIME.mkdir(mode=0o700, parents=True, exist_ok=True)
        RUNTIME.chmod(0o700)
        for supplied, kind in [(keycloak, "keycloak"), (tools, "tools")]:
            tag = "ghcr.io/pavelsur07/identity-" + kind + ":sha-" + revision
            run(["docker", "pull", tag])
            inspected = json.loads(
                run(
                    ["docker", "image", "inspect", tag], capture_output=True, text=True
                ).stdout
            )[0]
            validate_image(inspected, supplied, revision)
        previous = (
            (RUNTIME / "release.env").read_text()
            if (RUNTIME / "release.env").exists()
            else None
        )
        if previous:
            run(
                [
                    "python3",
                    "scripts/ops_host.py",
                    "backup",
                    "--environment",
                    "production",
                    "--runtime",
                    str(RUNTIME),
                ]
            )
        release = f"IDENTITY_RELEASE={revision}\nIDENTITY_KEYCLOAK_IMAGE={keycloak}\nIDENTITY_TOOLING_IMAGE={tools}\n"
        (RUNTIME / "release.env").write_text(release)
        run(
            [
                "docker",
                "run",
                "--rm",
                "--user",
                "0:0",
                "-v",
                str(RUNTIME) + ":/runtime",
                tools,
                "scripts/init_runtime.py",
                "/runtime",
                "--production",
            ]
        )
        # Persist recovery metadata before the first database/schema initialization.
        record = {
            "revision": revision,
            "keycloak": keycloak,
            "tools": tools,
            "archiveSHA256": checksum,
            "previousRelease": previous,
            "startedAt": time.time(),
            "status": "pending",
        }
        (RUNTIME / "deployment.json").write_text(json.dumps(record, indent=2))
        run(
            [
                "python3",
                "scripts/ops_host.py",
                "runtime-apply",
                "--environment",
                "production",
                "--runtime",
                str(RUNTIME),
            ]
        )
        if (RUNTIME / "bootstrap_secret").read_text().strip():
            run(
                [
                    "python3",
                    "scripts/ops_host.py",
                    "bootstrap",
                    "--environment",
                    "production",
                    "--runtime",
                    str(RUNTIME),
                ]
            )
        run(
            [
                "python3",
                "scripts/ops_host.py",
                "project-apply",
                "--environment",
                "production",
                "--runtime",
                str(RUNTIME),
                "--project",
                "documentation",
            ]
        )
        # The external route uses the system CA, not the private operator CA.
        run(
            [
                "docker",
                "run",
                "--rm",
                "--network",
                "traefik_web",
                "--user",
                "0:0",
                "-v",
                str(RUNTIME / "clients/docs-mcp-reader-prod")
                + ":/run/secrets/client:ro",
                tools,
                "scripts/smoke.py",
                "--base",
                "https://auth.vashfindir.ru",
                "--client",
                "docs-mcp-reader-prod",
                "--secret-file",
                "/run/secrets/client",
            ]
        )
        run(
            [
                "python3",
                "scripts/ops_host.py",
                "backup",
                "--environment",
                "production",
                "--runtime",
                str(RUNTIME),
            ]
        )
        record["status"] = "healthy"
        record["finishedAt"] = time.time()
        (RUNTIME / "deployment.json").write_text(json.dumps(record, indent=2))
        link = BASE / "current.next"
        link.unlink(missing_ok=True)
        link.symlink_to(destination)
        os.replace(link, BASE / "current")
        print(
            "Identity deployed with verified public issuer and encrypted recovery backup: "
            + revision
        )
