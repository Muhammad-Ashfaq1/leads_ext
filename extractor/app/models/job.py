from __future__ import annotations

import asyncio
from datetime import datetime, timezone
from enum import StrEnum
from typing import Any

from pydantic import BaseModel, Field


class JobStatus(StrEnum):
    IDLE = "idle"
    STARTING = "starting"
    SEARCHING = "searching"
    EXTRACTING = "extracting"
    ENRICHING = "enriching"
    WAITING_FOR_HUMAN_VERIFICATION = "waiting_for_human_verification"
    COMPLETED = "completed"
    CANCELLED = "cancelled"
    ERROR = "error"
    VERIFICATION_TIMEOUT = "verification_timeout"
    BLOCKED = "blocked"


TERMINAL_STATUSES = {
    JobStatus.COMPLETED,
    JobStatus.CANCELLED,
    JobStatus.ERROR,
    JobStatus.VERIFICATION_TIMEOUT,
    JobStatus.BLOCKED,
}


class ExtractionJob(BaseModel):
    job_id: str
    prompt: str
    query: str
    limit: int = 100
    status: JobStatus = JobStatus.IDLE
    businesses_seen: int = 0
    leads_extracted: int = 0
    emails_found: int = 0
    websites_found: int = 0
    started_at: str | None = None
    completed_at: str | None = None
    error: str | None = None
    current_activity: str | None = None
    mode: str = "live"
    simulate_verification: bool = False
    cancel_requested: bool = False
    seen_identities: set[str] = Field(default_factory=set)
    leads: list[dict[str, Any]] = Field(default_factory=list)

    model_config = {"arbitrary_types_allowed": True}

    def mark_started(self) -> None:
        self.started_at = datetime.now(timezone.utc).isoformat()
        self.status = JobStatus.STARTING

    def mark_finished(self, status: JobStatus, error: str | None = None) -> None:
        self.status = status
        self.error = error
        self.completed_at = datetime.now(timezone.utc).isoformat()

    def is_terminal(self) -> bool:
        return self.status in TERMINAL_STATUSES

    def should_stop(self) -> bool:
        return self.cancel_requested or self.is_terminal()

    def public_dict(self) -> dict[str, Any]:
        return {
            "job_id": self.job_id,
            "status": self.status.value,
            "prompt": self.prompt,
            "query": self.query,
            "limit": self.limit,
            "businesses_seen": self.businesses_seen,
            "leads_extracted": self.leads_extracted,
            "emails_found": self.emails_found,
            "websites_found": self.websites_found,
            "started_at": self.started_at,
            "completed_at": self.completed_at,
            "error": self.error,
            "current_activity": self.current_activity,
            "mode": self.mode,
            "cancel_requested": self.cancel_requested,
        }


class JobRuntime:
    """Mutable runtime bag that is not serialized onto the job model."""

    def __init__(self, job: ExtractionJob) -> None:
        self.job = job
        self.events: asyncio.Queue[dict[str, Any]] = asyncio.Queue()
        self.cancel_event = asyncio.Event()
        self.browser = None
        self.context = None
        self.page = None
        self.task: asyncio.Task | None = None
        self.mock_verification_completed = False
