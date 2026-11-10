"""Create a private Docmost environment without overwriting existing secrets."""

import os
from pathlib import Path
import secrets
import sys


destination = Path(sys.argv[1])
template = Path(__file__).resolve().parent.parent / "docmost/.env.example"
content = template.read_text().replace(
    "DOCMOST_APP_SECRET=\n", f"DOCMOST_APP_SECRET={secrets.token_hex(32)}\n"
).replace(
    "DOCMOST_DB_PASSWORD=\n", f"DOCMOST_DB_PASSWORD={secrets.token_hex(32)}\n"
)
destination.parent.mkdir(parents=True, exist_ok=True)
try:
    descriptor = os.open(destination, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
except FileExistsError:
    sys.exit(f"{destination} already exists; existing secrets were preserved.")
with os.fdopen(descriptor, "w") as output:
    output.write(content)
print(f"Created {destination} (0600); public access is disabled.")
