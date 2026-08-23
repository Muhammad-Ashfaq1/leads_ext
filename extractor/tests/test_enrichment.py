from __future__ import annotations

from unittest.mock import patch

import httpx
import pytest

from app.config import Settings
from app.services.enrichment import WebsiteEnricher


class FakeTransport(httpx.AsyncBaseTransport):
    def __init__(self, pages: dict[str, str], robots: str = "User-agent: *\nAllow: /\n") -> None:
        self.pages = pages
        self.robots = robots

    async def handle_async_request(self, request: httpx.Request) -> httpx.Response:
        url = str(request.url)
        if url.endswith("/robots.txt"):
            return httpx.Response(200, text=self.robots, request=request)
        for path, body in self.pages.items():
            if request.url.path.rstrip("/") == path.rstrip("/") or (
                path == "/" and request.url.path in {"", "/"}
            ):
                return httpx.Response(200, text=body, request=request)
        return httpx.Response(404, text="", request=request)


@pytest.fixture
def allow_example_urls():
    with patch("app.services.enrichment.is_safe_public_url", return_value=True):
        yield


@pytest.mark.asyncio
async def test_extracts_public_emails_from_contact_page(allow_example_urls):
    transport = FakeTransport(
        {
            "/": "<html>Welcome</html>",
            "/contact": "<html>Email us at hello@clinic.example</html>",
        }
    )
    client = httpx.AsyncClient(transport=transport, base_url="https://clinic.example")
    enricher = WebsiteEnricher(Settings(enrichment_enabled=True), client=client)
    emails = await enricher.enrich("https://clinic.example")
    assert "hello@clinic.example" in emails


@pytest.mark.asyncio
async def test_failed_enrichment_returns_empty():
    enricher = WebsiteEnricher(Settings(enrichment_enabled=True))
    assert await enricher.enrich("http://127.0.0.1/secret") == []
    assert await enricher.enrich(None) == []


@pytest.mark.asyncio
async def test_robots_can_block_enrichment(allow_example_urls):
    transport = FakeTransport(
        {"/contact": "hello@clinic.example"},
        robots="User-agent: *\nDisallow: /\n",
    )
    client = httpx.AsyncClient(transport=transport, base_url="https://clinic.example")
    enricher = WebsiteEnricher(Settings(enrichment_enabled=True, user_agent="AWT-Phone-Lead-Extractor/1.0"), client=client)
    assert await enricher.enrich("https://clinic.example") == []
