"""Initialize protected runtime; private operator TLS is never a public trust override."""

from pathlib import Path
from datetime import datetime, timedelta, timezone
from urllib.parse import urlsplit
import argparse, json, os, secrets
from cryptography import x509
from cryptography.x509.oid import NameOID
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import rsa


def initialize(
    directory: Path, production=False, public_host=None, admin_host=None
) -> None:
    if directory.is_symlink():
        raise RuntimeError("Runtime symlink refused.")
    directory.mkdir(parents=True, exist_ok=True, mode=0o700)
    os.chmod(directory, 0o700)
    for name in ["db_password", "postgres_password", "bootstrap_secret"]:
        path = directory / name
        if path.is_symlink():
            raise RuntimeError("Secret symlink refused.")
        if not path.exists():
            fd = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o644)
            with os.fdopen(fd, "w") as output:
                output.write(secrets.token_urlsafe(40))
    public = public_host or ("auth.vashfindir.ru" if production else "identity.test")
    admin = admin_host or (
        "auth-admin.vashfindir.ru" if production else "identity-admin.test"
    )
    import re

    if any(not re.fullmatch("[a-z0-9][a-z0-9.-]+", host) for host in [public, admin]):
        raise RuntimeError("Invalid private TLS hostname.")
    operator = "10.254.235.6/32" if production else "10.254.236.6/32"
    tls = directory / "tls"
    tls.mkdir(mode=0o700, exist_ok=True)
    if not (tls / "server.crt").exists():
        if (tls / "server.key").exists() or (directory / "private-ca.key").exists():
            raise RuntimeError("Incomplete TLS material requires explicit recovery.")
        now = datetime.now(timezone.utc)
        rootkey = rsa.generate_private_key(public_exponent=65537, key_size=2048)
        rootname = x509.Name(
            [x509.NameAttribute(NameOID.COMMON_NAME, "Identity private operator CA")]
        )
        ca = (
            x509.CertificateBuilder()
            .subject_name(rootname)
            .issuer_name(rootname)
            .public_key(rootkey.public_key())
            .serial_number(x509.random_serial_number())
            .not_valid_before(now - timedelta(minutes=1))
            .not_valid_after(now + timedelta(days=3650 if production else 2))
            .add_extension(
                x509.SubjectKeyIdentifier.from_public_key(rootkey.public_key()),
                critical=False,
            )
            .add_extension(x509.BasicConstraints(ca=True, path_length=0), critical=True)
            .add_extension(
                x509.KeyUsage(
                    True, False, False, False, False, True, True, False, False
                ),
                critical=True,
            )
            .sign(rootkey, hashes.SHA256())
        )
        key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
        leaf = (
            x509.CertificateBuilder()
            .subject_name(x509.Name([x509.NameAttribute(NameOID.COMMON_NAME, public)]))
            .issuer_name(rootname)
            .public_key(key.public_key())
            .serial_number(x509.random_serial_number())
            .not_valid_before(now - timedelta(minutes=1))
            .not_valid_after(now + timedelta(days=365 if production else 2))
            .add_extension(
                x509.AuthorityKeyIdentifier.from_issuer_public_key(
                    rootkey.public_key()
                ),
                critical=False,
            )
            .add_extension(
                x509.SubjectKeyIdentifier.from_public_key(key.public_key()),
                critical=False,
            )
            .add_extension(
                x509.BasicConstraints(ca=False, path_length=None), critical=True
            )
            .add_extension(
                x509.SubjectAlternativeName(
                    [x509.DNSName(public), x509.DNSName(admin)]
                ),
                critical=False,
            )
            .add_extension(
                x509.ExtendedKeyUsage([x509.oid.ExtendedKeyUsageOID.SERVER_AUTH]),
                critical=False,
            )
            .sign(rootkey, hashes.SHA256())
        )
        for path, value, mode in [
            (directory / "ca.crt", ca.public_bytes(serialization.Encoding.PEM), 0o644),
            (
                directory / "private-ca.key",
                rootkey.private_bytes(
                    serialization.Encoding.PEM,
                    serialization.PrivateFormat.PKCS8,
                    serialization.NoEncryption(),
                ),
                0o600,
            ),
            (tls / "server.crt", leaf.public_bytes(serialization.Encoding.PEM), 0o644),
            (
                tls / "server.key",
                key.private_bytes(
                    serialization.Encoding.PEM,
                    serialization.PrivateFormat.PKCS8,
                    serialization.NoEncryption(),
                ),
                0o600,
            ),
        ]:
            fd = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, mode)
            with os.fdopen(fd, "wb") as output:
                output.write(value)

    def route(rule, entry, middlewares=None, priority=None):
        result = {"rule": rule, "entryPoints": [entry], "service": "keycloak"}
        if entry == "websecure":
            result["tls"] = {}
        if middlewares:
            result["middlewares"] = middlewares
        if priority:
            result["priority"] = priority
        return result

    paths = [
        "/realms/projects/.well-known/openid-configuration",
        "/realms/projects/protocol/openid-connect/token",
        "/realms/projects/protocol/openid-connect/certs",
    ]
    config = {
        "http": {
            "routers": {
                "private-admin": route(
                    "Host(`" + admin + "`)", "websecure", ["admin-only"]
                ),
                "private-public-admin-deny": route(
                    "Host(`" + public + "`) && PathPrefix(`/admin`)",
                    "websecure",
                    ["deny-all"],
                    100,
                ),
                "private-public": route("Host(`" + public + "`)", "websecure"),
                "public-oauth": route(
                    "Host(`"
                    + public
                    + "`) && ("
                    + " || ".join("Path(`" + p + "`)" for p in paths)
                    + ")",
                    "public",
                    ["public-https", "request-size", "oauth-rate"],
                ),
            },
            "services": {
                "keycloak": {
                    "loadBalancer": {"servers": [{"url": "http://keycloak:8080"}]}
                }
            },
            "middlewares": {
                "admin-only": {
                    "ipAllowList": {
                        "sourceRange": [
                            operator,
                            "10.254.235.1/32" if production else "10.254.236.1/32",
                        ]
                    }
                },
                "deny-all": {"ipAllowList": {"sourceRange": ["192.0.2.255/32"]}},
                "public-https": {
                    "headers": {"customRequestHeaders": {"X-Forwarded-Proto": "https"}}
                },
                "request-size": {"buffering": {"maxRequestBodyBytes": 16384}},
                "oauth-rate": {"rateLimit": {"average": 10, "burst": 30}},
            },
        },
        "tls": {
            "certificates": [
                {"certFile": "/tls/server.crt", "keyFile": "/tls/server.key"}
            ]
        },
    }
    (tls / "ingress.json").write_text(json.dumps(config))
    print("Private runtime initialized; credentials not printed.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("directory", type=Path)
    parser.add_argument("--production", action="store_true")
    parser.add_argument("--public-host")
    parser.add_argument("--admin-host")
    args = parser.parse_args()
    initialize(args.directory, args.production, args.public_host, args.admin_host)
