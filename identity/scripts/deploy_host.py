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


def scoped_registry_config(global_config):
    result = {
        "auths": {
            key: value
            for key, value in global_config.get("auths", {}).items()
            if key in {"ghcr.io", "https://ghcr.io"}
        }
    }
    helper = global_config.get("credHelpers", {}).get("ghcr.io") or global_config.get(
        "credsStore"
    )
    if helper:
        result["credHelpers"] = {"ghcr.io": helper}
    return result


def prepare_registry_config(directory, global_path):
    if directory.is_symlink():
        raise RuntimeError("Registry config directory symlink refused.")
    directory.mkdir(mode=0o700, parents=True, exist_ok=True)
    directory.chmod(0o700)
    target = directory / "config.json"
    if target.is_symlink():
        raise RuntimeError("Registry config symlink refused.")
    if not target.exists():
        global_config = (
            json.loads(global_path.read_text()) if global_path.is_file() else {}
        )
        fd = os.open(target, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
        with os.fdopen(fd, "w") as output:
            json.dump(scoped_registry_config(global_config), output)
    target.chmod(0o600)


def needs_database_backup(previous, current_exists, container_exists, volume_exists):
    if previous is None and (container_exists or volume_exists):
        raise RuntimeError("Existing database has no owned release configuration.")
    if container_exists:
        return True
    if current_exists or volume_exists:
        raise RuntimeError("Existing database requires recovery before deployment.")
    # A failed first pull can leave only protected config/secrets, without data.
    return False


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
            "DOCKER_CONFIG": str(BASE / "docker"),
        }

        prepare_registry_config(BASE / "docker", Path("/root/.docker/config.json"))

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
        if not (BASE / "current").exists() and available < 1000 * 1024:
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
        db_containers = run(
            [
                "docker",
                "ps",
                "-aq",
                "--filter",
                "label=com.docker.compose.project=identity-production",
                "--filter",
                "label=com.docker.compose.service=db",
            ],
            capture_output=True,
            text=True,
        ).stdout.splitlines()
        if len(db_containers) > 1:
            raise RuntimeError("Multiple Identity databases require operator recovery.")
        db_volumes = run(
            [
                "docker",
                "volume",
                "ls",
                "-q",
                "--filter",
                "name=identity-production_identity_db",
            ],
            capture_output=True,
            text=True,
        ).stdout.splitlines()
        if needs_database_backup(
            previous,
            (BASE / "current").exists(),
            bool(db_containers),
            "identity-production_identity_db" in db_volumes,
        ):
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
