# Leads Engine (VektorLeads) — SaaS Lead Generation & Autonomous Outreach Platform

An enterprise-grade, multi-tenant B2B lead generation, intelligence enrichment, AI spec asset creation, and direct cold outreach platform powered by **Google Places Platform API**, **Geospatial Coordinate Sub-Grids**, **Google Gemini 2.5 Flash**, and a unified **Gmail / Hostinger Email Hub**.

Built with **Laravel 12**, **PHP 8.3**, **MySQL 8.0+**, and styled with the **Vuexy / POS Glass Surface System**.

---

## 🚀 Key Features

- **⚡ Dual Extraction Engines**:
  - **Google Places Platform API (Default & Production)**: High-velocity lead discovery worldwide directly via HTTPS with **zero Python or VPS dependencies**.
  - **Browser Crawler (Chromium / Playwright)**: Headless browser automation for local scraping with interactive human-verification checkpoints.
- **🗺️ Geospatial Coordinate Sub-Grid Matrix**: Splits cities into dynamic micro-grids to bypass standard 60-lead search caps and pull **up to 2,500+ dense leads per search**.
- **🤖 AI Spec Website Generator (`Gemini 2.5 Flash`)**: Autonomously writes and styles high-converting landing pages for businesses lacking web presence, viewable via live public preview (`/preview/{uuid}`).
- **🛡️ 3-Tier Live Email Verification**: RFC syntax checks, disposable domain filtering, and real-time DNS **Mail Exchange (MX)** server validation for zero bounce rates.
- **🌐 Complete Social Footprint Extractor**: Automatically discovers company profiles on LinkedIn, Facebook, Instagram, Twitter/X, and YouTube.
- **✉️ Direct Outreach Suite & POS Email Templates**: Send cold emails directly from your own custom SMTP domain with dynamic tag personalization (`{{business_name}}`, `{{demo_website_url}}`, `{{pos_url}}`).
- **📬 Unified Email Inbox Hub (`/gmail`)**: Connect Google Gmail (OAuth 2.0) and Hostinger Webmail (IMAP & SMTP) to manage prospect replies and two-way conversations inside the app.
- **👥 Team Collaboration & User Invitations**: Workspace Admins can invite team members with secure 64-character tokens and enforce seat quotas per subscription tier.
- **🏢 Multi-Tenant Architecture & Impersonation**: Strict organization isolation (`tenant_id`), plan tiers (`Starter`, `Growth`, `Agency`, `Enterprise`), and administrative user impersonation.
- **📊 Real-time SSE Streaming**: Discovered leads stream live to the browser via Server-Sent Events with dynamic metric counters.
- **📁 Memory-Safe OpenSpout Export**: 1-click high-capacity streaming exports to Microsoft Excel (`.xlsx`), CSV, and JSON.
- **🚀 Automated CI/CD Ready**: Zero-downtime GitHub Actions deployment pipeline for **Hostinger Shared Hosting** (`leads.obtainsolutions.com`).

---

## 📚 Complete Documentation

All technical and operational documentation is organized in the [`docs/`](docs/README.md) directory:

| Document | Description |
| :--- | :--- |
| 📑 [**Documentation Master Index**](docs/README.md) | Central table of contents and directory of all platform documentation. |
| 🏗️ [**Architecture & System Design**](docs/ARCHITECTURE.md) | 4-layer architecture, dual extraction engines, sequence diagrams, and schema. |
| 📡 [**Complete REST & SSE API Reference**](docs/API_REFERENCE.md) | All endpoints: Extraction, Leads CRM, Gemini Demo, Templates, Inbox Hub, and Admin. |
| 🏢 [**SaaS Multi-Tenancy & Roles**](docs/SAAS_AND_ROLES.md) | RBAC permissions matrix, tenant scoping, seat limits, and impersonation. |
| 🤖 [**AI Spec Website Generator Guide**](docs/AI_WEBSITE_GENERATOR.md) | Gemini 2.5 Flash landing page generation, design tokens, and `/preview/{uuid}`. |
| ✉️ [**Email Outreach & Templates System**](docs/EMAIL_OUTREACH_AND_TEMPLATES.md) | Dynamic placeholder tags, POS email templates, bulk sending, and delivery logs. |
| 📬 [**Unified Email Inbox Hub (Gmail & Hostinger)**](docs/EMAIL_INBOX_INTEGRATION.md) | Google OAuth 2.0, Hostinger IMAP/SMTP sync, thread association, and replies. |
| 🗺️ [**Geospatial Grid Engine & Lead Matrix**](docs/GEOSPATIAL_GRID_ENGINE.md) | Bounding boxes, radial sweeps, and bypassing standard 60-result caps. |
| 👥 [**Team Invitations & Workspace Management**](docs/TEAM_INVITATIONS_AND_WORKSPACE.md) | Token security, dedicated no-reply mailer, and seat cap enforcement. |
| 🌐 [**Google Places API Integration Guide**](docs/GOOGLE_PLACES_API.md) | Places API v1 (searchText), field masks, pricing tiers, and API key hierarchy. |
| 🕷️ [**Python Chromium Crawler Service**](docs/PYTHON_CRAWLER.md) | FastAPI / Playwright crawler setup, CAPTCHA handling, and VPS hosting. |
| 🚀 [**Production Deployment Guide**](docs/DEPLOYMENT_GUIDE.md) | Hostinger shared hosting setup, GitHub Actions CI/CD, cron jobs, and queues. |
| 🧭 [**Google Places Feature Roadmap**](docs/GOOGLE_PLACES_FEATURES_EXPANSION.md) | Price tiers, operational health scanner, review sentiment, and tech stack detection. |
| 📈 [**Scaling Audit & Architecture Roadmap**](docs/SCALING_AND_ROADMAP.md) | Memory optimization, OpenSpout streaming, and Hostinger resource audit. |

---

## 🛠️ Local Development Quickstart

### 1. Prerequisites
- **PHP 8.2 or 8.3** with `pdo_mysql`, `curl`, `mbstring`, `openssl`, `zip`
- **Composer**
- **MySQL 8.0+** or **SQLite**
- **Node.js & NPM**

### 2. Installation
```bash
# Clone the repository
git clone https://github.com/Muhammad-Ashfaq1/leads_ext.git leads-info
cd leads-info

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env and run migrations & seeders
php artisan migrate --seed
```

### 3. Environment Configuration (`.env`)
Configure your Google Cloud and AI API keys in `.env`:
```dotenv
GOOGLE_MAPS_API_KEY=AIzaSyYourGoogleApiKey
GEMINI_API_KEY=AIzaSyYourGeminiApiKey
GEMINI_MODEL=gemini-2.5-flash
```

### 4. Serve the Application
```bash
php artisan serve
```
Access the application at `http://localhost:8000` (or `http://extractor.test` if using Laravel Valet).

---

## 🔑 Default Seeded Accounts

| Role | Email | Password | Organization | Plan |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@leads.test` | `password` | *Global Platform Owner* | Enterprise |
| **Tenant Admin** | `admin@acme.com` | `password` | Acme Corporation | Enterprise |
| **Tenant Admin** | `admin@nexus.com` | `password` | Nexus Digital Marketing | Pro |

---

## 🧪 Automated Testing Suite

Run the full automated test suite covering all features:

```bash
php artisan test
```

Over **150 automated tests** covering Authentication, Multi-Tenant Scoping, Role Authorization, Geospatial Grid Extraction, Real-Time SSE Streaming, Gemini Spec Website Generation, Gmail/Hostinger Inbox Sync, User Invitations, and Excel/CSV exports pass cleanly.

---

## 📄 License
This software is proprietary and confidential. All rights reserved.
