from __future__ import annotations

import re
from urllib.parse import quote_plus

_PREFIXES = (
    r"please\s+",
    r"can you\s+",
    r"could you\s+",
    r"i want to\s+",
    r"i'd like to\s+",
    r"id like to\s+",
    r"help me\s+",
)

_ACTION_WORDS = (
    r"find(?:\s+me)?",
    r"search(?:\s+for)?",
    r"look(?:\s+for)?",
    r"get(?:\s+me)?",
    r"extract",
    r"scrape",
    r"list",
    r"show(?:\s+me)?",
    r"locate",
)


def normalize_search_query(prompt: str) -> str:
    """Turn a natural-language prompt into a Google Maps search query."""
    query = (prompt or "").strip()
    if not query:
        return ""

    query = re.sub(r"\s+", " ", query)
    lowered = query
    for prefix in _PREFIXES:
        lowered = re.sub(rf"^{prefix}", "", lowered, flags=re.IGNORECASE)

    lowered = re.sub(
        rf"^({'|'.join(_ACTION_WORDS)})\s+",
        "",
        lowered,
        flags=re.IGNORECASE,
    )

    lowered = re.sub(
        r"\s+with\s+(phone\s+numbers?|emails?|websites?|contact\s+details?)(?:\s+and\s+(phone\s+numbers?|emails?|websites?|contact\s+details?))*\s*$",
        "",
        lowered,
        flags=re.IGNORECASE,
    )

    return re.sub(r"\s+", " ", lowered).strip() or query.strip()


def maps_search_url(query: str, maps_url: str = "https://www.google.com/maps") -> str:
    """Build a Google Maps search URL so we do not have to type into the omnibox."""
    cleaned = normalize_search_query(query) or (query or "").strip()
    if not cleaned:
        return maps_url.rstrip("/")
    return f"{maps_url.rstrip('/')}/search/{quote_plus(cleaned)}"
