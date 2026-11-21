"""Bounded Admin API adapter. Never include response bodies/credentials in errors."""

from pathlib import Path
from urllib.parse import quote, urlsplit
import ssl, time
import httpx


class IdentityOperationError(RuntimeError):
    pass


class KeycloakAdmin:
    def __init__(self, public_url: str, admin_url: str, ca_file: str | None = None):
        for url in (public_url, admin_url):
            parsed = urlsplit(url)
            if (
                parsed.scheme != "https"
                or not parsed.hostname
                or parsed.username
                or parsed.password
                or parsed.query
                or parsed.fragment
                or parsed.path not in ("", "/")
            ):
                raise IdentityOperationError("Configured HTTPS base URL required.")
        self.public_url = public_url.rstrip("/")
        self.admin_url = admin_url.rstrip("/")
        self.http = httpx.Client(
            verify=ssl.create_default_context(cafile=ca_file),
            timeout=5,
            follow_redirects=False,
            trust_env=False,
        )
        self.token: str | None = None

    def login(self, client_id: str, secret_path: Path, realm: str = "master"):
        response = self.http.post(
            self.public_url
            + "/realms/"
            + segment(realm)
            + "/protocol/openid-connect/token",
            data={
                "grant_type": "client_credentials",
                "client_id": client_id,
                "client_secret": secret_path.read_text().strip(),
            },
        )
        if response.status_code != 200:
            raise IdentityOperationError(
                f"Admin authentication failed ({response.status_code})."
            )
        self.token = response.json()["access_token"]

    def request(self, method: str, path: str, body=None, statuses=(200, 201, 204)):
        if not path.startswith("/admin/realms") or self.token is None:
            raise IdentityOperationError("Authenticated realm operation required.")
        response = self.http.request(
            method,
            self.admin_url + path,
            json=body,
            headers={"Authorization": "Bearer " + self.token},
        )
        if response.status_code not in statuses:
            raise IdentityOperationError(
                f"Admin operation {method} failed ({response.status_code})."
            )
        return response.json() if response.content else None

    def wait_ready(self, seconds=90):
        deadline = time.monotonic() + seconds
        while time.monotonic() < deadline:
            try:
                if (
                    self.http.get(
                        self.public_url
                        + "/realms/master/.well-known/openid-configuration"
                    ).status_code
                    == 200
                ):
                    return
            except httpx.TransportError:
                pass
            time.sleep(1)
        raise IdentityOperationError("Issuer readiness deadline exceeded.")


def segment(value: str) -> str:
    return quote(value, safe="")
