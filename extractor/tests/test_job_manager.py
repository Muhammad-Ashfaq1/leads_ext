from __future__ import annotations

import asyncio

import pytest

from app.config import Settings
from app.models.job import JobStatus
from app.services.job_manager import JobManager


@pytest.fixture
def manager() -> JobManager:
    return JobManager(Settings(allow_mock=True, human_verification_timeout=2))


@pytest.mark.asyncio
async def test_job_lifecycle_and_leads(manager: JobManager):
    job = await manager.create("Find dentists in Lahore", limit=3, mode="mock")
    await manager.start(job.job_id)
    events = []
    async for event in manager.iter_events(job.job_id):
        events.append(event)
    types = [event["type"] for event in events]
    assert types[0] == "started"
    assert "searching" in types
    assert types.count("lead") == 3
    assert types[-1] == "completed"
    runtime = manager.require(job.job_id)
    assert runtime.job.status == JobStatus.COMPLETED
    assert runtime.job.leads_extracted == 3


@pytest.mark.asyncio
async def test_cancellation_preserves_leads(manager: JobManager):
    job = await manager.create("Find dentists in Lahore", limit=20, mode="mock")
    await manager.start(job.job_id)
    await asyncio.sleep(0.45)
    await manager.cancel(job.job_id)
    events = []
    async for event in manager.iter_events(job.job_id):
        events.append(event)
    assert events[-1]["type"] == "cancelled"
    runtime = manager.require(job.job_id)
    assert runtime.job.status == JobStatus.CANCELLED
    assert runtime.job.leads_extracted >= 1
    assert runtime.job.leads


@pytest.mark.asyncio
async def test_human_verification_resume(manager: JobManager):
    job = await manager.create(
        "Find dentists in Lahore",
        limit=5,
        mode="mock",
        simulate_verification=True,
    )
    await manager.start(job.job_id)
    saw_verification = False
    saw_resume = False
    lead_after_resume = 0
    resumed = False
    async for event in manager.iter_events(job.job_id):
        if event["type"] == "human_verification_required":
            saw_verification = True
            runtime = manager.require(job.job_id)
            assert runtime.job.status == JobStatus.WAITING_FOR_HUMAN_VERIFICATION
            assert runtime.job.leads_extracted >= 1
            runtime.mock_verification_completed = True
            resumed = True
        if event["type"] == "verification_completed":
            saw_resume = True
        if resumed and event["type"] == "lead":
            lead_after_resume += 1
    assert saw_verification
    assert saw_resume
    assert lead_after_resume >= 1
    assert manager.require(job.job_id).job.status == JobStatus.COMPLETED


@pytest.mark.asyncio
async def test_verification_timeout(manager: JobManager):
    manager.settings.human_verification_timeout = 1
    job = await manager.create(
        "Find dentists in Lahore",
        limit=8,
        mode="mock",
        simulate_verification=True,
    )
    await manager.start(job.job_id)
    events = []
    async for event in manager.iter_events(job.job_id):
        events.append(event)
    assert events[-1]["type"] == "verification_timeout"
    runtime = manager.require(job.job_id)
    assert runtime.job.status == JobStatus.VERIFICATION_TIMEOUT
    assert runtime.job.leads_extracted >= 1
