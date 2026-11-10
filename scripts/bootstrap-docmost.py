"""Initialize a private Docmost workspace and optionally enable its public router."""

import argparse
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile


def run(command, *, payload=None):
    result = subprocess.run(
        command, input=payload, text=True, capture_output=True, timeout=60,
    )
    if result.returncode:
        # Container/API output can contain credentials; report only the operation.
        raise RuntimeError("Docmost operation failed; existing data and credentials were preserved")
    return result.stdout


def initialize(command):
    count = run(command + [
        "exec", "-T", "db", "psql", "-U", "docmost", "-d", "docmost",
        "-Atqc", "SELECT count(*) FROM workspaces",
    ]).strip()
    if count == "1":
        print("Workspace already initialized; no accounts or passwords were changed.")
        return
    if count != "0":
        raise RuntimeError("Unexpected workspace count; refusing to change this installation")
    settings = json.loads(run(command + ["config", "--format", "json"]))
    if settings["services"]["docmost"]["labels"]["traefik.enable"] != "false":
        raise RuntimeError("Initial setup requires DOCMOST_PUBLIC=false")
    email = os.environ.get("DOCMOST_OWNER_EMAIL", "")
    password = os.environ.get("DOCMOST_OWNER_PASSWORD", "")
    if not re.fullmatch(r"[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}", email):
        raise ValueError("Set a valid DOCMOST_OWNER_EMAIL")
    if len(password) < 24:
        raise ValueError("Set DOCMOST_OWNER_PASSWORD with at least 24 characters")
    payload = json.dumps({
        "workspaceName": "Ваш ФинДиректор", "name": "Владелец",
        "email": email, "password": password,
    })
    # Send credentials over stdin, never process arguments or command output.
    script = """
    let body = '';
    process.stdin.setEncoding('utf8');
    process.stdin.on('data', chunk => { body += chunk; });
    process.stdin.on('end', async () => {
      try {
        const response = await fetch('http://127.0.0.1:3000/api/auth/setup', {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body,
          signal: AbortSignal.timeout(20000)
        });
        process.exit(response.ok ? 0 : 1);
      } catch { process.exit(1); }
    });
    """
    run(command + ["exec", "-T", "docmost", "node", "-e", script], payload=payload)
    print("Created the workspace and its owner while public routing was disabled.")


def publish(env_file, command):
    # Also rejects an installation whose setup transaction has not completed.
    count = run(command + [
        "exec", "-T", "db", "psql", "-U", "docmost", "-d", "docmost",
        "-Atqc", "SELECT count(*) FROM workspaces",
    ]).strip()
    if count != "1":
        raise RuntimeError("Create the workspace owner before publication")
    content = env_file.read_text()
    content, replacements = re.subn(
        r"^DOCMOST_PUBLIC=.*$", "DOCMOST_PUBLIC=true", content, flags=re.MULTILINE,
    )
    if replacements != 1:
        raise ValueError("Expected exactly one DOCMOST_PUBLIC setting in the env file")
    temporary = None
    try:
        with tempfile.NamedTemporaryFile(mode="w", dir=env_file.parent, delete=False) as output:
            temporary = Path(output.name)
            os.chmod(temporary, 0o600)
            output.write(content)
        os.replace(temporary, env_file)
    finally:
        if temporary is not None:
            temporary.unlink(missing_ok=True)
    print("Public routing enabled in env; apply it with make docmost-up.")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--publish", action="store_true")
    parser.add_argument("--env-file", required=True, type=Path)
    parser.add_argument("compose", nargs=argparse.REMAINDER)
    args = parser.parse_args()
    if args.compose[:2] != ["docker", "compose"]:
        raise ValueError("Expected the Docker Compose command from Makefile")
    if args.publish:
        publish(args.env_file, args.compose)
    else:
        initialize(args.compose)


if __name__ == "__main__":
    try:
        main()
    except (ValueError, RuntimeError, subprocess.TimeoutExpired) as error:
        raise SystemExit(str(error))
