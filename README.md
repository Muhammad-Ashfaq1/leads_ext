# leads_ext

# AWT Phone — Google Maps Lead Extractor

A local AWT Phone tool that turns a natural-language prompt into a Google Maps search, extracts publicly listed business information, and **streams each lead to the browser as soon as it is found**.

Open:

```text
http://extractor.test
```

There is no login in this first version.

## Architecture

```text
Browser (extractor.test)
   │
   ▼
Laravel 12  — page, API, SSE proxy, persistence, CSV export
   │
   ▼
Python FastAPI (127.0.0.1:8001)
   │
   ▼
Playwright Chromium  →  Google Maps
```

Laravel owns the UI and job records. Python owns browser automation, parsing, enrichment, cancellation, and human-verification pause/resume. Events flow:

```text
Python SSE  →  Laravel /api/extractor/{job}/stream  →  EventSource in the page
```

Existing AWT Phone realtime (Reverb) is not required. This tool uses Server-Sent Events so leads appear immediately without polling.

## Requirements

Already expected on this MacBook:

- PHP 8.2+ / Composer / Laravel
- MySQL or SQLite
- Laravel Valet
- Python 3.14

Do not reinstall PHP, Composer, Laravel, MySQL, or Valet.

## Laravel setup

```bash
cd /Users/macbookpro2019/Projects/leads-info
cp .env.example .env
php artisan key:generate
```

`.env` essentials:

```dotenv
APP_NAME="AWT Phone"
APP_URL=http://extractor.test
EXTRACTOR_SERVICE_URL=http://127.0.0.1:8001
EXTRACTOR_ALLOW_MOCK=true
```

Create the database (MySQL) or keep SQLite:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS leads_extractor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
```

The site is already available at:

```text
http://extractor.test
```

(and also `http://leads-info.test` if `~/Projects` is parked).

To register the HTTPS Valet link yourself:

```bash
cd /Users/macbookpro2019/Projects/leads-info
sudo valet link extractor
sudo valet secure extractor
```

If SSE events look buffered, the Laravel stream already sends `X-Accel-Buffering: no`.

## Python setup

```bash
cd extractor
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
playwright install chromium
cp .env.example .env
```

Python `.env`:

```dotenv
HOST=127.0.0.1
PORT=8001
HEADLESS=false
HUMAN_VERIFICATION_TIMEOUT=300
ALLOW_MOCK=true
```

`HEADLESS=false` is required so you can complete Google human verification in the same Playwright window.

## Run

Terminal 1 — Python extractor:

```bash
cd extractor
source .venv/bin/activate
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

Laravel / Valet continues to serve `http://extractor.test`.

## How to extract leads

1. Open `http://extractor.test`.
2. Enter a prompt such as `Find dentists in Lahore`.
3. Choose a maximum (100 / 500 / 1000).
4. Click **Start Extraction**.
5. Playwright opens Google Maps and searches `dentists in Lahore`.
6. Each discovered business appears in the table immediately.
7. Counters update live.
8. Click **Stop Extraction** at any time. Already-found leads stay on the page and in the database.
9. When the job finishes, export CSV.

The extractor never invents missing emails, phones, or ratings. Empty values stay empty.

## Human verification

Google may show CAPTCHA / unusual-traffic / “I’m not a robot” screens.

This tool **does not** solve, bypass, OCR, token-inject, stealth, fingerprint, or proxy-rotate around those screens.

Instead:

1. Extraction pauses in `WAITING_FOR_HUMAN_VERIFICATION`.
2. The Playwright browser and session stay alive.
3. The page shows **Human Verification Required**.
4. Click **Open Verification** to focus the same Playwright window.
5. Complete the challenge yourself.
6. The extractor notices Maps is usable again, emits `verification_completed`, and continues from the current session.
7. This can happen more than once in one job.
8. If verification is not finished within `HUMAN_VERIFICATION_TIMEOUT` (default 300 seconds), the job stops safely and keeps already extracted leads.

## Development mock stream

Because Google may not show a CAPTCHA while you are building, local/dev mode can simulate the full event sequence:

```text
started → searching → lead → lead → lead
→ human_verification_required
→ verification_completed
→ lead → lead → completed
```

On `extractor.test` (only when `APP_ENV` is not `production` and `EXTRACTOR_ALLOW_MOCK=true`):

1. Enable **Development mock stream**.
2. Optionally enable **Simulate human verification**.
3. Start extraction.
4. Leads appear in real time without opening Google Maps.
5. When verification is simulated, click **Mark Verification Complete** to resume.

This toggle is not available in production.

## Website enrichment

If a public website is listed, Python may fetch only:

```text
/
/contact
/contact-us
/about
/about-us
```

It respects `robots.txt`, blocks private/loopback URLs (SSRF), and never crawls the rest of the site. A failed enrichment does not fail the lead.

## Tests

Laravel:

```bash
php artisan test --filter=Extractor
```

Python:

```bash
cd extractor
source .venv/bin/activate
pytest
```

Unit tests do not call live Google Maps.

## Troubleshooting

| Symptom | What to check |
|---|---|
| “Extractor service is unavailable” | Python is not running on port 8001 |
| Page loads but no live rows | Confirm EventSource is hitting `/api/extractor/{id}/stream` and Python `/jobs/{id}/events` |
| Browser does not open | `HEADLESS=false` and `playwright install chromium` |
| Verification never resumes | Stay in the same Playwright window; do not open a separate Maps tab |
| MySQL connection error | Create `leads_extractor` or switch `.env` to `DB_CONNECTION=sqlite` |
| Mock toggle missing | `APP_ENV=local` and `EXTRACTOR_ALLOW_MOCK=true` |

## Safety

Allowed: search public Google Maps results, extract publicly displayed business fields, pause for a person to complete Google’s own verification, resume afterwards.

Not allowed and not implemented: CAPTCHA solving, token extraction/injection, stealth plugins, fingerprint spoofing, proxy rotation to evade blocks, or using Google account credentials.
