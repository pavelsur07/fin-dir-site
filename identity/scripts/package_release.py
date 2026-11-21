"""Package an exact git tree; verify before extracting into a new release directory."""

from pathlib import Path, PurePosixPath
import argparse, gzip, hashlib, io, json, re, subprocess, tarfile


def safe(name):
    p = PurePosixPath(name)
    return (
        bool(name)
        and bool(p.parts)
        and not p.is_absolute()
        and ".." not in p.parts
        and p.parts[0] not in {".runtime", "site", "conwix", ".git"}
        and not any(
            part.startswith(".env") and part != ".env.example" for part in p.parts
        )
        and p.suffix not in {".dump", ".key", ".pem", ".enc"}
    )


def package(repository, revision, output):
    if not re.fullmatch("[0-9a-f]{40}", revision):
        raise RuntimeError("Exact git SHA required.")
    actual = subprocess.check_output(
        ["git", "rev-parse", revision + "^{commit}"], cwd=repository, text=True
    ).strip()
    if actual != revision:
        raise RuntimeError("Revision mismatch.")
    entries = subprocess.check_output(
        ["git", "ls-tree", "-rz", revision, "--", "identity/"], cwd=repository
    ).split(b"\0")
    files = {}
    for entry in entries:
        if not entry:
            continue
        metadata, rawpath = entry.split(b"\t", 1)
        mode, kind, oid = metadata.decode().split()
        name = rawpath.decode().removeprefix("identity/")
        if kind != "blob" or mode not in {"100644", "100755"} or not safe(name):
            raise RuntimeError("Unsafe release entry.")
        content = subprocess.check_output(
            ["git", "cat-file", "blob", oid], cwd=repository
        )
        files[name] = (content, 0o755 if mode == "100755" else 0o644)
    if (
        not {
            "Makefile",
            "compose.yaml",
            "compose.prod.yaml",
            "versions.env",
            "scripts/provision.py",
        }
        <= files.keys()
    ):
        raise RuntimeError("Incomplete Identity tree.")
    manifest = {
        "schemaVersion": 1,
        "revision": revision,
        "files": {
            name: hashlib.sha256(content).hexdigest()
            for name, (content, _) in files.items()
        },
    }
    files["release-manifest.json"] = (
        json.dumps(manifest, sort_keys=True, indent=2).encode(),
        0o644,
    )
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open("xb") as raw, tarfile.open(fileobj=raw, mode="w:gz") as archive:
        for name, (content, mode) in sorted(files.items()):
            info = tarfile.TarInfo(name)
            info.size = len(content)
            info.mode = mode
            info.mtime = 0
            archive.addfile(info, io.BytesIO(content))
    return manifest


def verify(archive, revision, destination=None, expected_tree=None):
    files = {}
    modes = {}
    total = 0
    # Bound the complete expanded stream before tarfile parses metadata headers.
    with gzip.open(archive, "rb") as compressed:
        expanded = compressed.read(16 * 1024 * 1024 + 1)
    if len(expanded) > 16 * 1024 * 1024:
        raise RuntimeError("Package aggregate size limit exceeded.")
    with tarfile.open(fileobj=io.BytesIO(expanded), mode="r:") as source:
        for entry in source:
            total += entry.size
            if total > 16 * 1024 * 1024 or entry.size < 0:
                raise RuntimeError("Package aggregate size limit exceeded.")
            if (
                not entry.isfile()
                or not safe(entry.name)
                or entry.name in files
                or entry.size > 8 * 1024 * 1024
                or len(files) > 500
            ):
                raise RuntimeError("Unsafe package entry.")
            if expected_tree is not None and entry.name != "release-manifest.json":
                expected = expected_tree.get(entry.name)
                if (
                    expected is None
                    or entry.size != expected["size"]
                    or bool(entry.mode & 0o111) != (expected["mode"] == "100755")
                ):
                    raise RuntimeError("Package differs from canonical Git tree.")
            files[entry.name] = source.extractfile(entry).read()
            modes[entry.name] = entry.mode
    manifest = json.loads(files.pop("release-manifest.json"))
    if manifest.get("schemaVersion") != 1 or manifest.get("revision") != revision:
        raise RuntimeError("Release manifest revision mismatch.")
    if {
        name: hashlib.sha256(content).hexdigest() for name, content in files.items()
    } != manifest["files"]:
        raise RuntimeError("Release package checksum mismatch.")
    if expected_tree is not None:
        if files.keys() != expected_tree.keys():
            raise RuntimeError("Incomplete canonical Git tree.")
        for name, content in files.items():
            oid = hashlib.sha1(
                b"blob " + str(len(content)).encode() + b"\0" + content
            ).hexdigest()
            if oid != expected_tree[name]["sha"]:
                raise RuntimeError("Package content differs from canonical Git tree.")
    if destination is not None:
        destination.mkdir(mode=0o755, parents=True, exist_ok=False)
        for name, content in files.items():
            target = destination / name
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(content)
            target.chmod(0o755 if modes[name] & 0o111 else 0o644)
        (destination / "release-manifest.json").write_text(
            json.dumps(manifest, sort_keys=True, indent=2)
        )
    return manifest


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--revision", required=True)
    parser.add_argument("--output", type=Path)
    parser.add_argument("--verify", type=Path)
    parser.add_argument("--destination", type=Path)
    args = parser.parse_args()
    try:
        if args.verify:
            verify(args.verify, args.revision, args.destination)
        else:
            package(Path(__file__).resolve().parents[2], args.revision, args.output)
        print("Identity-only release package checks passed.")
    except Exception as error:
        print(
            "Release package rejected: " + type(error).__name__,
            file=__import__("sys").stderr,
        )
        raise SystemExit(1)
