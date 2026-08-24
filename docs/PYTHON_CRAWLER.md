# Leads Engine — Python Chromium Crawler Service

The **Python Crawler Service** (`extractor-service`) provides headless Chromium browser automation for extracting leads directly from Google Maps without requiring a Google Places API key.

---

## 1. Overview

- **Framework**: Python 3.12+ / FastAPI / Uvicorn.
- **Browser Automation**: Microsoft Playwright (Headless/Headful Chromium).
- **Communication Protocol**: Server-Sent Events (SSE) stream over HTTP.
- **Default Port**: `8001` (configurable via `.env`).

---

## 2. Local Setup & Execution

### Prerequisites
- Python 3.12+ installed.
- Pip and virtualenv.

### Installation

1. Navigate to the `extractor` directory:
   ```bash
   cd extractor
   ```
2. Create and activate a Python virtual environment:
   ```bash
   python3 -m venv .venv
   source .venv/bin/activate
   ```
3. Install required packages:
   ```bash
   pip install -r requirements.txt
   ```
4. Install Playwright Chromium binaries:
   ```bash
   playwright install chromium
   ```
5. Copy environment template:
   ```bash
   cp .env.example .env
   ```

### Configuration (`extractor/.env`)

```dotenv
HOST=127.0.0.1
PORT=8001
HEADLESS=false
HUMAN_VERIFICATION_TIMEOUT=300
ALLOW_MOCK=true
```

- `HEADLESS=false`: Recommended for local development so you can observe the browser window and solve Google human verification / CAPTCHA prompts if presented.
- `ALLOW_MOCK=true`: Enables local mock streaming for testing UI and SSE events without querying Google Maps.

### Running the Service

```bash
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

---

## 3. Human Verification (CAPTCHA) Handling

Google Maps may intermittently present an "unusual traffic" challenge or CAPTCHA screen.

### Non-Invasive Workflow:
1. When a CAPTCHA is detected, the Python scraper **does not attempt to bypass or break it**.
2. The scraper pauses and transitions the job into `WAITING_FOR_HUMAN_VERIFICATION`.
3. The Laravel UI shows a **Human Verification Required** alert with an **Open Verification Window** button.
4. The user completes the verification directly in the open Chromium window.
5. The scraper detects that Maps is accessible again, emits `verification_completed`, and automatically resumes extraction.

---

## 4. Deploying Python Scraper to a VPS (Optional)

If you want the Python Chromium crawler available in production:

1. Provision a small Ubuntu 22.04 / 24.04 VPS (e.g. Hostinger VPS 1, 1 vCPU, 4 GB RAM).
2. Install dependencies:
   ```bash
   sudo apt update && sudo apt install -y python3-pip python3-venv git
   git clone <your-repo> leads-extractor
   cd leads-extractor/extractor
   python3 -m venv .venv
   source .venv/bin/activate
   pip install -r requirements.txt
   playwright install-deps chromium
   playwright install chromium
   ```
3. Create a systemd service (`/etc/systemd/system/extractor.service`):
   ```ini
   [Unit]
   Description=Leads Engine Python Extractor
   After=network.target

   [Service]
   User=ubuntu
   WorkingDirectory=/home/ubuntu/leads-extractor/extractor
   ExecStart=/home/ubuntu/leads-extractor/extractor/.venv/bin/uvicorn app.main:app --host 0.0.0.0 --port 8001
   Restart=always

   [Install]
   WantedBy=multi-user.target
   ```
4. Enable and start:
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable extractor
   sudo systemctl start extractor
   ```
5. In your Laravel `.env` (on Hostinger shared hosting):
   ```dotenv
   EXTRACTOR_SERVICE_URL=http://<YOUR_VPS_IP>:8001
   ```
