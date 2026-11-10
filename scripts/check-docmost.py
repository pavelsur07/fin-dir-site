"""Validate Docmost isolation and optionally exercise a disposable installation."""

import argparse
import http.cookiejar
import io
import json
import os
from pathlib import Path
import secrets
import shlex
import subprocess
import tarfile
import tempfile
import urllib.error
import urllib.request


ROOT = Path(__file__).resolve().parent.parent


def run(command, *, env=None, check=True, binary=False):
    return subprocess.run(
        command, cwd=ROOT, env=env, text=not binary, capture_output=True, check=check,
        timeout=600,
    )


def compose(env_file, project="vf-docmost", smoke=False):
    command = [
        "docker", "compose", "--env-file", str(env_file), "-p", project,
        "-f", "docker-compose.docmost.yml",
    ]
    if smoke:
        command += ["-f", "docker-compose.docmost-smoke.yml"]
    return command


def config(command, env=None):
    # Never print this result: a real configuration contains credentials.
    return json.loads(run(command + ["config", "--format", "json"], env=env).stdout)


def publication_ready(command, settings):
    public = settings["services"]["docmost"]["labels"]["traefik.enable"]
    if public not in ("true", "false"):
        raise ValueError("DOCMOST_PUBLIC must be true or false")
    if public == "true":
        count = run(command + [
            "exec", "-T", "db", "psql", "-U", "docmost", "-d", "docmost",
            "-Atqc", "SELECT count(*) FROM workspaces",
        ])
        if count.stdout.strip() != "1":
            raise ValueError("Create the workspace owner through the SSH tunnel first")


def static_checks(directory, env):
    env_file = directory / ".env"
    run(["python3", "scripts/init-docmost.py", str(env_file)])
    original = env_file.read_bytes()
    assert env_file.stat().st_mode & 0o777 == 0o600
    assert run(["python3", "scripts/init-docmost.py", str(env_file)], check=False).returncode != 0
    assert env_file.read_bytes() == original, "Initializer overwrote existing credentials"

    command = compose(env_file)
    settings = config(command, env)
    services = settings["services"]
    assert set(services) == {"docmost", "db", "redis"}
    assert settings["networks"]["web"]["external"] is True
    assert settings["networks"]["web"]["name"] == "traefik_web"
    assert settings["networks"]["private"]["internal"] is True
    for name, service in services.items():
        assert "@sha256:" in service["image"] and ":latest" not in service["image"]
        assert service["healthcheck"] and service["volumes"]
        assert not service.get("container_name"), "Global container names break isolation"
        assert all(volume["type"] == "volume" for volume in service["volumes"])
        if name != "docmost":
            assert not service.get("ports")
            assert set(service["networks"]) == {"private"}
    app = services["docmost"]
    assert app["environment"]["APP_URL"] == "https://docs.vashfindir.ru"
    assert app["labels"]["traefik.enable"] == "false"
    assert len(app["ports"]) == 1 and app["ports"][0]["host_ip"] == "127.0.0.1"
    assert app["ports"][0]["published"] == "3100"
    publication_ready(command, settings)
    published = config(command, dict(env, DOCMOST_PUBLIC="true"))
    labels = published["services"]["docmost"]["labels"]
    assert labels["traefik.enable"] == "true"
    assert labels["traefik.http.routers.vf-docmost.rule"] == "Host(`docs.vashfindir.ru`)"
    assert labels["traefik.http.routers.vf-docmost-http.rule"] == "Host(`docs.vashfindir.ru`)"
    assert labels["traefik.http.middlewares.vf-docmost-https.redirectscheme.scheme"] == "https"
    assert labels["traefik.http.routers.vf-docmost.tls.certresolver"] == "le"
    assert labels["traefik.http.routers.vf-docmost.entrypoints"] == "websecure"

    for missing in ("DOCMOST_APP_SECRET", "DOCMOST_DB_PASSWORD"):
        result = run(command + ["config", "--quiet"], env=dict(env, **{missing: ""}), check=False)
        assert result.returncode != 0 and missing in result.stderr

    smoke_command = compose(env_file, "vf-docmost-smoke", smoke=True)
    smoke_settings = config(smoke_command, env)
    assert smoke_settings["networks"]["web"]["name"] == "vf-docmost-smoke-web"
    assert not smoke_settings["networks"]["web"].get("external")
    assert smoke_settings["services"]["docmost"]["labels"]["traefik.enable"] == "false"
    print("PASS: configuration, required secrets, bootstrap and network/volume isolation", flush=True)
    return smoke_command


def request_http(opener, path, payload=None, expected=200):
    body = None if payload is None else json.dumps(payload).encode()
    request = urllib.request.Request(
        "http://127.0.0.1:18042" + path, data=body,
        headers={"Content-Type": "application/json"},
    )
    try:
        response = opener.open(request, timeout=20)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        data = response.read().decode()
        assert response.code == expected, f"{path}: expected {expected}, got {response.code}"
        return data


def smoke_checks(command, env):
    existing = run(command + ["ps", "-aq"], env=env).stdout.strip()
    volumes = run([
        "docker", "volume", "ls", "-q", "--filter", "label=com.docker.compose.project=vf-docmost-smoke",
    ], env=env).stdout.strip()
    if existing or volumes:
        raise ValueError("vf-docmost-smoke already has containers or volumes; refusing to overwrite them")
    print("Starting disposable Docmost (pulling pinned images if needed)...", flush=True)
    try:
        run(command + ["up", "-d", "--wait", "--wait-timeout", "240"], env=env)
        ready_settings = config(command, env)
        ready_settings["services"]["docmost"]["labels"]["traefik.enable"] = "true"
        try:
            publication_ready(command, ready_settings)
        except ValueError:
            pass
        else:
            raise AssertionError("An empty workspace must not be published")
        anonymous = urllib.request.build_opener()
        authenticated = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        request_http(anonymous, "/api/health")
        assert "<html" in request_http(anonymous, "/").lower()
        payload = {
            "workspaceName": "Docmost smoke", "name": "Smoke Owner",
            "email": "owner@example.test", "password": secrets.token_hex(20),
        }
        env_file = command[command.index("--env-file") + 1]
        make = [
            "make", "--no-print-directory", "DOCMOST=" + shlex.join(command),
            "DOCMOST_ENV=" + env_file,
        ]
        assert run(make + ["docmost-publish"], env=env, check=False).returncode != 0
        assert "DOCMOST_PUBLIC=false" in Path(env_file).read_text()
        assert run(make + ["docmost-bootstrap"], env=env, check=False).returncode != 0
        owner_env = dict(env, DOCMOST_OWNER_EMAIL=payload["email"], DOCMOST_OWNER_PASSWORD=payload["password"])
        result = run(make + ["docmost-bootstrap"], env=owner_env)
        assert payload["password"] not in result.stdout + result.stderr
        run(make + ["docmost-bootstrap"], env=owner_env)
        request_http(authenticated, "/api/auth/login", {
            "email": payload["email"], "password": payload["password"],
        })
        request_http(anonymous, "/api/auth/setup", payload, expected=403)
        request_http(anonymous, "/api/workspace/info", {}, expected=401)
        assert "Ваш ФинДиректор" in request_http(authenticated, "/api/workspace/info", {})
        run(make + ["docmost-publish"], env=env)
        assert "DOCMOST_PUBLIC=true" in Path(env_file).read_text()
        assert Path(env_file).stat().st_mode & 0o777 == 0o600
        run(command + [
            "exec", "-T", "docmost", "node", "-e",
            "require('fs').writeFileSync('/app/data/storage/smoke-marker', 'persistent')",
        ], env=env)
        print("PASS: health, UI, owner setup, repeated setup rejection and private API access", flush=True)
        run(command + ["up", "-d", "--force-recreate", "--wait", "--wait-timeout", "240"], env=env)
        assert "Ваш ФинДиректор" in request_http(authenticated, "/api/workspace/info", {})
        run(command + [
            "exec", "-T", "docmost", "node", "-e",
            "process.exit(require('fs').readFileSync('/app/data/storage/smoke-marker','utf8')==='persistent'?0:1)",
        ], env=env)
        publication_ready(command, ready_settings)
        print("PASS: workspace, login and file storage survive container recreation", flush=True)
        run(make + ["docmost-stop"], env=env)
        dump = run(make + ["docmost-db-dump"], env=env, binary=True).stdout
        assert dump.startswith(b"PGDMP"), "Database dump is not a PostgreSQL custom archive"
        archive = run(make + ["docmost-files-dump"], env=env, binary=True).stdout
        with tarfile.open(fileobj=io.BytesIO(archive), mode="r:gz") as files:
            marker = files.extractfile("./smoke-marker")
            assert marker is not None and marker.read() == b"persistent"
        print("PASS: Makefile backup commands produce database and storage archives", flush=True)
    finally:
        # This project and its volumes are synthetic and never share production names.
        run(command + ["down", "--volumes", "--remove-orphans"], env=env)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--smoke", action="store_true")
    parser.add_argument("--ready", metavar="ENV_FILE")
    args = parser.parse_args()
    if args.ready:
        command = compose(args.ready)
        publication_ready(command, config(command))
        print("PASS: publication guard")
        return
    env = {key: value for key, value in os.environ.items() if not key.startswith(("DOCMOST_", "COMPOSE_"))}
    with tempfile.TemporaryDirectory(prefix="vf-docmost-check-") as temporary:
        command = static_checks(Path(temporary), env)
        if args.smoke:
            smoke_checks(command, env)


if __name__ == "__main__":
    try:
        main()
    except subprocess.CalledProcessError as error:
        # stdout can contain expanded configuration, so never include it here.
        raise SystemExit(f"Docmost check failed: command exited {error.returncode}\n{error.stderr}")
