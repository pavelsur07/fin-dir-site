import io, json, tarfile, pytest
from pathlib import Path
from package_release import verify


def archive(tmp_path, name, entries):
    path = tmp_path / name
    with tarfile.open(path, "w:gz") as out:
        for filename, content, kind in entries:
            info = tarfile.TarInfo(filename)
            info.type = kind
            info.size = len(content) if kind == tarfile.REGTYPE else 0
            out.addfile(info, io.BytesIO(content) if kind == tarfile.REGTYPE else None)
    return path


@pytest.mark.parametrize(
    "filename,kind",
    [
        ("../escape", tarfile.REGTYPE),
        ("/absolute", tarfile.REGTYPE),
        ("site/config", tarfile.REGTYPE),
        (".runtime/secret", tarfile.REGTYPE),
        ("config/link", tarfile.SYMTYPE),
    ],
)
def test_unsafe_package_rejected_before_extraction(tmp_path, filename, kind):
    path = archive(tmp_path, "unsafe.tar.gz", [(filename, b"unsafe", kind)])
    with pytest.raises(RuntimeError, match="Unsafe"):
        verify(path, "a" * 40, tmp_path / "extracted")
    assert not (tmp_path / "extracted").exists()


def test_changed_package_checksum_fails(tmp_path):
    manifest = {
        "schemaVersion": 1,
        "revision": "a" * 40,
        "files": {"Makefile": "0" * 64},
    }
    path = archive(
        tmp_path,
        "corrupt.tar.gz",
        [
            ("release-manifest.json", json.dumps(manifest).encode(), tarfile.REGTYPE),
            ("Makefile", b"changed", tarfile.REGTYPE),
        ],
    )
    with pytest.raises(RuntimeError, match="checksum"):
        verify(path, "a" * 40)


def test_self_consistent_forgery_is_rejected_by_canonical_git_tree(tmp_path):
    import hashlib

    original = b"good"
    malicious = b"evil"
    manifest = {
        "schemaVersion": 1,
        "revision": "a" * 40,
        "files": {"Makefile": hashlib.sha256(malicious).hexdigest()},
    }
    package = archive(
        tmp_path,
        "forged.tar.gz",
        [
            ("release-manifest.json", json.dumps(manifest).encode(), tarfile.REGTYPE),
            ("Makefile", malicious, tarfile.REGTYPE),
        ],
    )
    expected = {
        "Makefile": {
            "mode": "100644",
            "size": len(original),
            "sha": hashlib.sha1(b"blob 4\0" + original).hexdigest(),
        }
    }
    with pytest.raises(RuntimeError, match="canonical Git tree"):
        verify(package, "a" * 40, tmp_path / "extracted", expected)
    assert not (tmp_path / "extracted").exists()


def test_aggregate_expansion_limit_rejects_small_compressed_bomb(tmp_path):
    content = b"x" * (8 * 1024 * 1024)
    package = archive(
        tmp_path,
        "expansion.tar.gz",
        [(name, content, tarfile.REGTYPE) for name in ["one", "two", "three"]],
    )
    assert package.stat().st_size < 100000
    with pytest.raises(RuntimeError, match="aggregate size"):
        verify(package, "a" * 40)


def test_expanded_metadata_is_bounded_before_tar_parser(tmp_path):
    path = tmp_path / "large-metadata.tar.gz"
    with tarfile.open(path, "w:gz", format=tarfile.PAX_FORMAT) as out:
        info = tarfile.TarInfo("Makefile")
        info.size = 4
        info.pax_headers = {"comment": "x" * (20 * 1024 * 1024)}
        out.addfile(info, io.BytesIO(b"good"))
    assert path.stat().st_size < 100000
    with pytest.raises(RuntimeError, match="aggregate size"):
        verify(path, "a" * 40)
