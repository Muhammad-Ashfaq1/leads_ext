from __future__ import annotations

import asyncio
import logging

from app.models.job import JobRuntime, JobStatus
from app.services.events import make_event
from app.services.parser import parse_lead

logger = logging.getLogger(__name__)

MOCK_LEADS = [
    {
        "business_name": "Lahore Dental Studio",
        "address": "MM Alam Road, Lahore, Pakistan",
        "phone": "+92 42 35789012",
        "emails": ["hello@lahoredental.example"],
        "website": "https://lahoredental.example",
        "category": "Dentist",
        "rating": 4.7,
        "review_count": 238,
        "google_maps_url": "https://www.google.com/maps/place/Lahore+Dental+Studio",
        "place_id": "0x3919050000000001:0xaaa0000000000001",
        "city": "Lahore",
        "country": "Pakistan",
        "source": "Google Maps",
    },
    {
        "business_name": "Smile Care Clinic",
        "address": "Gulberg III, Lahore, Pakistan",
        "phone": "+92 42 35112233",
        "emails": [],
        "website": "https://smilecare.example",
        "category": "Dental clinic",
        "rating": 4.5,
        "review_count": 164,
        "google_maps_url": "https://www.google.com/maps/place/Smile+Care+Clinic",
        "place_id": "0x3919050000000002:0xaaa0000000000002",
        "city": "Lahore",
        "country": "Pakistan",
        "source": "Google Maps",
    },
    {
        "business_name": "Pearl Orthodontics",
        "address": "DHA Phase 5, Lahore, Pakistan",
        "phone": "+92 300 1234567",
        "emails": ["info@pearlortho.example"],
        "website": None,
        "category": "Orthodontist",
        "rating": 4.8,
        "review_count": 91,
        "google_maps_url": "https://www.google.com/maps/place/Pearl+Orthodontics",
        "place_id": "0x3919050000000003:0xaaa0000000000003",
        "city": "Lahore",
        "country": "Pakistan",
        "source": "Google Maps",
    },
    {
        "business_name": "City Braces Center",
        "address": "Johar Town, Lahore, Pakistan",
        "phone": "+92 42 35224455",
        "emails": [],
        "website": "https://citybraces.example",
        "category": "Dentist",
        "rating": 4.3,
        "review_count": 57,
        "google_maps_url": "https://www.google.com/maps/place/City+Braces+Center",
        "place_id": "0x3919050000000004:0xaaa0000000000004",
        "city": "Lahore",
        "country": "Pakistan",
        "source": "Google Maps",
    },
    {
        "business_name": "Family Tooth Clinic",
        "address": "Model Town, Lahore, Pakistan",
        "phone": "+92 42 35887766",
        "emails": ["contact@familytooth.example"],
        "website": "https://familytooth.example",
        "category": "Dentist",
        "rating": 4.6,
        "review_count": 120,
        "google_maps_url": "https://www.google.com/maps/place/Family+Tooth+Clinic",
        "place_id": "0x3919050000000005:0xaaa0000000000005",
        "city": "Lahore",
        "country": "Pakistan",
        "source": "Google Maps",
    },
]


async def run_mock_job(manager, runtime: JobRuntime) -> None:
    job = runtime.job
    try:
        await asyncio.sleep(0.15)
        if job.cancel_requested:
            await manager.finish(runtime, JobStatus.CANCELLED, "cancelled", "Extraction stopped.")
            return

        job.status = JobStatus.SEARCHING
        job.current_activity = f"Searching {job.query}"
        logger.info("Google Maps search (mock) %s query=%s", job.job_id, job.query)
        await manager.emit(
            runtime,
            make_event(
                "searching",
                job.job_id,
                status=job.status.value,
                query=job.query,
                message=f"Searching Google Maps for “{job.query}”.",
            ),
        )
        await asyncio.sleep(0.2)

        job.status = JobStatus.EXTRACTING
        leads = (MOCK_LEADS * ((job.limit // len(MOCK_LEADS)) + 1))[: job.limit]
        verification_after = 3 if job.simulate_verification else None

        for index, raw in enumerate(leads, start=1):
            if job.cancel_requested:
                await manager.finish(
                    runtime,
                    JobStatus.CANCELLED,
                    "cancelled",
                    "Extraction stopped. Previously extracted leads have been preserved.",
                )
                return

            if verification_after is not None and index == verification_after + 1:
                if not await _simulate_verification(manager, runtime):
                    return
                job.status = JobStatus.EXTRACTING

            await asyncio.sleep(0.18)
            clone = dict(raw)
            if index > len(MOCK_LEADS):
                clone["business_name"] = f"{raw['business_name']} {index}"
                clone["place_id"] = f"{raw['place_id']}-{index}"
            lead = parse_lead(clone)
            if lead is None:
                continue
            job.businesses_seen += 1
            job.leads_extracted += 1
            if lead.website:
                job.websites_found += 1
            if lead.emails:
                job.emails_found += len(lead.emails)
            job.current_activity = f"Processing {lead.business_name}"
            job.leads.append(lead.model_dump())
            logger.info("business discovered %s", lead.business_name)
            await manager.emit(
                runtime,
                make_event(
                    "lead",
                    job.job_id,
                    status=job.status.value,
                    lead=lead.model_dump(),
                    businesses_seen=job.businesses_seen,
                    leads_extracted=job.leads_extracted,
                    emails_found=job.emails_found,
                    websites_found=job.websites_found,
                ),
            )
            await manager.emit(
                runtime,
                make_event(
                    "progress",
                    job.job_id,
                    status=job.status.value,
                    businesses_seen=job.businesses_seen,
                    leads_extracted=job.leads_extracted,
                    emails_found=job.emails_found,
                    websites_found=job.websites_found,
                    current_activity=job.current_activity,
                ),
            )

        await manager.finish(runtime, JobStatus.COMPLETED, "completed", "Extraction completed.")
    except Exception as exc:
        logger.exception("mock job error %s", job.job_id)
        await manager.finish(runtime, JobStatus.ERROR, "error", "Extraction failed.", error=str(exc))


async def _simulate_verification(manager, runtime: JobRuntime) -> bool:
    job = runtime.job
    job.status = JobStatus.WAITING_FOR_HUMAN_VERIFICATION
    job.current_activity = "Waiting for human verification"
    logger.info("human verification detected %s", job.job_id)
    await manager.emit(
        runtime,
        make_event(
            "human_verification_required",
            job.job_id,
            status=job.status.value,
            message="Google Maps requires human verification.",
        ),
    )

    timeout = manager.settings.human_verification_timeout
    elapsed = 0.0
    while elapsed < timeout:
        if job.cancel_requested:
            await manager.finish(
                runtime,
                JobStatus.CANCELLED,
                "cancelled",
                "Extraction stopped. Previously extracted leads have been preserved.",
            )
            return False
        if getattr(runtime, "mock_verification_completed", False):
            runtime.mock_verification_completed = False
            job.status = JobStatus.EXTRACTING
            logger.info("verification completed %s", job.job_id)
            await manager.emit(
                runtime,
                make_event(
                    "verification_completed",
                    job.job_id,
                    status=job.status.value,
                    message="Verification completed. Extraction resumed.",
                ),
            )
            return True
        await asyncio.sleep(0.2)
        elapsed += 0.2

    await manager.finish(
        runtime,
        JobStatus.VERIFICATION_TIMEOUT,
        "verification_timeout",
        "Human verification was not completed within the allowed time. "
        "Extraction has stopped. Previously extracted leads have been preserved.",
    )
    return False


def complete_mock_verification(runtime: JobRuntime) -> None:
    runtime.mock_verification_completed = True
