# Leads Engine — Production Deployment Guide

This guide covers deploying **Leads Engine** to **Hostinger Shared Hosting** (e.g. `leads.obtainsolutions.com`) using automated **GitHub Actions CI/CD**.

---

## 1. Hosting Environment Requirements

- **Web Server**: Apache / LiteSpeed (Hostinger default) with `mod_rewrite` enabled.
- **PHP Version**: **PHP 8.2 or PHP 8.3**.
- **PHP Extensions**: `BCMath`, `Ctype`, `cURL`, `DOM`, `Fileinfo`, `JSON`, `Mbstring`, `OpenSSL`, `PDO`, `PDO_MySQL`, `Tokenizer`, `XML`.
- **Database**: MySQL 8.0+ or MariaDB 10.4+.
- **SSH Access**: Enabled on Hostinger hPanel.

---

## 2. GitHub Actions Secrets Configuration

In your GitHub repository, navigate to **Settings** > **Secrets and variables** > **Actions** > **New repository secret**:

| Secret Name | Example Value | Description |
| :--- | :--- | :--- |
| `SSH_PASSWORD` | `your_hostinger_ssh_password` | The SSH password for your Hostinger account `u407529782`. |

---

## 3. Deployment Pipeline Overview (`deploy.yml`)

The repository includes a ready-to-use GitHub Actions workflow [`.github/workflows/deploy.yml`](file:///Users/macbookpro2019/Projects/leads-info/.github/workflows/deploy.yml):

```yaml
name: Deploy Leads Engine Application

on:
  push:
    branches:
      - main
      - develop

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Code
      - name: Set up PHP 8.3
      - name: Install Laravel Dependencies (Composer)
      - name: Deploy to Hostinger (rsync via SSH)
      - name: Run Remote Laravel Commands (Migrate, Optimize Cache, Symlink Storage)
```

Every push to `main` or `develop` triggers an automated zero-downtime deployment.

---

## 4. Hostinger Domain & Subdomain Setup

1. **Create Subdomain**:
   - In Hostinger hPanel, go to **Domains** > **Subdomains**.
   - Create: `leads.obtainsolutions.com`.
   - Set custom folder / document root to:
     ```text
     /public_html/leads
     ```
2. **Root `.htaccess` Routing**:
   The repository includes a root [`.htaccess`](file:///Users/macbookpro2019/Projects/leads-info/.htaccess) file:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   ```
   This ensures that all web traffic hitting `/public_html/leads` is automatically routed into `public/index.php`.

---

## 5. Production `.env` Setup

Log into your server via SSH or file manager and create `/home/u407529782/domains/obtainsolutions.com/public_html/leads/.env`:

```dotenv
APP_NAME="Leads Engine"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://leads.obtainsolutions.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u407529782_leads
DB_USERNAME=u407529782_lead_user
DB_PASSWORD=YourStrongDatabasePassword

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Google Maps Places API Configuration
GOOGLE_MAPS_API_KEY=AIzaSyYourGlobalProductionApiKey

# Optional Python Scraper URL (if hosted on external VPS)
EXTRACTOR_SERVICE_URL=
EXTRACTOR_ALLOW_MOCK=false
```

---

## 6. Initial Database Seeding (First-Time Setup)

Once the files and `.env` are configured, SSH into your server:

```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads
php artisan migrate --force
php artisan db:seed --force
```

This creates the default Super Admin and demo tenant accounts.

---

## 7. Verification Checklist

- [ ] Visit `https://leads.obtainsolutions.com/login` and verify HTTPS loads cleanly.
- [ ] Log in with your Super Admin credentials.
- [ ] Test a Google Places API extraction (e.g. "Dentists in Miami").
- [ ] Verify that real-time leads appear and download as an Excel spreadsheet (`.xlsx`).
- [ ] Verify that team members and tenant quotas update correctly.

