from datetime import datetime, timezone
from typing import Any

from pydantic import BaseModel, Field


class Lead(BaseModel):
    business_name: str | None = None
    address: str | None = None
    phone: str | None = None
    emails: list[str] = Field(default_factory=list)
    website: str | None = None
    google_maps_url: str | None = None
    place_id: str | None = None
    category: str | None = None
    rating: float | None = None
    review_count: int | None = None
    business_hours: str | None = None
    latitude: float | None = None
    longitude: float | None = None
    city: str | None = None
    country: str | None = None
    source: str = "Google Maps"
    extracted_at: str = Field(
        default_factory=lambda: datetime.now(timezone.utc).isoformat()
    )
    metadata: dict[str, Any] = Field(default_factory=dict)

    def identity_key(self) -> str:
        if self.place_id:
            return f"place:{self.place_id.strip().lower()}"
        name = _normalize_text(self.business_name)
        address = _normalize_text(self.address)
        if name:
            return f"nameaddr:{name}|{address}"
        if self.google_maps_url:
            return f"url:{self.google_maps_url.strip().lower()}"
        return f"anon:{id(self)}"


def _normalize_text(value: str | None) -> str:
    if not value:
        return ""
    return " ".join(value.casefold().split())
