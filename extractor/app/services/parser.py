from __future__ import annotations

import re
from datetime import datetime, timezone
from typing import Any
from urllib.parse import parse_qs, unquote, urlparse

from app.models.lead import Lead
from app.utils.text import collapse_whitespace, unique_preserve_order

EMAIL_RE = re.compile(
    r"\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b",
    re.IGNORECASE,
)

_JUNK_EMAIL_PARTS = (
    "example.com",
    "email.com",
    "sentry.io",
    "wixpress.com",
    "cloudflare",
    "schema.org",
    "yourdomain",
    "domain.com",
    "png",
    "jpg",
    "jpeg",
    "webp",
    "svg",
)

_PLACE_ID_RE = re.compile(r"(0x[0-9a-f]+:0x[0-9a-f]+)", re.IGNORECASE)
_DATA_CID_RE = re.compile(r"!1s(0x[0-9a-f]+:0x[0-9a-f]+)", re.IGNORECASE)
_COORD_RE = re.compile(r"@(-?\d+\.\d+),(-?\d+\.\d+)")
_REVIEW_RE = re.compile(r"([\d,.]+)\s+reviews?", re.IGNORECASE)
_RATING_RE = re.compile(r"\b([1-5](?:\.\d)?)\b")


def parse_lead(raw: dict[str, Any]) -> Lead | None:
    """Build a Lead from a loosely-structured Maps scrape dict. Never fabricates."""
    if not isinstance(raw, dict):
        return None

    name = collapse_whitespace(_as_str(raw.get("business_name") or raw.get("name")))
    if not name:
        return None

    emails = extract_emails(raw.get("emails") or raw.get("email") or raw.get("text") or "")
    website = _clean_url(_as_str(raw.get("website")))
    maps_url = _clean_url(_as_str(raw.get("google_maps_url") or raw.get("url")))
    place_id = collapse_whitespace(_as_str(raw.get("place_id"))) or extract_place_id(maps_url)
    rating = _as_float(raw.get("rating"))
    review_count = _as_int(raw.get("review_count") or raw.get("reviews"))
    latitude = _as_float(raw.get("latitude") or raw.get("lat"))
    longitude = _as_float(raw.get("longitude") or raw.get("lng"))

    if maps_url and (latitude is None or longitude is None):
        coords = extract_coordinates(maps_url)
        latitude = latitude if latitude is not None else coords[0]
        longitude = longitude if longitude is not None else coords[1]

    city, country = infer_city_country(_as_str(raw.get("address")))
    source = collapse_whitespace(_as_str(raw.get("source"))) or "Google Maps"

    return Lead(
        business_name=name,
        address=collapse_whitespace(_as_str(raw.get("address"))),
        phone=collapse_whitespace(_as_str(raw.get("phone"))),
        emails=emails,
        website=website,
        google_maps_url=maps_url,
        place_id=place_id,
        category=collapse_whitespace(_as_str(raw.get("category"))),
        rating=rating,
        review_count=review_count,
        business_hours=collapse_whitespace(_as_str(raw.get("business_hours") or raw.get("hours"))),
        latitude=latitude,
        longitude=longitude,
        city=collapse_whitespace(_as_str(raw.get("city"))) or city,
        country=collapse_whitespace(_as_str(raw.get("country"))) or country,
        source=source,
        extracted_at=_as_str(raw.get("extracted_at"))
        or datetime.now(timezone.utc).isoformat(),
        metadata=raw.get("metadata") if isinstance(raw.get("metadata"), dict) else {},
    )


def extract_emails(value: Any) -> list[str]:
    if isinstance(value, list):
        text = " ".join(_as_str(item) or "" for item in value)
    else:
        text = _as_str(value) or ""

    found = [match.group(0) for match in EMAIL_RE.finditer(text)]
    cleaned: list[str] = []
    for email in found:
        lowered = email.lower()
        if any(part in lowered for part in _JUNK_EMAIL_PARTS):
            continue
        if lowered.endswith((".png", ".jpg", ".jpeg", ".gif", ".webp", ".svg")):
            continue
        cleaned.append(email)
    return unique_preserve_order(cleaned)


def extract_place_id(url: str | None) -> str | None:
    if not url:
        return None
    for pattern in (_DATA_CID_RE, _PLACE_ID_RE):
        match = pattern.search(url)
        if match:
            return match.group(1)
    parsed = urlparse(url)
    query = parse_qs(parsed.query)
    if "cid" in query:
        return collapse_whitespace(query["cid"][0])
    return None


def extract_coordinates(url: str | None) -> tuple[float | None, float | None]:
    if not url:
        return None, None
    match = _COORD_RE.search(unquote(url))
    if not match:
        return None, None
    return float(match.group(1)), float(match.group(2))


def infer_city_country(address: str | None) -> tuple[str | None, str | None]:
    if not address:
        return None, None
    parts = [part.strip() for part in address.split(",") if part.strip()]
    if not parts:
        return None, None
    if len(parts) == 1:
        return parts[0], None
    country = parts[-1]
    city = parts[-2] if len(parts) >= 2 else None
    if city and re.search(r"\d", city):
        city = parts[-3] if len(parts) >= 3 else None
    return city, country


def parse_rating_and_reviews(text: str | None) -> tuple[float | None, int | None]:
    if not text:
        return None, None
    rating = None
    reviews = None
    review_match = _REVIEW_RE.search(text)
    if review_match:
        reviews = _as_int(review_match.group(1))
    rating_match = _RATING_RE.search(text)
    if rating_match:
        rating = _as_float(rating_match.group(1))
    return rating, reviews


def _as_str(value: Any) -> str | None:
    if value is None:
        return None
    if isinstance(value, str):
        return value
    return str(value)


def _as_float(value: Any) -> float | None:
    if value is None or value == "":
        return None
    try:
        number = float(str(value).replace(",", ""))
    except (TypeError, ValueError):
        return None
    return number


def _as_int(value: Any) -> int | None:
    if value is None or value == "":
        return None
    try:
        return int(float(str(value).replace(",", "")))
    except (TypeError, ValueError):
        return None


def _clean_url(value: str | None) -> str | None:
    url = collapse_whitespace(value)
    if not url:
        return None
    if url.startswith("//"):
        url = "https:" + url
    if not re.match(r"^https?://", url, re.IGNORECASE):
        if "." in url and " " not in url:
            url = "https://" + url
        else:
            return None
    return url


class DuplicateTracker:
    def __init__(self) -> None:
        self._seen: set[str] = set()

    def is_duplicate(self, lead: Lead) -> bool:
        key = lead.identity_key()
        if key in self._seen:
            return True
        self._seen.add(key)
        return False

    def seen_count(self) -> int:
        return len(self._seen)
