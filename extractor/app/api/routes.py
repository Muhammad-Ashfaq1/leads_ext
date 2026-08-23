from __future__ import annotations

import json
import logging
from typing import Any

from fastapi import APIRouter, HTTPException
from fastapi.responses import StreamingResponse
from pydantic import BaseModel, Field

from app.config import get_settings
from app.models.job import JobStatus
from app.services.job_manager import job_manager
from app.services.mock_runner import complete_mock_verification

logger = logging.getLogger(__name__)
router = APIRouter()


class StartRequest(BaseModel):
    prompt: str = Field(..., min_length=2, max_length=500)
    limit: int = Field(default=100, ge=1, le=1000)
    mode: str = Field(default="live")
    simulate_verification: bool = False


@router.get("/health")
async def health() -> dict[str, Any]:
    return {"ok": True, "service": "awt-phone-extractor"}


@router.post("/jobs")
async def start_job(payload: StartRequest) -> dict[str, Any]:
    settings = get_settings()
    mode = payload.mode if payload.mode in {"live", "mock"} else "live"
    if mode == "mock" and not settings.allow_mock:
        raise HTTPException(status_code=403, detail="Mock extraction is disabled.")
    try:
        job = await job_manager.create(
            prompt=payload.prompt,
            limit=payload.limit,
            mode=mode,
            simulate_verification=payload.simulate_verification,
        )
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    await job_manager.start(job.job_id)
    return {"job_id": job.job_id, "query": job.query, "status": job.status.value}


@router.get("/jobs/{job_id}")
async def job_status(job_id: str) -> dict[str, Any]:
    runtime = job_manager.get(job_id)
    if runtime is None:
        raise HTTPException(status_code=404, detail="Unknown extraction job.")
    return runtime.job.public_dict()


@router.get("/jobs/{job_id}/leads")
async def job_leads(job_id: str) -> dict[str, Any]:
    runtime = job_manager.get(job_id)
    if runtime is None:
        raise HTTPException(status_code=404, detail="Unknown extraction job.")
    return {"job_id": job_id, "leads": runtime.job.leads}


@router.post("/jobs/{job_id}/stop")
async def stop_job(job_id: str) -> dict[str, Any]:
    try:
        job = await job_manager.cancel(job_id)
    except KeyError as exc:
        raise HTTPException(status_code=404, detail="Unknown extraction job.") from exc
    return job.public_dict()


@router.post("/jobs/{job_id}/focus")
async def focus_job(job_id: str) -> dict[str, Any]:
    try:
        focused = await job_manager.focus_browser(job_id)
    except KeyError as exc:
        raise HTTPException(status_code=404, detail="Unknown extraction job.") from exc
    return {"ok": focused}


@router.post("/jobs/{job_id}/verify-complete")
async def verify_complete(job_id: str) -> dict[str, Any]:
    settings = get_settings()
    if not settings.allow_mock:
        raise HTTPException(status_code=403, detail="Mock verification is disabled.")
    runtime = job_manager.get(job_id)
    if runtime is None:
        raise HTTPException(status_code=404, detail="Unknown extraction job.")
    if runtime.job.status != JobStatus.WAITING_FOR_HUMAN_VERIFICATION:
        raise HTTPException(status_code=409, detail="Job is not waiting for verification.")
    complete_mock_verification(runtime)
    return {"ok": True, "job_id": job_id}


@router.get("/jobs/{job_id}/events")
async def job_events(job_id: str) -> StreamingResponse:
    runtime = job_manager.get(job_id)
    if runtime is None:
        raise HTTPException(status_code=404, detail="Unknown extraction job.")

    async def generate():
        try:
            async for event in job_manager.iter_events(job_id):
                yield f"data: {json.dumps(event)}\n\n"
        except Exception as exc:
            logger.exception("stream error %s", job_id)
            yield f"data: {json.dumps({'type': 'error', 'job_id': job_id, 'message': str(exc)})}\n\n"

    return StreamingResponse(
        generate(),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache, no-transform",
            "Connection": "keep-alive",
            "X-Accel-Buffering": "no",
        },
    )
