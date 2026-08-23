from __future__ import annotations

import ipaddress
import socket
from urllib.parse import urlparse

_BLOCKED_HOSTS = {
    "localhost",
    "metadata.google.internal",
    "metadata.google.internal.",
}


def is_safe_public_url(url: str | None) -> bool:
    if not url:
        return False

    parsed = urlparse(url.strip())
    if parsed.scheme not in {"http", "https"}:
        return False
    if not parsed.hostname:
        return False
    if parsed.username or parsed.password:
        return False

    host = parsed.hostname.rstrip(".").lower()
    if host in _BLOCKED_HOSTS or host.endswith(".localhost"):
        return False

    if _is_blocked_ip(host):
        return False

    try:
        infos = socket.getaddrinfo(host, None)
    except OSError:
        return False

    for info in infos:
        address = info[4][0]
        if _is_blocked_ip(address):
            return False

    return True


def _is_blocked_ip(value: str) -> bool:
    try:
        ip = ipaddress.ip_address(value)
    except ValueError:
        return False

    return bool(
        ip.is_private
        or ip.is_loopback
        or ip.is_link_local
        or ip.is_multicast
        or ip.is_reserved
        or ip.is_unspecified
        or ip in ipaddress.ip_network("169.254.0.0/16")
    )
