# Leads Engine — SaaS Lead Generation & Enrichment Platform

A modern, multi-tenant SaaS application that discovers, enriches, and exports verified B2B leads from Google Maps using **Google Places Platform API** and an optional **Playwright Chromium browser crawler**.

Built with **Laravel 12**, **PHP 8.3**, **MySQL**, and styled with the **Vuexy / POS Glass Surface System**.

---

## 🚀 Key Features

- **⚡ Dual Extraction Engines**:
  - **Google Places Platform API (Default)**: Instant, high-volume lead discovery worldwide directly via HTTPS with **zero Python or VPS required**.
  - **Browser Crawler (Chromium / Playwright)**: Headless browser automation for free local scraping with human-verification pause/resume.
- **🏢 Multi-Tenant Architecture**: Strict client organization isolation (`tenant_id`), custom lead discovery quotas, and plan tiers (`starter`, `pro`, `enterprise`).
- **👥 Role-Based Access Control**:
  - 👑 **Super Admin**: Platform-wide tenant management, global analytics, and quota provisioning.
  - 🏢 **Tenant Admin**: Team management, custom API keys, lead extraction, and Excel exports.
  - 👤 **Team Member**: Extraction tasks, lead searching, and downloads.
- **🔍 Contact & Email Discovery**: Automatic background inspection of business websites to extract public contact emails.
- **📊 Real-time SSE Streaming**: Leads appear live on screen the instant they are found via Server-Sent Events.
- **📁 Universal Excel Export**: Instant 1-click Microsoft Excel (`.xlsx`) and JSON exports for search sessions or the master lead database.
- **🎨 POS UI Design System**: Glass panels, stat cards, live metric pills, and clean sidebar navigation matching `Projects/pos`.
- **🚀 CI/CD Ready**: Automated GitHub Actions deployment pipeline for **Hostinger Shared Hosting** (`leads.obtainsolutions.com`).

---

## 📚 Complete Documentation

Comprehensive guides are organized in the [`docs/`](file:///Users/macbookpro2019/Projects/leads-info/docs/) directory:

- 🏗️ [**Architecture & System Design**](docs/ARCHITECTURE.md) — Dual engine pipeline, SSE lifecycle, and database schema.
- 🏢 [**SaaS Multi-Tenancy & Roles**](docs/SAAS_AND_ROLES.md) — Super Admin, Tenant Admin, team permissions, and quotas.
- 🌐 [**Google Places API Guide**](docs/GOOGLE_PLACES_API.md) — API keys hierarchy, pricing, free tiers, and pre-filtering criteria.
- 🕷️ [**Python Chromium Crawler Service**](docs/PYTHON_CRAWLER.md) — FastAPI / Playwright crawler setup and optional VPS hosting.
- 🚀 [**Production Deployment Guide**](docs/DEPLOYMENT_GUIDE.md) — Hostinger shared hosting setup, GitHub Actions CI/CD, and `.htaccess` routing.
- 📡 [**REST & SSE API Reference**](docs/API_REFERENCE.md) — Complete endpoint specifications and JSON/SSE payload schemas.

---

## 🛠️ Local Development Quickstart

### 1. Requirements
- PHP 8.2 or 8.3
- Composer
- MySQL 8.0+ / SQLite
- Node.js & NPM (for frontend builds if modifying CSS/JS)

### 2. Installation
```bash
# Clone the repository
git clone https://github.com/Muhammad-Ashfaq1/leads_ext.git leads-info
cd leads-info

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env and run migrations & seeders
php artisan migrate --seed
```

### 3. Google Places API Key Configuration
Add your Google Cloud Places API key to `.env`:
```dotenv
GOOGLE_MAPS_API_KEY=AIzaSyYourActualGoogleApiKey
```

### 4. Serve the Application
```bash
# Start Laravel server (or use Laravel Valet: http://extractor.test)
php artisan serve
```

---

## 🔑 Default Seeded Accounts

| Role | Email | Password | Organization | Plan |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@leads.test` | `password` | *Global Platform Owner* | Enterprise |
| **Tenant Admin** | `admin@acme.com` | `password` | Acme Corporation | Enterprise |
| **Tenant Admin** | `admin@nexus.com` | `password` | Nexus Digital Marketing | Pro |

---

## 🧪 Testing

Run the full automated test suite:

```bash
php artisan test
```

All 28 tests covering Authentication, Multi-Tenancy, Role Authorization, Google Places API streaming, Pre-filters, and Excel export run in under 2 seconds.

---

## 📄 License
This software is proprietary and confidential. All rights reserved.
