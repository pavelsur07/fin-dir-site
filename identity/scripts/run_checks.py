"""Own one transient stack; serialize local fixed-subnet usage and always clean up."""

from pathlib import Path
import argparse, fcntl, hashlib, json, os, shutil, subprocess, tempfile, time
from backup import backup

ROOT = Path(__file__).resolve().parent.parent


def run(argv, env, **kw):
    result = subprocess.run(argv, cwd=kw.pop("cwd", ROOT), env=env, **kw)
    if result.returncode:
        raise RuntimeError("Identity check command failed.")
    return result


def consumer(env, runtime, image, unavailable=False):
    run(
        [
            "docker",
            "run",
            "--rm",
            "--network",
            "identity-smoke_default",
            "-v",
            str(runtime) + ":/runtime:ro",
            "-v",
            str(ROOT / "tests/consumers/symfony-mcp-verifier.php") + ":/probe.php:ro",
            image,
            "php",
            "/probe.php",
        ]
        + (["--expect-unavailable"] if unavailable else []),
        env,
    )


def checks(image):
    state = ROOT / ".runtime"
    state.mkdir(mode=0o700, exist_ok=True)
    with (state / "checks.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        runtime = Path(tempfile.mkdtemp(prefix="check-", dir=state))
        env = {**os.environ, "IDENTITY_RUNTIME_DIR": str(runtime)}
        dc = [
            "docker",
            "compose",
            "-p",
            "identity-smoke",
            "--env-file",
            "versions.env",
            "-f",
            "compose.yaml",
            "-f",
            "compose.smoke.yaml",
        ]
        active = subprocess.run(
            dc + ["ps", "--quiet"],
            cwd=ROOT,
            env=env,
            capture_output=True,
            text=True,
            check=True,
        )
        if active.stdout.strip():
            runtime.rmdir()
            raise RuntimeError(
                "An existing identity-smoke stack must be stopped before CI."
            )
        uid = str(os.getuid()) + ":" + str(os.getgid())
        started = time.monotonic()
        try:
            run(["python3", "scripts/validate_topology.py"], env)
            run(["make", "init", "IDENTITY_RUNTIME_DIR=" + str(runtime)], env)
            run(dc + ["config", "--quiet"], env)
            run(dc + ["up", "-d", "db", "keycloak", "ingress"], env)
            run(
                dc + ["run", "--rm", "--user", uid, "operator", "scripts/validate.py"],
                env,
            )
            run(
                dc
                + [
                    "run",
                    "--rm",
                    "--user",
                    uid,
                    "operator",
                    "-m",
                    "pytest",
                    "-p",
                    "no:cacheprovider",
                    "tests",
                    "-q",
                    "--tb=short",
                    "--show-capture=no",
                ],
                env,
            )
            role = run(
                dc
                + [
                    "exec",
                    "-T",
                    "db",
                    "psql",
                    "-U",
                    "postgres",
                    "-d",
                    "identity",
                    "-Atqc",
                    "SELECT rolsuper::integer FROM pg_roles WHERE rolname='identity'",
                ],
                env,
                capture_output=True,
                text=True,
            )
            if role.stdout.strip() != "0":
                raise RuntimeError("Application database role is privileged.")
            consumer(env, runtime, image)
            # A different container/source IP must not enter the private admin route.
            run(
                dc
                + [
                    "run",
                    "--rm",
                    "--user",
                    uid,
                    "tools",
                    "scripts/external_ingress_probe.py",
                ],
                env,
            )
            tunnel = run(
                [
                    "curl",
                    "--resolve",
                    "identity-admin.test:443:10.254.236.4",
                    "--cacert",
                    str(runtime / "ca.crt"),
                    "--connect-timeout",
                    "3",
                    "--max-time",
                    "5",
                    "--silent",
                    "--show-error",
                    "--output",
                    "/dev/null",
                    "--write-out",
                    "%{http_code}",
                    "https://identity-admin.test/admin/",
                ],
                env,
                capture_output=True,
                text=True,
            )
            if tunnel.stdout not in {"200", "302", "303"}:
                raise RuntimeError(
                    "Host/SSH tunnel source cannot reach private administrator login."
                )
            # Real production SDK/HTTP boundary, not only standalone JWT validation.
            run(
                dc
                + [
                    "run",
                    "--rm",
                    "--user",
                    uid,
                    "operator",
                    "scripts/prepare_http_contract.py",
                ],
                env,
            )
            http_dir = runtime / "http-contract"
            nginx = (ROOT.parent / "site/docker/common/nginx/default.conf").read_text()
            (http_dir / "nginx.conf").write_text(
                nginx.replace(
                    "fastcgi_pass $fpm_upstream;",
                    "fastcgi_pass $fpm_upstream;\n        add_header X-Smoke-Upstream $upstream_addr always;",
                )
            )
            (http_dir / "nginx.conf").chmod(0o644)
            http_env = {**env, "PHP_CLI_IMAGE": image}
            web = [
                "docker",
                "compose",
                "-p",
                "identity-consumer",
                "-f",
                "docker-compose.php-smoke.yml",
                "-f",
                "identity/tests/compose.consumer.yaml",
            ]
            try:
                run(
                    web + ["up", "-d", "--wait", "site-postgres"],
                    http_env,
                    cwd=ROOT.parent,
                )
                run(
                    web
                    + [
                        "run",
                        "--rm",
                        "site-php-cli",
                        "php",
                        "bin/console",
                        "doctrine:migrations:migrate",
                        "--no-interaction",
                        "--allow-no-migration",
                    ],
                    http_env,
                    cwd=ROOT.parent,
                )
                run(
                    web + ["up", "-d", "--wait", "--scale", "site-php-fpm=2", "site"],
                    http_env,
                    cwd=ROOT.parent,
                )
                run(
                    web + ["run", "--rm", "site-php-cli", "php", "/check-mcp-http.php"],
                    http_env,
                    cwd=ROOT.parent,
                )
            finally:
                subprocess.run(
                    web + ["down", "--volumes"],
                    cwd=ROOT.parent,
                    env=http_env,
                    check=True,
                )
            manifest_path = backup(ROOT, runtime, dc, env)
            metadata = json.loads(manifest_path.read_text())
            evidence = os.environ.get("IDENTITY_RECOVERY_EVIDENCE_DIR")
            if evidence:
                evidence = Path(evidence).absolute()
                if state.absolute() not in evidence.parents:
                    raise RuntimeError(
                        "Recovery evidence must stay in ignored component runtime."
                    )
                evidence.mkdir(mode=0o700, exist_ok=False)
                for source in [
                    manifest_path,
                    manifest_path.parent / metadata["archive"],
                    runtime / "backup_key",
                ]:
                    shutil.copyfile(source, evidence / source.name)
                    (evidence / source.name).chmod(0o600)
            run(
                dc
                + [
                    "run",
                    "--rm",
                    "--user",
                    uid,
                    "operator",
                    "scripts/backup_archive.py",
                    "decrypt",
                    "--archive",
                    "/runtime/backups/" + metadata["archive"],
                    "--destination",
                    "/runtime/restored",
                    "--sha256",
                    metadata["sha256"],
                ],
                env,
            )
            dump = runtime / "restored/database.dump"
            run(dc + ["stop", "keycloak"], env)
            consumer(env, runtime, image, True)
            run(
                dc
                + [
                    "exec",
                    "-T",
                    "db",
                    "createdb",
                    "-U",
                    "postgres",
                    "-O",
                    "identity",
                    "identity_restore",
                ],
                env,
            )
            with dump.open("rb") as source:
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
                        "--dbname=identity_restore",
                    ],
                    env,
                    stdin=source,
                )
            env["IDENTITY_DATABASE"] = "identity_restore"
            run(dc + ["up", "-d", "--force-recreate", "keycloak"], env)
            run(
                dc
                + [
                    "run",
                    "--rm",
                    "--user",
                    uid,
                    "operator",
                    "scripts/recovery_probe.py",
                ],
                env,
            )
            consumer(env, runtime, image)
            print(
                f"Identity CI passed: real issuer/profile/isolation/ingress/bootstrap/restore/consumer, {time.monotonic()-started:.1f}s.",
                flush=True,
            )
        except Exception:
            subprocess.run(
                dc + ["logs", "--tail", "40", "keycloak", "ingress"], cwd=ROOT, env=env
            )
            raise
        finally:
            cleanup = subprocess.run(dc + ["down", "--volumes"], cwd=ROOT, env=env)
            shutil.rmtree(runtime)
            if cleanup.returncode:
                raise RuntimeError("Transient stack cleanup failed.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--consumer-image", required=True)
    args = parser.parse_args()
    try:
        checks(args.consumer_image)
    except Exception as error:
        print("Identity CI failed: " + str(error), file=__import__("sys").stderr)
        raise SystemExit(1)
