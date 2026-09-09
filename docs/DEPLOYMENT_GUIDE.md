# Leads Engine — Production Deployment & Hostinger Operations Guide

This guide details the complete deployment, configuration, and maintenance procedures for running **Leads Engine** (VektorLeads) in production on **Hostinger Shared / Cloud Hosting** (`leads.obtainsolutions.com`) using automated **GitHub Actions CI/CD**.

---

## 1. Environment & Server Requirements

- **PHP Version**: **PHP 8.2 or 8.3** (Hostinger hPanel: Advanced > PHP Configuration).
- **Web Server**: Apache / LiteSpeed with `mod_rewrite` enabled.
- **Database**: MySQL 8.0+ or MariaDB 10.4+.
- **Required PHP Extensions**:
  `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `intl`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`.
- **SSH Access**: Enabled via Hostinger hPanel (Advanced > SSH Access).

---

## 2. GitHub Actions Automated CI/CD Pipeline

The repository includes an automated zero-downtime deployment workflow [`.github/workflows/deploy.yml`](file:///Users/macbookpro2019/Projects/leads-info/.github/workflows/deploy.yml).

### Configured Secrets in GitHub Repository
Navigate to **Settings** > **Secrets and variables** > **Actions**:

| Secret Name | Example Value | Description |
| :--- | :--- | :--- |
| `SSH_PASSWORD` | `YourSecretPassword` | SSH password for Hostinger user `u407529782` |

### Pipeline Workflow:
1. Triggered on every `git push` to `main` or `develop`.
2. Sets up PHP 8.3 and builds Composer dependencies (`composer install --no-dev --optimize-autoloader`).
3. Uses `rsync` over SSH to sync updated files to `/home/u407529782/domains/obtainsolutions.com/public_html/leads`.
4. Runs remote Artisan commands:
   ```bash
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   ```

---

## 3. Hostinger Subdomain & Document Root Configuration

1. Log into **Hostinger hPanel** > **Websites** > **Subdomains**.
2. Create subdomain: `leads.obtainsolutions.com`.
3. Set custom folder to:
   ```text
   /public_html/leads
   ```
4. **Root `.htaccess` Setup**:
   The application includes a root [`.htaccess`](file:///Users/macbookpro2019/Projects/leads-info/.htaccess) file that routes incoming requests into `public/index.php`:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   ```

---

## 4. Production `.env` Specification

Create `/home/u407529782/domains/obtainsolutions.com/public_html/leads/.env`:

```dotenv
APP_NAME="VektorLeads"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://leads.obtainsolutions.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# MySQL Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u407529782_leads
DB_USERNAME=u407529782_lead_user
DB_PASSWORD=YourStrongDatabasePassword

# Drivers
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Primary SMTP (Transactional & System Emails)
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=support@obtainsolutions.com
MAIL_PASSWORD=YourSupportEmailPassword
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=support@obtainsolutions.com
MAIL_FROM_NAME="${APP_NAME}"

# Dedicated No-Reply SMTP (User Invitations & Alerts)
MAIL_NOREPLY_HOST=smtp.hostinger.com
MAIL_NOREPLY_PORT=465
MAIL_NOREPLY_USERNAME=noreply@obtainsolutions.com
MAIL_NOREPLY_PASSWORD=YourNoreplyEmailPassword
MAIL_NOREPLY_ADDRESS=noreply@obtainsolutions.com
MAIL_NOREPLY_NAME="${APP_NAME}"

# Discovery & Google Places API
GOOGLE_MAPS_API_KEY=AIzaSyYourGlobalGooglePlacesKey

# Google OAuth (Gmail Inbox Integration)
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-google-client-secret
GOOGLE_GMAIL_REDIRECT_URI=https://leads.obtainsolutions.com/gmail/callback

# Gemini AI Spec Website Generator
GEMINI_API_KEY=AIzaSyYourGeminiApiKey
GEMINI_MODEL=gemini-2.5-flash

# Extractor Engine Defaults
EXTRACTOR_SERVICE_URL=
EXTRACTOR_ALLOW_MOCK=false
EXTRACTOR_DEFAULT_LIMIT=50
```

---

## 5. Hostinger Cron Jobs & Queue Workers

To support background email inbox synchronization, scheduled outreach, and queue jobs:

1. In **Hostinger hPanel**, navigate to **Advanced** > **Cron Jobs**.
2. Add a Cron Job running **Every Minute** (`* * * * *`):
   ```bash
   /usr/bin/php /home/u407529782/domains/obtainsolutions.com/public_html/leads/artisan schedule:run >> /dev/null 2>&1
   ```
3. Add a Cron Job running **Every 5 Minutes** for queue processing:
   ```bash
   /usr/bin/php /home/u407529782/domains/obtainsolutions.com/public_html/leads/artisan queue:work --stop-when-empty >> /dev/null 2>&1
   ```

---

## 6. Post-Deployment Verification Checklist

- [ ] **HTTPS & Security**: Confirm SSL certificate is active at `https://leads.obtainsolutions.com`.
- [ ] **Super Admin Login**: Authenticate at `/login` with seeded credentials.
- [ ] **Google Places API**: Run a test extraction for "Roofers in Austin TX" and verify real-time SSE stream.
- [ ] **Spec Website Generator**: Click "Generate Demo" on an extracted lead and verify public rendering at `/preview/{uuid}`.
- [ ] **Email Inbox Hub**: Test connecting a Hostinger Webmail or Gmail account at `/settings` and verify message sync.
- [ ] **Excel Export**: Export a job or master leads list and verify valid `.xlsx` spreadsheet download.
- [ ] **User Invitations**: Test inviting a new user and completing signup at `/invitation/{token}`.
