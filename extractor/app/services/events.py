from __future__ import annotations

from datetime import datetime, timezone
from typing import Any


def make_event(event_type: str, job_id: str, **payload: Any) -> dict[str, Any]:
    event = {
        "type": event_type,
        "job_id": job_id,
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }
    event.update(payload)
    return event
