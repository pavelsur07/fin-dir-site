"""Validate effective production isolation without creating containers or using secrets."""

from pathlib import Path
import json, os, shutil, subprocess, tempfile
from ops_host import compose

ROOT = Path(__file__).resolve().parent.parent


def validate():
    state = ROOT / ".runtime"
    state.mkdir(mode=0o700, exist_ok=True)
    runtime = Path(tempfile.mkdtemp(prefix="validate-", dir=state))
    try:
        release = (
            "IDENTITY_RELEASE="
            + "0" * 40
            + "\nIDENTITY_KEYCLOAK_IMAGE=ghcr.io/pavelsur07/identity-keycloak@sha256:"
            + "1" * 64
            + "\nIDENTITY_TOOLING_IMAGE=ghcr.io/pavelsur07/identity-tools@sha256:"
            + "2" * 64
            + "\n"
        )
        (runtime / "release.env").write_text(release)
        result = subprocess.run(
            compose("production", runtime) + ["config", "--format", "json"],
            cwd=ROOT,
            env={**os.environ, "IDENTITY_RUNTIME_DIR": str(runtime)},
            capture_output=True,
            text=True,
            check=True,
        )
        model = json.loads(result.stdout)
        if (
            model["name"] != "identity-production"
            or not model["networks"]["default"]["internal"]
        ):
            raise RuntimeError("Production namespace/network isolation lost.")
        for name, service in model["services"].items():
            if service.get("ports"):
                raise RuntimeError("Identity must not publish host ports.")
            if name in {"db", "keycloak"} and set(service["networks"]) != {"default"}:
                raise RuntimeError("Database or Keycloak joined a public network.")
            if name in {"tools", "operator"}:
                if any(v["source"] != str(runtime) for v in service.get("volumes", [])):
                    raise RuntimeError(
                        "Production tooling depends on mounted source code."
                    )
        if set(model["services"]["ingress"]["networks"]) != {"default", "web"}:
            raise RuntimeError("Ingress network boundary differs.")
        print(
            "Effective production topology: no host ports, private DB/Keycloak, independent state and immutable tooling passed."
        )
    finally:
        shutil.rmtree(runtime)


if __name__ == "__main__":
    try:
        validate()
    except Exception as error:
        print(
            "Topology validation failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
