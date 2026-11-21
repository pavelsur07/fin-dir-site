from pathlib import Path
import hashlib, pytest
from backup_archive import encrypt, decrypt


def test_authenticated_backup_preserves_secrets_and_rejects_corruption(tmp_path):
    runtime = tmp_path / "source"
    runtime.mkdir()
    (runtime / "db_password").write_text("synthetic-password")
    dump = runtime / "test.dump"
    dump.write_bytes(b"synthetic-database")
    archive = tmp_path / "recovery.enc"
    checksum = encrypt(runtime, dump, archive)
    restored = tmp_path / "restored"
    decrypt(archive, runtime / "backup_key", restored, checksum)
    assert (restored / "database.dump").read_bytes() == dump.read_bytes()
    assert (restored / "db_password").read_text() == "synthetic-password"
    assert archive.stat().st_mode & 0o777 == 0o600
    changed = bytearray(archive.read_bytes())
    changed[-1] ^= 1
    archive.write_bytes(changed)
    with pytest.raises(RuntimeError, match="checksum"):
        decrypt(archive, runtime / "backup_key", tmp_path / "corrupt", checksum)
    with pytest.raises(Exception):
        decrypt(
            archive,
            runtime / "backup_key",
            tmp_path / "forged",
            hashlib.sha256(changed).hexdigest(),
        )
