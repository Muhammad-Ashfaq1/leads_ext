# Leads Engine (VektorLeads) — Documentation Index

Welcome to the comprehensive technical and operational documentation for **Leads Engine** (branded as **VektorLeads**), an autonomous B2B lead generation, intelligence enrichment, AI spec asset generation, and direct cold outreach platform.

---

## 📚 Master Documentation Directory

| Document | Topic | Key Contents |
| :--- | :--- | :--- |
| 🏗️ [**Architecture & System Design**](ARCHITECTURE.md) | System Architecture | 4-Layer architecture, dual extraction engines, data flow sequences, and MySQL database schema. |
| 📡 [**Complete REST & SSE API Reference**](API_REFERENCE.md) | API Specifications | Endpoints for Extraction, Leads CRM, AI Demo Generator, Templates, Email Hub, Invitations, and Multi-Tenancy. |
| 🏢 [**SaaS Multi-Tenancy, Roles & Permissions**](SAAS_AND_ROLES.md) | Access Control & Multi-Tenancy | RBAC matrix (Super Admin, Tenant Admin, Member), tenant isolation, seat quotas, and impersonation. |
| 🌐 [**Google Places API Integration Guide**](GOOGLE_PLACES_API.md) | Discovery Engine A | Places API v1 (searchText), field masks, pricing tiers, and API key hierarchy. |
| 🗺️ [**Geospatial Grid Engine & Lead Matrix**](GEOSPATIAL_GRID_ENGINE.md) | Density Scaling | Coordinate matrix sub-grids, radial sweeps, and bypassing Google's 60-result search limit. |
| 🤖 [**AI Spec Website Generator & Preview**](AI_WEBSITE_GENERATOR.md) | Sales Conversion Engine | Gemini 2.5 Flash integration, niche design tokens, public `/preview/{uuid}` route, and interactive claim header. |
| ✉️ [**Email Outreach & Templates System**](EMAIL_OUTREACH_AND_TEMPLATES.md) | Cold Outreach | Dynamic placeholder interpolation (`{{business_name}}`, `{{demo_website_url}}`), POS UI templates, and delivery logs. |
| 📬 [**Unified Email Inbox Hub (Gmail & Hostinger)**](EMAIL_INBOX_INTEGRATION.md) | In-App Messaging | Google OAuth 2.0, Hostinger IMAP/SMTP sync, thread association with CRM leads, and direct replies. |
| 👥 [**Team Invitations & Workspace Management**](TEAM_INVITATIONS_AND_WORKSPACE.md) | Collaboration | Cryptographic invitation tokens, dedicated no-reply SMTP mailer, and seat cap enforcement. |
| 🕷️ [**Python Chromium Crawler Service**](PYTHON_CRAWLER.md) | Discovery Engine B | Playwright browser crawler, FastAPI microservice, and non-invasive CAPTCHA human verification. |
| 🚀 [**Production Deployment & Hostinger Guide**](DEPLOYMENT_GUIDE.md) | DevOps & Operations | Automated GitHub Actions CI/CD, Hostinger shared hosting, `.htaccess` routing, cron jobs, and queue setup. |
| 🧭 [**Google Places Feature Roadmap**](GOOGLE_PLACES_FEATURES_EXPANSION.md) | Product Roadmap | Price tiers, operational health scanner, review sentiment analysis, and tech stack fingerprinting. |
| 📈 [**Scaling Audit & Architecture Roadmap**](SCALING_AND_ROADMAP.md) | Historical Architecture | Memory optimization, OpenSpout streaming, and Hostinger LVE resource analysis. |

---

## 🛠️ Technology Stack Summary

- **Backend Framework**: Laravel 12 (PHP 8.3)
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Frontend / Styling**: Blade Components + Vuexy / POS Glass Surface System (Tailwind + Vanilla CSS + Bootstrap 5 tokens)
- **Real-Time Streaming**: Server-Sent Events (SSE) native HTTP streaming
- **AI Engine**: Google Gemini 2.5 Flash (`gemini-2.5-flash`) via REST API
- **Data Exporting**: OpenSpout memory-safe streaming Excel (`.xlsx`), CSV, and JSON
- **Browser Automation (Optional)**: Python 3.12+ / FastAPI / Playwright Chromium
- **Mail & Deliverability**: Hostinger SMTP/IMAP, Google Gmail API (OAuth 2.0), Live DNS/MX Validator
- **Hosting & CI/CD**: Hostinger Shared Hosting (`leads.obtainsolutions.com`) via GitHub Actions CI/CD
