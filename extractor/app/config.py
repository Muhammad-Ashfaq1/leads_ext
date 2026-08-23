from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    host: str = "127.0.0.1"
    port: int = 8001
    headless: bool = False
    human_verification_timeout: int = 300
    allow_mock: bool = True
    log_level: str = "INFO"
    user_agent: str = "AWT-Phone-Lead-Extractor/1.0"
    enrichment_enabled: bool = True
    enrichment_timeout_seconds: float = 10.0
    enrichment_max_pages: int = 5
    default_limit: int = 100
    min_limit: int = 1
    max_limit: int = 1000
    maps_url: str = "https://www.google.com/maps"
    verification_poll_seconds: float = 1.5
    result_settle_seconds: float = 1.2
    card_click_timeout_ms: int = 8000
    search_timeout_ms: int = 25000


@lru_cache
def get_settings() -> Settings:
    return Settings()
