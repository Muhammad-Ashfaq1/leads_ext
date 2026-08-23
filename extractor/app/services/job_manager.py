from __future__ import annotations

import asyncio
import logging
import uuid
from typing import Any

from app.config import Settings, get_settings
from app.models.job import ExtractionJob, JobRuntime, JobStatus
from app.services.events import make_event
from app.utils.prompt import normalize_search_query

logger = logging.getLogger(__name__)


class JobManager:
    def __init__(self, settings: Settings | None = None) -> None:
        self.settings = settings or get_settings()
        self._jobs: dict[str, JobRuntime] = {}
        self._lock = asyncio.Lock()

    def get(self, job_id: str) -> JobRuntime | None:
        return self._jobs.get(job_id)

    def require(self, job_id: str) -> JobRuntime:
        runtime = self.get(job_id)
        if runtime is None:
            raise KeyError(job_id)
        return runtime

    async def create(
        self,
        prompt: str,
        limit: int,
        mode: str = "live",
        simulate_verification: bool = False,
    ) -> ExtractionJob:
        query = normalize_search_query(prompt)
        if not query:
            raise ValueError("Prompt did not produce a usable search query.")

        limit = max(self.settings.min_limit, min(int(limit), self.settings.max_limit))
        if mode == "mock" and not self.settings.allow_mock:
            raise PermissionError("Mock extraction is disabled.")

        job = ExtractionJob(
            job_id=str(uuid.uuid4()),
            prompt=prompt.strip(),
            query=query,
            limit=limit,
            mode=mode,
            simulate_verification=simulate_verification and self.settings.allow_mock,
        )
        runtime = JobRuntime(job)
        async with self._lock:
            self._jobs[job.job_id] = runtime
        logger.info("job created %s query=%s limit=%s mode=%s", job.job_id, query, limit, mode)
        return job

    async def start(self, job_id: str) -> None:
        runtime = self.require(job_id)
        if runtime.task and not runtime.task.done():
            return
        runtime.job.mark_started()
        await self.emit(
            runtime,
            make_event(
                "started",
                job_id,
                status=runtime.job.status.value,
                query=runtime.job.query,
                message="Extraction started.",
            ),
        )
        logger.info("job started %s", job_id)
        if runtime.job.mode == "mock":
            from app.services.mock_runner import run_mock_job

            runtime.task = asyncio.create_task(run_mock_job(self, runtime), name=f"extract-{job_id}")
        else:
            from app.services.google_maps import GoogleMapsExtractor

            extractor = GoogleMapsExtractor(self, self.settings)
            runtime.task = asyncio.create_task(extractor.run(runtime), name=f"extract-{job_id}")

    async def cancel(self, job_id: str) -> ExtractionJob:
        runtime = self.require(job_id)
        runtime.job.cancel_requested = True
        runtime.cancel_event.set()
        logger.info("job cancelled requested %s", job_id)
        if runtime.job.is_terminal():
            return runtime.job
        runtime.job.current_activity = "Stopping extraction"
        return runtime.job

    async def focus_browser(self, job_id: str) -> bool:
        runtime = self.require(job_id)
        page = runtime.page
        if page is None:
            return False
        try:
            await page.bring_to_front()
        except Exception as exc:
            logger.info("Could not focus Playwright page: %s", exc)
            return False
        return True

    async def emit(self, runtime: JobRuntime, event: dict[str, Any]) -> None:
        await runtime.events.put(event)

    async def finish(
        self,
        runtime: JobRuntime,
        status: JobStatus,
        event_type: str,
        message: str,
        error: str | None = None,
    ) -> None:
        runtime.job.mark_finished(status, error=error)
        runtime.job.current_activity = message
        await self.emit(
            runtime,
            make_event(
                event_type,
                runtime.job.job_id,
                status=status.value,
                message=message,
                businesses_seen=runtime.job.businesses_seen,
                leads_extracted=runtime.job.leads_extracted,
                emails_found=runtime.job.emails_found,
                websites_found=runtime.job.websites_found,
                error=error,
            ),
        )
        logger.info("job %s %s", runtime.job.job_id, status.value)
        await self.cleanup_browser(runtime)

    async def cleanup_browser(self, runtime: JobRuntime) -> None:
        page = runtime.page
        context = runtime.context
        browser = runtime.browser
        runtime.page = None
        runtime.context = None
        runtime.browser = None
        for closer, target in (
            (getattr(page, "close", None), page),
            (getattr(context, "close", None), context),
            (getattr(browser, "close", None), browser),
        ):
            if closer is None:
                continue
            try:
                await closer()
            except Exception as exc:
                logger.debug("Browser cleanup: %s %s", target, exc)

    async def iter_events(self, job_id: str):
        runtime = self.require(job_id)
        while True:
            event = await runtime.events.get()
            yield event
            if event.get("type") in {
                "completed",
                "cancelled",
                "error",
                "verification_timeout",
            }:
                break


job_manager = JobManager()
