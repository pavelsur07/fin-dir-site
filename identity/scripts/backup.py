"""Host-side online logical backup, verified TOC and encrypted recovery bundle."""

from pathlib import Path
from datetime import datetime, timezone
import json, os, subprocess, tempfile


def backup(root, runtime, dc, env):
    backups = runtime / "backups"
    backups.mkdir(mode=0o700, parents=True, exist_ok=True)
    fd, name = tempfile.mkstemp(prefix="database-", suffix=".dump", dir=runtime)
    dump = Path(name)

    def run(args, **kw):
        result = subprocess.run(dc + args, cwd=root, env=env, **kw)
        if result.returncode:
            raise RuntimeError("Backup command failed.")
        return result

    try:
        with os.fdopen(fd, "wb") as output:
            run(
                [
                    "exec",
                    "-T",
                    "db",
                    "pg_dump",
                    "-U",
                    "postgres",
                    "-d",
                    "identity",
                    "--format=custom",
                ],
                stdout=output,
            )
        with dump.open("rb") as source:
            run(
                ["exec", "-T", "db", "pg_restore", "--list"],
                stdin=source,
                stdout=subprocess.DEVNULL,
            )
        name = (
            datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%S")
            + "-"
            + os.urandom(4).hex()
        )
        archive = backups / (name + ".enc")
        result = run(
            [
                "run",
                "--rm",
                "--user",
                str(os.getuid()) + ":" + str(os.getgid()),
                "operator",
                "scripts/backup_archive.py",
                "encrypt",
                "--dump",
                "/runtime/" + dump.name,
                "--archive",
                "/runtime/backups/" + archive.name,
            ],
            capture_output=True,
            text=True,
        )
        digest = result.stdout.strip()
        import re

        if not re.fullmatch("[0-9a-f]{64}", digest):
            raise RuntimeError("Invalid backup digest response.")
        manifest = {
            "schemaVersion": 1,
            "createdAt": datetime.now(timezone.utc).isoformat(),
            "database": "identity",
            "archive": archive.name,
            "sha256": digest,
            "release": env.get("IDENTITY_RELEASE", "local-check"),
            "images": {
                line.split("=", 1)[0]: line.split("=", 1)[1]
                for line in (root / "versions.env").read_text().splitlines()
                if line and not line.startswith("#")
            },
        }
        production = (runtime / "release.env").is_file()
        manifest["publicUrl"] = (
            "https://auth.vashfindir.ru"
            if production
            else env.get("IDENTITY_PUBLIC_URL", "https://identity.test")
        )
        manifest["adminUrl"] = (
            "https://auth-admin.vashfindir.ru"
            if production
            else env.get("IDENTITY_ADMIN_URL", "https://identity-admin.test")
        )
        manifest["consumerClient"] = (
            "docs-mcp-reader-prod" if production else "docs-mcp-reader-smoke"
        )
        if production:
            manifest["releaseImages"] = (
                (runtime / "release.env").read_text().splitlines()
            )
        path = backups / (name + ".json")
        fd = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
        with os.fdopen(fd, "w") as output:
            json.dump(manifest, output, indent=2)
        print("Encrypted Identity backup verified; manifest: " + str(path), flush=True)
        return path
    finally:
        dump.unlink(missing_ok=True)
