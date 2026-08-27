# VektorLeads — Installation & Setup Guide

This guide walks you through setting up and running **VektorLeads** locally for development or in production on your server.

---

## 📋 System Requirements

Ensure your environment meets the following minimum requirements:

- **PHP**: `^8.2` or `^8.3`
  - Required Extensions: `pdo`, `pdo_sqlite` (or `pdo_mysql`), `curl`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- **Composer**: `^2.x`
- **Database**: SQLite 3 (default, zero-configuration) or MySQL 8.0+ / MariaDB 10.5+
- **Web Server**: Built-in PHP server, Laravel Herd, Laravel Valet, Nginx, or Apache

---

## ⚡ Quick Start (5-Minute Setup)

### Step 1: Clone the Repository
```bash
git clone https://github.com/Muhammad-Ashfaq1/leads_ext.git leads-info
cd leads-info
```

### Step 2: Install PHP Dependencies
```bash
composer install
```

### Step 3: Configure Environment
Copy the sample environment file:
```bash
cp .env.example .env
```

Generate the unique application encryption key:
```bash
php artisan key:generate
```

### Step 4: Configure Database & Run Migrations

#### Option A: SQLite (Quickest, Recommended for Local Dev)
Ensure the database file exists:
```bash
touch database/database.sqlite
```
Verify your `.env` has:
```dotenv
DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database/database.sqlite (or leave blank for default database/database.sqlite)
```

#### Option B: MySQL / MariaDB (Recommended for Production)
Update `.env` with your database credentials:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=leads_engine
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run the database migrations and seed default administrators, tenants, and email templates:
```bash
php artisan migrate --seed
```

### Step 5: Link Storage & Clear Cache
```bash
php artisan storage:link
php artisan optimize:clear
```

### Step 6: Start the Application Server
```bash
php artisan serve
```
Open your browser and navigate to: **`http://127.0.0.1:8000`** (or your local virtual host, e.g. `http://leads-info.test`).

---

## 🔑 Default Login Credentials

After running `php artisan migrate --seed`, the database is seeded with two administrative accounts:

| Role | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@obtainsolutions.com` | `Obtain@2026!` | Global SaaS console, multi-tenant management, all workspaces |
| **Workspace Admin** | `admin@obtainsolutions.com` | `Obtain@2026!` | Lead extraction, CRM database, cold outreach, template manager |

> 🔒 **Security Notice**: Change these default passwords immediately after your initial login from the **My Profile** page (`/profile`).

---

## ✉️ Custom Business Email / SMTP Configuration

To send live cold outreach emails directly from your own domain email address (e.g. `alex@yourcompany.com`), configure your SMTP settings in `.env`:

### 1. Google Workspace / Gmail
```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-name@yourcompany.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-name@yourcompany.com"
MAIL_FROM_NAME="Your Company Name"
```
*(Note: For Google Workspace, generate a 16-character **App Password** under Google Account Security settings).*

### 2. Microsoft 365 / Outlook
```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-name@yourcompany.com
MAIL_PASSWORD=your-microsoft-password
MAIL_ENCRYPTION=STARTTLS
MAIL_FROM_ADDRESS="your-name@yourcompany.com"
MAIL_FROM_NAME="Your Company Name"
```

### 3. Custom Server SMTP / cPanel / Postmark / SendGrid
```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_USERNAME=outreach@yourdomain.com
MAIL_PASSWORD=your-secure-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="outreach@yourdomain.com"
MAIL_FROM_NAME="Your Name"
```

After updating `.env`, reload the configuration cache:
```bash
php artisan config:clear
```

---

## ⚙️ Extraction Engine & API Settings

Configure your extraction parameters from the user interface or `.env`:

1. Log into your dashboard as an admin.
2. Navigate to **Settings** (`/settings`) from the sidebar.
3. **General & Limits**:
   - Set your company/workspace name.
   - Select default extraction engine (*Google Places API* or *Browser Automation*).
   - Set default extraction limit per batch (10, 25, 50, 100, 200, up to 2,500 leads).
   - Toggle **Automatic Email Enrichment** & **Automatic Social Profile Extraction**.
4. **Google Places API Key**:
   - Provide your Google Places API Key under the **Google Places API** tab to enable instant high-density extraction and coordinate grid search.

---

## ⚡ Background Queue Worker Setup

For high-volume asynchronous extraction jobs and large bulk email outreach campaigns, configure background workers:

In development:
```bash
php artisan queue:work
```

In production, run workers continuously using **Supervisor**:

```ini
[program:leads-engine-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/leads-info/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/leads-info/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 🚀 Production Deployment & Optimization

When deploying to a production server (Ubuntu/Debian, Forge, DigitalOcean, AWS, etc.):

### 1. Optimize Laravel Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Set Proper Directory Permissions
```bash
sudo chown -R www-data:www-data /var/www/leads-info
sudo chmod -R 775 /var/www/leads-info/storage
sudo chmod -R 775 /var/www/leads-info/bootstrap/cache
```

### 3. Nginx Server Configuration Sample
```nginx
server {
    listen 80;
    server_name leads.yourdomain.com;
    root /var/www/leads-info/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🧪 Automated Testing

Run the automated test suite to ensure all 66+ feature and unit tests pass:

```bash
php artisan test
```

---

## 🛠️ Maintenance & Troubleshooting

- **Clear all application caches:**
  ```bash
  php artisan optimize:clear
  ```
- **Re-run database seeders:**
  ```bash
  php artisan db:seed
  ```
- **Inspect application log:**
  ```bash
  tail -f storage/logs/laravel.log
  ```

