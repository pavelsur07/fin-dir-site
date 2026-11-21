"""Bounded host-side component operations; never selects site projects or production restore."""

from pathlib import Path
import argparse, json, os, re, subprocess
from backup import backup

ROOT = Path(__file__).resolve().parent.parent
ENVIRONMENTS = {"dev", "smoke", "staging", "production"}


def compose(environment, runtime):
    if environment not in ENVIRONMENTS:
        raise RuntimeError("Unsupported Identity environment.")
    if not runtime.is_absolute() or runtime.is_symlink():
        raise RuntimeError("Absolute protected runtime required.")
    binary = Path("/opt/identity/bin/docker-compose")
    command = ([str(binary)] if binary.is_file() else ["docker", "compose"]) + [
        "-p",
        "identity-" + environment,
        "--env-file",
        "versions.env",
    ]
    if environment == "production":
        release = runtime / "release.env"
        values = {
            line.split("=", 1)[0]: line.split("=", 1)[1]
            for line in release.read_text().splitlines()
            if line and not line.startswith("#")
        }
        if set(values) != {
            "IDENTITY_KEYCLOAK_IMAGE",
            "IDENTITY_TOOLING_IMAGE",
            "IDENTITY_RELEASE",
        }:
            raise RuntimeError("Unexpected release configuration.")
        if not re.fullmatch("[0-9a-f]{40}", values["IDENTITY_RELEASE"]):
            raise RuntimeError("Exact release SHA required.")
        for key, kind in [
            ("IDENTITY_KEYCLOAK_IMAGE", "keycloak"),
            ("IDENTITY_TOOLING_IMAGE", "tools"),
        ]:
            if not re.fullmatch(
                r"ghcr\.io/pavelsur07/identity-" + kind + r"@sha256:[0-9a-f]{64}",
                values[key],
            ):
                raise RuntimeError("Digest-pinned Identity release image required.")
        command += ["--env-file", str(release)]
    command += [
        "-f",
        "compose.yaml",
        "-f",
        "compose.prod.yaml" if environment == "production" else "compose.smoke.yaml",
    ]
    return command


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "operation",
        choices=[
            "backup",
            "config",
            "runtime-apply",
            "bootstrap",
            "project-plan",
            "project-apply",
            "rotate-client",
            "smoke",
        ],
    )
    parser.add_argument("--environment", required=True, choices=sorted(ENVIRONMENTS))
    parser.add_argument("--runtime", required=True, type=Path)
    parser.add_argument("--project", default="documentation")
    parser.add_argument("--client")
    args = parser.parse_args()
    if not re.fullmatch("[a-z][a-z0-9-]{1,60}", args.project):
        raise RuntimeError("Invalid project identifier.")
    if args.client and not re.fullmatch("[a-z][a-z0-9-]{1,100}", args.client):
        raise RuntimeError("Invalid client identifier.")
    env = {**os.environ, "IDENTITY_RUNTIME_DIR": str(args.runtime)}
    if args.environment == "production":
        env["DOCKER_CONFIG"] = "/opt/identity/docker"
    dc = compose(args.environment, args.runtime)
    if args.operation == "backup":
        backup(ROOT, args.runtime, dc, env)
    elif args.operation == "config":
        subprocess.run(dc + ["config", "--quiet"], cwd=ROOT, env=env, check=True)
    elif args.operation in {
        "bootstrap",
        "project-plan",
        "project-apply",
        "rotate-client",
        "smoke",
    }:
        scripts = {
            "bootstrap": ["scripts/bootstrap.py"],
            "smoke": ["scripts/smoke.py", "--private"],
            "project-plan": [
                "scripts/provision.py",
                "plan",
                "--environment",
                args.environment,
                "--manifest",
                "config/projects/" + args.project + ".json",
                "--secret-dir",
                "/runtime/clients",
            ],
            "project-apply": [
                "scripts/provision.py",
                "apply",
                "--environment",
                args.environment,
                "--manifest",
                "config/projects/" + args.project + ".json",
                "--secret-dir",
                "/runtime/clients",
            ],
            "rotate-client": [
                "scripts/rotate_client.py",
                "--environment",
                args.environment,
                "--manifest",
                "config/projects/" + args.project + ".json",
                "--client",
                args.client or "",
            ],
        }
        subprocess.run(
            dc
            + [
                "run",
                "--rm",
                "--user",
                str(os.getuid()) + ":" + str(os.getgid()),
                "operator",
            ]
            + scripts[args.operation],
            cwd=ROOT,
            env=env,
            check=True,
        )
    else:
        # Releases are verified before this operator-only step; ordinary up/down are dev-only.
        manifest = json.loads((ROOT / "release-manifest.json").read_text())
        if (
            args.environment != "production"
            or env.get("IDENTITY_RELEASE") != manifest["revision"]
        ):
            raise RuntimeError("Verified production release SHA required.")
        subprocess.run(
            dc
            + [
                "up",
                "-d",
                "--wait",
                "--wait-timeout",
                "180",
                "db",
                "keycloak",
                "ingress",
            ],
            cwd=ROOT,
            env=env,
            check=True,
        )


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        print(
            "Identity operation failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
