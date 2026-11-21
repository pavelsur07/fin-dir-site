"""Read-only issuer check; no user data or credentials in output."""

from pathlib import Path
import argparse, json, os, ssl, time
import httpx, jwt


def check(base, client_id, secret_file, ca=None):
    with httpx.Client(
        verify=ssl.create_default_context(cafile=ca),
        timeout=10,
        follow_redirects=False,
        trust_env=False,
    ) as http:
        issuer = base.rstrip("/") + "/realms/projects"
        response = http.get(issuer + "/.well-known/openid-configuration")
        if response.status_code != 200:
            raise RuntimeError("Discovery unavailable.")
        discovery = response.json()
        for name, path in [
            ("issuer", ""),
            ("token_endpoint", "/protocol/openid-connect/token"),
            ("jwks_uri", "/protocol/openid-connect/certs"),
        ]:
            if discovery[name] != issuer + path:
                raise RuntimeError("Unexpected discovery URL.")
        response = http.post(
            discovery["token_endpoint"],
            data={
                "grant_type": "client_credentials",
                "client_id": client_id,
                "client_secret": secret_file.read_text().strip(),
                "scope": "docs:read",
                "resource": "https://vashfindir.ru/mcp/docs",
            },
        )
        if response.status_code != 200:
            raise RuntimeError("Token exchange failed.")
        token = response.json()["access_token"]
        header = jwt.get_unverified_header(token)
        if header.get("typ") != "at+jwt" or header.get("alg") != "RS256":
            raise RuntimeError("Token header contract failed.")
        keys = http.get(discovery["jwks_uri"]).json()["keys"]
        key = jwt.PyJWK.from_dict(
            next(k for k in keys if k["kid"] == header["kid"])
        ).key
        claims = jwt.decode(
            token,
            key,
            algorithms=["RS256"],
            issuer=issuer,
            audience="https://vashfindir.ru/mcp/docs",
            options={"require": ["exp", "iat", "client_id", "scope"]},
        )
        if (
            claims["client_id"] != client_id
            or claims["exp"] - claims["iat"] > 300
            or claims["scope"].split() != ["docs:read"]
        ):
            raise RuntimeError("Token identity/scope/lifetime contract failed.")
        for host in [base, base.replace("://auth.", "://auth-admin.")]:
            for path in ["/admin/realms", "/health/ready", "/metrics"]:
                if http.get(host + path).status_code not in (403, 404):
                    raise RuntimeError("Private endpoint exposed.")
        print("Issuer HTTPS/discovery/JWKS/token/TTL/private-endpoint smoke passed.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--base", default=os.environ.get("IDENTITY_PUBLIC_URL", "https://identity.test")
    )
    parser.add_argument("--client", default="docs-mcp-reader-prod")
    parser.add_argument(
        "--secret-file",
        type=Path,
        default=Path("/runtime/clients/docs-mcp-reader-prod"),
    )
    parser.add_argument("--private", action="store_true")
    args = parser.parse_args()
    try:
        check(
            args.base,
            args.client,
            args.secret_file,
            os.environ.get("IDENTITY_CA") if args.private else None,
        )
    except Exception as error:
        print(
            "Issuer smoke failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
