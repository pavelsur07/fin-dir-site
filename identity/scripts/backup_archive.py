"""Authenticated encrypted recovery archive; key delivered separately from the backup."""

from pathlib import Path, PurePosixPath
import argparse, hashlib, io, json, os, tarfile
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

FIXED = {
    "database.dump",
    "db_password",
    "postgres_password",
    "bootstrap_secret",
    "provisioner_secret",
    "operator_initial_password",
    "ca.crt",
    "private-ca.key",
    "tls/server.crt",
    "tls/server.key",
    "tls/ingress.json",
}


def allowed(name):
    path = PurePosixPath(name)
    return (
        not path.is_absolute()
        and ".." not in path.parts
        and (name in FIXED or (len(path.parts) == 2 and path.parts[0] == "clients"))
    )


def encrypt(runtime, dump, output):
    keypath = runtime / "backup_key"
    if keypath.is_symlink():
        raise RuntimeError("Key symlink refused.")
    if not keypath.exists():
        fd = os.open(keypath, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
        with os.fdopen(fd, "wb") as target:
            target.write(AESGCM.generate_key(bit_length=256))
    payload = io.BytesIO()
    with tarfile.open(fileobj=payload, mode="w") as archive:
        if dump.stat().st_size > 128 * 1024 * 1024:
            raise RuntimeError("Logical backup exceeds MVP archive size limit.")
        archive.add(dump, arcname="database.dump", recursive=False)
        for name in sorted(FIXED - {"database.dump"}):
            source = runtime / name
            if source.is_symlink():
                raise RuntimeError("Recovery symlink refused.")
            if source.is_file():
                archive.add(source, arcname=name, recursive=False)
        clients = runtime / "clients"
        if clients.exists():
            if clients.is_symlink():
                raise RuntimeError("Client symlink refused.")
            for source in sorted(clients.iterdir()):
                if source.is_symlink() or not source.is_file():
                    raise RuntimeError("Unexpected client credential entry.")
                archive.add(source, arcname="clients/" + source.name, recursive=False)
    nonce = os.urandom(12)
    ciphertext = (
        b"IDB1"
        + nonce
        + AESGCM(keypath.read_bytes()).encrypt(
            nonce, payload.getvalue(), b"identity-recovery-v1"
        )
    )
    output.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    fd = os.open(output, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(fd, "wb") as target:
        target.write(ciphertext)
    return hashlib.sha256(ciphertext).hexdigest()


def decrypt(archive, key, destination, expected):
    if archive.stat().st_size > 256 * 1024 * 1024:
        raise RuntimeError("Recovery archive exceeds size limit.")
    with archive.open("rb") as source:
        raw = source.read(256 * 1024 * 1024 + 1)
    if (
        len(raw) > 256 * 1024 * 1024
        or hashlib.sha256(raw).hexdigest() != expected
        or raw[:4] != b"IDB1"
    ):
        raise RuntimeError("Recovery archive checksum/format rejected.")
    plaintext = AESGCM(key.read_bytes()).decrypt(
        raw[4:16], raw[16:], b"identity-recovery-v1"
    )
    destination.mkdir(mode=0o700, parents=True, exist_ok=False)
    with tarfile.open(fileobj=io.BytesIO(plaintext), mode="r:") as entries:
        seen = set()
        for entry in entries:
            if (
                not entry.isfile()
                or not allowed(entry.name)
                or entry.name in seen
                or entry.size > 128 * 1024 * 1024
            ):
                raise RuntimeError("Recovery archive entry rejected.")
            seen.add(entry.name)
            target = destination / entry.name
            target.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
            fd = os.open(target, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
            with os.fdopen(fd, "wb") as output:
                output.write(entries.extractfile(entry).read())
    if "database.dump" not in seen:
        raise RuntimeError("Database missing in recovery archive.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("operation", choices=["encrypt", "decrypt"])
    parser.add_argument("--runtime", type=Path, default=Path("/runtime"))
    parser.add_argument("--archive", type=Path, required=True)
    parser.add_argument("--dump", type=Path)
    parser.add_argument("--destination", type=Path)
    parser.add_argument("--sha256")
    args = parser.parse_args()
    try:
        if args.operation == "encrypt":
            print(encrypt(args.runtime, args.dump, args.archive))
        else:
            decrypt(
                args.archive, args.runtime / "backup_key", args.destination, args.sha256
            )
            print("Authenticated backup decrypted into an isolated recovery directory.")
    except Exception as error:
        print(
            "Recovery archive operation failed: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
