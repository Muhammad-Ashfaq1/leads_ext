from __future__ import annotations

import logging
from urllib.parse import urljoin, urlparse
from urllib.robotparser import RobotFileParser

import httpx

from app.config import Settings, get_settings
from app.services.parser import extract_emails
from app.utils.ssrf import is_safe_public_url
from app.utils.text import unique_preserve_order

logger = logging.getLogger(__name__)

CANDIDATE_PATHS = ("/", "/contact", "/contact-us", "/about", "/about-us")


class WebsiteEnricher:
    def __init__(self, settings: Settings | None = None, client: httpx.AsyncClient | None = None) -> None:
        self.settings = settings or get_settings()
        self._client = client

    async def enrich(self, website: str | None) -> list[str]:
        if not self.settings.enrichment_enabled or not website:
            return []
        if not is_safe_public_url(website):
            logger.info("Skipped website enrichment for unsafe URL")
            return []
        emails: list[str] = []
        try:
            async with self._client_context() as client:
                if not await self._allowed_by_robots(client, website):
                    logger.info("robots.txt disallows enrichment for %s", website)
                    return []

                for path in CANDIDATE_PATHS[: self.settings.enrichment_max_pages]:
                    page_url = urljoin(website if website.endswith("/") else website + "/", path.lstrip("/"))
                    if path != "/" and not is_safe_public_url(page_url):
                        continue
                    try:
                        response = await client.get(page_url, follow_redirects=True)
                    except Exception as exc:
                        logger.info("Website enrichment page failed %s: %s", page_url, exc)
                        continue
                    if response.status_code >= 400:
                        continue
                    if not is_safe_public_url(str(response.url)):
                        continue
                    emails.extend(extract_emails(response.text))
        except Exception as exc:
            logger.info("Website enrichment failed for %s: %s", website, exc)
            return []

        found = unique_preserve_order(emails)
        if found:
            logger.info("Email discovered via website enrichment: %s", len(found))
        return found

    async def _allowed_by_robots(self, client: httpx.AsyncClient, website: str) -> bool:
        parsed = urlparse(website)
        robots_url = f"{parsed.scheme}://{parsed.netloc}/robots.txt"
        if not is_safe_public_url(robots_url):
            return False
        try:
            response = await client.get(robots_url, follow_redirects=True)
            if response.status_code >= 400:
                return True
            parser = RobotFileParser()
            parser.parse(response.text.splitlines())
            user_agent = self.settings.user_agent
            return all(parser.can_fetch(user_agent, urljoin(website, path)) for path in CANDIDATE_PATHS)
        except Exception:
            return True

    def _client_context(self):
        if self._client is not None:
            return _NullAsyncContext(self._client)
        return httpx.AsyncClient(
            timeout=self.settings.enrichment_timeout_seconds,
            headers={"User-Agent": self.settings.user_agent},
            follow_redirects=True,
        )


class _NullAsyncContext:
    def __init__(self, client: httpx.AsyncClient) -> None:
        self.client = client

    async def __aenter__(self) -> httpx.AsyncClient:
        return self.client

    async def __aexit__(self, *args: object) -> None:
        return None
