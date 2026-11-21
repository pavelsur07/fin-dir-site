"""Restore a supplied encrypted backup only into a fresh, internal disposable stack."""

from pathlib import Path
from urllib.parse import urlsplit
import argparse, fcntl, json, os, re, shutil, subprocess, tempfile, time

ROOT = Path(__file__).resolve().parent.parent


def validate_manifest(path):
    metadata = json.loads(path.read_text())
    if metadata.get("schemaVersion") != 1 or metadata.get("database") != "identity":
        raise RuntimeError("Unsupported recovery manifest.")
    if not re.fullmatch(
        "[0-9]{8}T[0-9]{6}-[a-f0-9]{8}\.enc", metadata.get("archive", "")
    ) or not re.fullmatch("[0-9a-f]{64}", metadata.get("sha256", "")):
        raise RuntimeError("Unsafe recovery archive reference.")
    locked = {
        line.split("=", 1)[0]: line.split("=", 1)[1]
        for line in (ROOT / "versions.env").read_text().splitlines()
        if line and not line.startswith("#")
    }
    if metadata.get("images") != locked:
        raise RuntimeError("Recovery requires the exact original vendor versions.")
    for key in ["publicUrl", "adminUrl"]:
        url = urlsplit(metadata[key])
        if (
            url.scheme != "https"
            or not url.hostname
            or not re.fullmatch("[a-z0-9][a-z0-9.-]+", url.hostname)
            or url.port not in (None, 443)
            or url.username
            or url.password
            or url.path
            or url.query
            or url.fragment
        ):
            raise RuntimeError("Exact original HTTPS base URL required.")
    if not re.fullmatch("[a-z][a-z0-9-]{1,100}", metadata.get("consumerClient", "")):
        raise RuntimeError("Recovery client must be declared.")
    return metadata


def restore_check(manifest, key, consumer_image):
    metadata = validate_manifest(manifest)
    state = ROOT / ".runtime"
    state.mkdir(mode=0o700, exist_ok=True)
    with (state / "checks.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        runtime = Path(tempfile.mkdtemp(prefix="restore-", dir=state))
        public = urlsplit(metadata["publicUrl"]).hostname
        admin = urlsplit(metadata["adminUrl"]).hostname
        env = {
            **os.environ,
            "IDENTITY_RUNTIME_DIR": str(runtime),
            "IDENTITY_PUBLIC_URL": metadata["publicUrl"],
            "IDENTITY_ADMIN_URL": metadata["adminUrl"],
            "IDENTITY_PRIVATE_PUBLIC_HOST": public,
            "IDENTITY_PRIVATE_ADMIN_HOST": admin,
        }
        dc = [
            "docker",
            "compose",
            "-p",
            "identity-restore",
            "--env-file",
            "versions.env",
            "-f",
            "compose.yaml",
            "-f",
            "compose.smoke.yaml",
        ]

        def run(args, **kwargs):
            return subprocess.run(args, cwd=ROOT, env=env, check=True, **kwargs)

        active = run(dc + ["ps", "-q"], capture_output=True, text=True)
        if active.stdout.strip():
            runtime.rmdir()
            raise RuntimeError("Existing recovery stack must be stopped by its owner.")
        uid = str(os.getuid()) + ":" + str(os.getgid())
        start = time.monotonic()
        tools = os.environ.get(
            "IDENTITY_TOOLING_IMAGE", "vf-check/identity-tools:local"
        )
        init = [
            "docker",
            "run",
            "--rm",
            "--network",
            "none",
            "--user",
            uid,
            "-v",
            str(ROOT) + ":/identity:ro",
            "-v",
            str(runtime) + ":/runtime",
            tools,
        ]
        try:
            run(
                init
                + [
                    "scripts/init_runtime.py",
                    "/runtime",
                    "--public-host",
                    public,
                    "--admin-host",
                    admin,
                ]
            )
            shutil.copyfile(key, runtime / "backup_key")
            (runtime / "backup_key").chmod(0o600)
            shutil.copyfile(
                manifest.parent / metadata["archive"], runtime / "input.enc"
            )
            (runtime / "input.enc").chmod(0o600)
            run(
                init
                + [
                    "scripts/backup_archive.py",
                    "decrypt",
                    "--archive",
                    "/runtime/input.enc",
                    "--destination",
                    "/runtime/payload",
                    "--sha256",
                    metadata["sha256"],
                ]
            )
            payload = runtime / "payload"
            for name in ["db_password", "bootstrap_secret", "provisioner_secret"]:
                shutil.copyfile(payload / name, runtime / name)
                (runtime / name).chmod(
                    0o644 if name in {"db_password", "bootstrap_secret"} else 0o600
                )
            shutil.copytree(payload / "clients", runtime / "clients")
            run(dc + ["up", "-d", "--wait", "db"])
            with (payload / "database.dump").open("rb") as source:
                run(
                    dc
                    + [
                        "exec",
                        "-T",
                        "db",
                        "pg_restore",
                        "-U",
                        "postgres",
                        "--exit-on-error",
                        "--dbname=identity",
                    ],
                    stdin=source,
                )
            run(dc + ["up", "-d", "keycloak", "ingress"])
            (runtime / "consumer-settings.json").write_text(
                json.dumps(
                    {
                        "base": metadata["publicUrl"],
                        "clientId": metadata["consumerClient"],
                    }
                )
            )
            run(
                dc
                + [
                    "run",
                    "--rm",
                    "--user",
                    uid,
                    "operator",
                    "scripts/recovery_probe.py",
                    "--client",
                    metadata["consumerClient"],
                ]
            )
            run(
                [
                    "docker",
                    "run",
                    "--rm",
                    "--network",
                    "identity-restore_default",
                    "-v",
                    str(runtime) + ":/runtime:ro",
                    "-v",
                    str(ROOT / "tests/consumers/symfony-mcp-verifier.php")
                    + ":/probe.php:ro",
                    consumer_image,
                    "php",
                    "/probe.php",
                ]
            )
            print(
                f"Encrypted backup restored into isolated identity-restore; unchanged issuer/keys and real consumer passed in {time.monotonic()-start:.1f}s."
            )
        finally:
            cleanup = subprocess.run(dc + ["down", "--volumes"], cwd=ROOT, env=env)
            shutil.rmtree(runtime)
            if cleanup.returncode:
                raise RuntimeError("Recovery cleanup failed.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", type=Path, required=True)
    parser.add_argument("--key-file", type=Path, required=True)
    parser.add_argument("--consumer-image", required=True)
    args = parser.parse_args()
    try:
        restore_check(args.manifest, args.key_file, args.consumer_image)
    except Exception as error:
        print(
            "Isolated restore check failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
