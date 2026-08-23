from __future__ import annotations

import logging
import re
from typing import Any

logger = logging.getLogger(__name__)

VERIFICATION_PATTERNS = (
    r"unusual traffic",
    r"are you a robot",
    r"i.?m not a robot",
    r"not a robot",
    r"recaptcha",
    r"captcha",
    r"verify you.?re (a )?human",
    r"verify you are (a )?human",
    r"human verification",
    r"suspicious traffic",
    r"detected unusual traffic",
    r"our systems have detected",
    r"please verify",
    r"verify it.?s you",
    r"confirm you.?re not a bot",
    r"before you continue to google",
    r"this page can.t be displayed",
    r"/sorry/",
    r"sorry/index",
)

CONSENT_PATTERNS = (
    r"before you continue",
    r"accept all",
    r"reject all",
    r"i agree",
)


class VerificationDetector:
    """Detect Google human-verification / consent screens. Never bypasses them."""

    def __init__(self, extra_patterns: tuple[str, ...] = ()) -> None:
        self.patterns = [
            re.compile(pattern, re.IGNORECASE)
            for pattern in VERIFICATION_PATTERNS + extra_patterns
        ]

    def page_requires_verification(self, html: str | None, url: str | None = None) -> bool:
        haystack = f"{html or ''} {url or ''}"
        if not haystack.strip():
            return False
        return any(pattern.search(haystack) for pattern in self.patterns)

    async def inspect_page(self, page: Any) -> bool:
        if page is None:
            return False
        try:
            url = page.url or ""
            if "sorry/index" in url or "/sorry/" in url:
                return True

            recaptcha = await page.locator(
                'iframe[src*="recaptcha"], iframe[title*="reCAPTCHA"], #recaptcha, .g-recaptcha'
            ).count()
            if recaptcha:
                return True

            body_text = ""
            try:
                body_text = await page.locator("body").inner_text(timeout=1500)
            except Exception:
                body_text = await page.content()

            return self.page_requires_verification(body_text, url)
        except Exception as exc:
            logger.debug("Verification inspect failed: %s", exc)
            return False

    async def try_accept_consent(self, page: Any) -> bool:
        """Click an obvious cookie-consent button if one is present. Not a CAPTCHA bypass."""
        if page is None:
            return False
        selectors = [
            'button:has-text("Accept all")',
            'button:has-text("I agree")',
            'button:has-text("Accept")',
            'button[aria-label="Accept all"]',
            'form[action*="consent"] button',
        ]
        for selector in selectors:
            try:
                locator = page.locator(selector).first
                if await locator.count() and await locator.is_visible():
                    await locator.click(timeout=2000)
                    logger.info("Accepted Google consent dialog")
                    return True
            except Exception:
                continue
        return False
