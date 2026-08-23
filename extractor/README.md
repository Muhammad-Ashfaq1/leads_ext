# Python extractor service

FastAPI + Playwright service used by the AWT Phone Lead Extractor.

```bash
cd extractor
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
playwright install chromium
cp .env.example .env
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

Health check: `http://127.0.0.1:8001/health`

See the repository root `README.md` for architecture, verification, and mock-stream usage.
