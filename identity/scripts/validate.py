from pathlib import Path
import json, re
from provision import load_manifest

root = Path(__file__).resolve().parent.parent
for path in (root / "config/projects").glob("*.json"):
    load_manifest(path, json.loads(path.read_text())["environment"])
for line in (root / "versions.env").read_text().splitlines():
    if line and not line.startswith("#"):
        if not re.search(r"@sha256:[0-9a-f]{64}$", line):
            raise SystemExit("Unpinned image in versions.env.")
print("Identity manifests and image pins valid.")
