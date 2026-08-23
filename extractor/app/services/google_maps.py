from __future__ import annotations

import asyncio
import logging
import platform
import subprocess
from typing import Any

from playwright.async_api import TimeoutError as PlaywrightTimeout
from playwright.async_api import async_playwright

from app.config import Settings
from app.models.job import JobRuntime, JobStatus
from app.models.lead import Lead
from app.services.enrichment import WebsiteEnricher
from app.services.events import make_event
from app.services.parser import DuplicateTracker, parse_lead
from app.services.verification import VerificationDetector
from app.utils.prompt import maps_search_url
from app.utils.text import collapse_whitespace

logger = logging.getLogger(__name__)

RESULT_SELECTORS = (
    'div[role="feed"] a[href*="/maps/place"]',
    'div[role="feed"] a.hfpxzc',
    'a[href*="/maps/place/"]',
)

SEARCH_BOX_SELECTORS = (
    '#searchboxinput',
    'input[name="q"]',
    'input[aria-label="Search Google Maps"]',
    'input[placeholder="Search Google Maps"]',
    'input[aria-label*="Search Google Maps"]',
    'input[placeholder*="Search Google Maps"]',
    'input.searchboxinput',
    'form#searchbox_form input[type="text"]',
    'input[id*="searchbox"]',
)

DETAIL_NAME_SELECTORS = (
    "h1.DUwDvf",
    'h1[class*="fontHeadline"]',
    "h1",
)


class GoogleMapsExtractor:
    def __init__(self, manager, settings: Settings) -> None:
        self.manager = manager
        self.settings = settings
        self.verification = VerificationDetector()
        self.enricher = WebsiteEnricher(settings)

    async def run(self, runtime: JobRuntime) -> None:
        job = runtime.job
        try:
            async with async_playwright() as playwright:
                browser = await playwright.chromium.launch(
                    headless=self.settings.headless,
                    args=["--disable-dev-shm-usage"],
                )
                context = await browser.new_context(
                    viewport={"width": 1360, "height": 900},
                    locale="en-US",
                )
                page = await context.new_page()
                runtime.browser = browser
                runtime.context = context
                runtime.page = page

                await self._goto_maps(runtime)
                if await self._pause_if_verification(runtime):
                    if job.should_stop():
                        return

                await self._search(runtime)
                if job.should_stop():
                    return

                await self._extract_results(runtime)
                if job.cancel_requested:
                    await self.manager.finish(
                        runtime,
                        JobStatus.CANCELLED,
                        "cancelled",
                        "Extraction stopped. Previously extracted leads have been preserved.",
                    )
                    return
                if job.is_terminal():
                    return
                await self.manager.finish(
                    runtime,
                    JobStatus.COMPLETED,
                    "completed",
                    "Extraction completed.",
                )
        except Exception as exc:
            logger.exception("job error %s", job.job_id)
            if not job.is_terminal():
                await self.manager.finish(
                    runtime,
                    JobStatus.ERROR,
                    "error",
                    _public_error_message(exc),
                    error=str(exc),
                )

    async def _goto_maps(self, runtime: JobRuntime) -> None:
        page = runtime.page
        job = runtime.job
        search_url = maps_search_url(job.query, self.settings.maps_url)
        runtime.job.current_activity = "Opening Google Maps"
        await self.manager.emit(
            runtime,
            make_event(
                "searching",
                runtime.job.job_id,
                status=JobStatus.SEARCHING.value,
                message="Opening Google Maps.",
                query=runtime.job.query,
            ),
        )
        try:
            await page.goto(search_url, wait_until="domcontentloaded", timeout=self.settings.search_timeout_ms)
        except Exception as exc:
            raise RuntimeError("Google Maps could not be reached.") from exc
        await page.wait_for_load_state("domcontentloaded")
        await self.verification.try_accept_consent(page)
        await asyncio.sleep(1.0)

    async def _search(self, runtime: JobRuntime) -> None:
        page = runtime.page
        job = runtime.job
        job.status = JobStatus.SEARCHING
        job.current_activity = f"Searching {job.query}"
        logger.info("Google Maps search %s query=%s", job.job_id, job.query)
        await self.manager.emit(
            runtime,
            make_event(
                "searching",
                job.job_id,
                status=job.status.value,
                message=f"Searching Google Maps for “{job.query}”.",
                query=job.query,
            ),
        )

        await self.verification.try_accept_consent(page)
        if await self._pause_if_verification(runtime):
            if job.should_stop():
                return

        if await self._results_ready(page):
            logger.info("Google Maps search results already visible for %s", job.job_id)
            return

        typed = await self._type_query_into_search_box(page, job.query)
        if not typed:
            # Direct search URL is the primary path. If results are still
            # missing, try the search URL once more after consent.
            search_url = maps_search_url(job.query, self.settings.maps_url)
            await page.goto(search_url, wait_until="domcontentloaded", timeout=self.settings.search_timeout_ms)
            await self.verification.try_accept_consent(page)
            await asyncio.sleep(self.settings.result_settle_seconds)

        await self._pause_if_verification(runtime)
        if not await self._wait_for_results(page):
            raise RuntimeError(
                "Google Maps opened, but the search did not run. "
                "Check the Maps window and try again."
            )

    async def _type_query_into_search_box(self, page: Any, query: str) -> bool:
        box = await self._find_search_box(page)
        if box is None:
            logger.info("Search box not found; relying on the Maps search URL")
            return False
        try:
            await box.click(timeout=4000)
            await box.fill("")
            await box.fill(query)
            current = (await box.input_value()) or ""
            if query.casefold() not in current.casefold():
                await box.press_sequentially(query, delay=20)
            await box.press("Enter")
            await asyncio.sleep(self.settings.result_settle_seconds)
            logger.info("Typed search query into the Maps search box")
            return True
        except Exception as exc:
            logger.info("Could not type into the Maps search box: %s", exc)
            return False

    async def _find_search_box(self, page: Any):
        frames = [page, *list(getattr(page, "frames", []) or [])]
        for frame in frames:
            for selector in SEARCH_BOX_SELECTORS:
                try:
                    locator = frame.locator(selector).first
                    if await locator.count() == 0:
                        continue
                    try:
                        await locator.wait_for(state="visible", timeout=2500)
                    except PlaywrightTimeout:
                        if not await locator.is_visible():
                            continue
                    return locator
                except Exception:
                    continue
        return None

    async def _results_ready(self, page: Any) -> bool:
        for selector in (
            'div[role="feed"]',
            "a.hfpxzc",
            'a[href*="/maps/place/"]',
            'div[role="main"] h1',
        ):
            try:
                locator = page.locator(selector).first
                if await locator.count() and await locator.is_visible():
                    return True
            except Exception:
                continue
        return "/maps/search" in (page.url or "") and "maps/place" in (page.url or "")

    async def _wait_for_results(self, page: Any) -> bool:
        for selector in ('div[role="feed"]', "a.hfpxzc", 'a[href*="/maps/place/"]', 'div[role="main"]'):
            try:
                await page.locator(selector).first.wait_for(state="visible", timeout=8000)
                return True
            except PlaywrightTimeout:
                continue
        return await self._results_ready(page)

    async def _extract_results(self, runtime: JobRuntime) -> None:
        page = runtime.page
        job = runtime.job
        tracker = DuplicateTracker()
        job.status = JobStatus.EXTRACTING
        empty_scrolls = 0

        while not job.should_stop() and job.leads_extracted < job.limit:
            if await self._pause_if_verification(runtime):
                if job.should_stop():
                    return

            cards = await self._result_cards(page)
            if not cards:
                empty_scrolls += 1
                await self._scroll_feed(page)
                if empty_scrolls >= 4:
                    logger.info("No more relevant results %s", job.job_id)
                    return
                continue

            new_in_pass = 0
            for index, card in enumerate(cards):
                if job.should_stop() or job.leads_extracted >= job.limit:
                    return
                if await self._pause_if_verification(runtime):
                    if job.should_stop():
                        return

                try:
                    href = await card.get_attribute("href")
                    aria = await card.get_attribute("aria-label")
                    preview_name = collapse_whitespace(aria)
                    job.businesses_seen += 1
                    job.current_activity = f"Processing {preview_name or 'business listing'}"
                    await self.manager.emit(
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

                    await card.click(timeout=self.settings.card_click_timeout_ms)
                    await asyncio.sleep(self.settings.result_settle_seconds)
                    raw = await self._read_place_panel(page, href)
                    if preview_name and not raw.get("business_name"):
                        raw["business_name"] = preview_name
                    lead = parse_lead(raw)
                    if lead is None:
                        logger.info("business parsed skipped (malformed) %s", preview_name)
                        continue
                    if tracker.is_duplicate(lead):
                        logger.info("duplicate skipped %s", lead.business_name)
                        continue

                    logger.info("business discovered %s", lead.business_name)
                    new_in_pass += 1
                    await self._emit_lead(runtime, lead)
                except Exception as exc:
                    logger.info("Individual lead parsing failed: %s", exc)
                    continue

                if job.leads_extracted >= job.limit:
                    return

            if new_in_pass == 0:
                empty_scrolls += 1
            else:
                empty_scrolls = 0
            await self._scroll_feed(page)
            if empty_scrolls >= 5:
                return

    async def _result_cards(self, page: Any):
        for selector in RESULT_SELECTORS:
            locator = page.locator(selector)
            count = await locator.count()
            if count:
                return [locator.nth(i) for i in range(count)]
        return []

    async def _scroll_feed(self, page: Any) -> None:
        feed = page.locator('div[role="feed"]').first
        try:
            if await feed.count():
                await feed.evaluate("(el) => { el.scrollBy(0, el.clientHeight); }")
            else:
                await page.mouse.wheel(0, 1400)
        except Exception:
            await page.mouse.wheel(0, 1400)
        await asyncio.sleep(0.8)

    async def _read_place_panel(self, page: Any, href: str | None) -> dict[str, Any]:
        raw: dict[str, Any] = {
            "google_maps_url": page.url if "/maps/place" in (page.url or "") else href,
            "source": "Google Maps",
        }
        try:
            for selector in DETAIL_NAME_SELECTORS:
                loc = page.locator(selector).first
                if await loc.count():
                    text = collapse_whitespace(await loc.inner_text())
                    if text:
                        raw["business_name"] = text
                        break
        except Exception:
            pass

        raw["address"] = await self._attr_or_text(
            page,
            'button[data-item-id="address"]',
            'button[data-tooltip="Copy address"]',
            'button[aria-label^="Address"]',
        )
        raw["phone"] = await self._phone(page)
        raw["website"] = await self._website(page)
        raw["category"] = await self._attr_or_text(
            page,
            "button.DkEaL",
            'button[jsaction*="category"]',
            'span.DkEaL',
        )
        raw["business_hours"] = await self._hours(page)

        rating_text = await self._attr_or_text(
            page,
            'div.F7nice',
            'span[aria-label*="stars"]',
            'div[role="img"][aria-label*="star"]',
        )
        if rating_text:
            from app.services.parser import parse_rating_and_reviews

            rating, reviews = parse_rating_and_reviews(rating_text)
            raw["rating"] = rating
            raw["review_count"] = reviews
            if reviews is None:
                reviews_text = await self._attr_or_text(page, "div.F7nice span", "span.UY7F9")
                if reviews_text:
                    _, reviews = parse_rating_and_reviews(reviews_text)
                    raw["review_count"] = reviews

        from app.services.parser import extract_coordinates, extract_place_id

        raw["place_id"] = extract_place_id(raw.get("google_maps_url"))
        lat, lng = extract_coordinates(raw.get("google_maps_url"))
        raw["latitude"] = lat
        raw["longitude"] = lng
        return raw

    async def _attr_or_text(self, page: Any, *selectors: str) -> str | None:
        for selector in selectors:
            try:
                loc = page.locator(selector).first
                if not await loc.count():
                    continue
                label = await loc.get_attribute("aria-label")
                text = collapse_whitespace(label) or collapse_whitespace(await loc.inner_text())
                if text:
                    for prefix in ("Address: ", "Phone: ", "Hours: "):
                        if text.startswith(prefix):
                            text = text[len(prefix) :]
                    return text
            except Exception:
                continue
        return None

    async def _phone(self, page: Any) -> str | None:
        try:
            loc = page.locator('button[data-item-id^="phone:"]').first
            if await loc.count():
                item_id = await loc.get_attribute("data-item-id")
                if item_id and ":" in item_id:
                    return collapse_whitespace(item_id.split(":", 1)[1])
                return await self._attr_or_text(page, 'button[data-item-id^="phone:"]')
        except Exception:
            pass
        return await self._attr_or_text(page, 'button[aria-label^="Phone"]', 'a[href^="tel:"]')

    async def _website(self, page: Any) -> str | None:
        try:
            loc = page.locator('a[data-item-id="authority"]').first
            if await loc.count():
                href = await loc.get_attribute("href")
                if href:
                    return href
        except Exception:
            pass
        return None

    async def _hours(self, page: Any) -> str | None:
        return await self._attr_or_text(
            page,
            'div[aria-label*="Hours"]',
            'button[data-item-id="oh"]',
            'div.t39EBf',
        )

    async def _emit_lead(self, runtime: JobRuntime, lead: Lead) -> None:
        job = runtime.job
        emails = list(lead.emails)
        source = lead.source
        if lead.website and self.settings.enrichment_enabled:
            previous = job.status
            job.status = JobStatus.ENRICHING
            job.current_activity = f"Enriching {lead.business_name}"
            try:
                extra = await self.enricher.enrich(lead.website)
                if extra:
                    emails = list(dict.fromkeys([*emails, *extra]))
                    source = "Website enrichment" if extra and not lead.emails else lead.source
                    logger.info("email discovered %s", extra)
            except Exception as exc:
                logger.info("website enrichment failed for %s: %s", lead.business_name, exc)
            job.status = previous

        lead.emails = emails
        lead.source = source
        if lead.website:
            job.websites_found += 1
        if emails:
            job.emails_found += len(emails)

        job.leads_extracted += 1
        payload = lead.model_dump()
        job.leads.append(payload)
        job.seen_identities.add(lead.identity_key())
        logger.info("business parsed %s", lead.business_name)
        await self.manager.emit(
            runtime,
            make_event(
                "lead",
                job.job_id,
                status=job.status.value,
                lead=payload,
                businesses_seen=job.businesses_seen,
                leads_extracted=job.leads_extracted,
                emails_found=job.emails_found,
                websites_found=job.websites_found,
            ),
        )

    async def _pause_if_verification(self, runtime: JobRuntime) -> bool:
        page = runtime.page
        if page is None:
            return False
        if not await self.verification.inspect_page(page):
            return False
        return await self._wait_for_human(runtime)

    async def _wait_for_human(self, runtime: JobRuntime) -> bool:
        job = runtime.job
        if job.should_stop():
            return True

        job.status = JobStatus.WAITING_FOR_HUMAN_VERIFICATION
        job.current_activity = "Waiting for human verification"
        logger.info("human verification detected %s", job.job_id)
        await self.manager.emit(
            runtime,
            make_event(
                "human_verification_required",
                job.job_id,
                status=job.status.value,
                message="Google Maps requires human verification.",
            ),
        )
        await self._focus_os_browser()

        timeout = self.settings.human_verification_timeout
        elapsed = 0.0
        poll = self.settings.verification_poll_seconds
        while elapsed < timeout:
            if job.cancel_requested:
                await self.manager.finish(
                    runtime,
                    JobStatus.CANCELLED,
                    "cancelled",
                    "Extraction stopped. Previously extracted leads have been preserved.",
                )
                return True
            await asyncio.sleep(poll)
            elapsed += poll
            if runtime.page and not await self.verification.inspect_page(runtime.page):
                job.status = JobStatus.EXTRACTING
                job.current_activity = "Verification completed. Extraction resumed."
                logger.info("verification completed %s", job.job_id)
                await self.manager.emit(
                    runtime,
                    make_event(
                        "verification_completed",
                        job.job_id,
                        status=job.status.value,
                        message="Verification completed. Extraction resumed.",
                    ),
                )
                return True

        logger.info("verification timeout %s", job.job_id)
        await self.manager.finish(
            runtime,
            JobStatus.VERIFICATION_TIMEOUT,
            "verification_timeout",
            "Human verification was not completed within the allowed time. "
            "Extraction has stopped. Previously extracted leads have been preserved.",
        )
        return True

    async def _focus_os_browser(self) -> None:
        if platform.system() != "Darwin":
            return
        for app in ("Chromium", "Google Chrome for Testing", "Google Chrome"):
            try:
                subprocess.run(
                    ["osascript", "-e", f'tell application "{app}" to activate'],
                    check=False,
                    capture_output=True,
                    timeout=3,
                )
            except Exception:
                continue


def _public_error_message(exc: Exception) -> str:
    text = str(exc)
    if "net::" in text or "Google Maps could not be reached" in text:
        return "Google Maps could not be reached."
    if "search did not run" in text or "search box" in text:
        return text
    if "Timeout" in type(exc).__name__:
        return "Google Maps timed out while loading search results."
    return text if text and len(text) < 180 else "Extraction failed."
