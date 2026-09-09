# Leads Engine — Unified Email Inbox Hub (Gmail & Hostinger)

**Leads Engine** features a centralized in-app Email Hub (`/gmail`) that integrates with **Google Gmail (OAuth 2.0)** and **Hostinger Webmail / Custom Domains (IMAP & SMTP)**. This enables sales teams to manage replies and follow-ups without leaving the application.

---

## 1. Supported Providers & Connection Protocols

```text
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                Connected Email Accounts                                │
├───────────────────────────────────────────┬────────────────────────────────────────────┤
│           Provider: Google Gmail          │     Provider: Hostinger / Custom IMAP      │
├───────────────────────────────────────────┼────────────────────────────────────────────┤
│ • Protocol: HTTPS via Google API Client   │ • Protocol: SSL / TLS IMAP & SMTP          │
│ • Auth: OAuth 2.0 with Refresh Token      │ • Auth: Encrypted Host, Port, & Password   │
│ • Scopes: `gmail.readonly`, `gmail.send`  │ • Ports: IMAP (993 / SSL), SMTP (465/SSL)  │
│ • Connection Route: `/gmail/connect`      │ • Connection Route: `/gmail/connect-host`  │
└───────────────────────────────────────────┴────────────────────────────────────────────┘
```

---

## 2. Google Gmail OAuth 2.0 Setup

### Google Cloud Console Configuration:
1. Navigate to [Google Cloud Console](https://console.cloud.google.com/) > **APIs & Services** > **Credentials**.
2. Create an **OAuth 2.0 Client ID** (Web application).
3. Set **Authorized redirect URIs**:
   ```text
   https://leads.obtainsolutions.com/gmail/callback
   ```
   *(For local testing: `http://localhost:8000/gmail/callback` or `http://extractor.test/gmail/callback`)*
4. Enable the **Gmail API** under **APIs & Services** > **Library**.
5. Copy your **Client ID** and **Client Secret** into your `.env`:
   ```dotenv
   GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-your-client-secret
   GOOGLE_GMAIL_REDIRECT_URI=https://leads.obtainsolutions.com/gmail/callback
   ```

### Connecting in the App:
1. Workspace Admins navigate to **Settings** > **Email Integrations** (`/settings#email`).
2. Click **"Connect Gmail Account"**.
3. Authorize Google permissions. Upon redirection to `/gmail/callback`, the access and refresh tokens are securely stored in the `gmail_accounts` table.

---

## 3. Hostinger Webmail / Custom Domain IMAP Setup

For businesses hosting email on Hostinger (e.g., `you@yourdomain.com`), Leads Engine provides native IMAP synchronization:

### Configuration in UI (`POST /gmail/connect-hostinger`):
- **Email Address**: `user@obtainsolutions.com`
- **Password**: Your mailbox password
- **IMAP Host**: `imap.hostinger.com` (Port: `993`, Encryption: `SSL`)
- **SMTP Host**: `smtp.hostinger.com` (Port: `465`, Encryption: `SSL`)

The credentials are encrypted using Laravel's application key before storage in `gmail_accounts`.

---

## 4. Message Synchronization & Thread Linking

The synchronization engine runs via `App\Services\GmailService` and `App\Services\HostingerEmailService`:

1. **Incremental Sync**:
   - Queries new messages received since the last synchronization timestamp (`historyId` for Gmail, `SINCE` date for IMAP).
2. **RFC 822 MIME Parsing**:
   - Extracts sender address, display name, recipient, subject, snippet, HTML body, and date.
3. **Automatic CRM Lead Association**:
   - The sync engine inspects the sender email:
     ```php
     $lead = ExtractedLead::where('tenant_id', $account->tenant_id)
         ->whereJsonContains('emails', $senderEmail)
         ->first();
     ```
   - If a match is found, the message is automatically linked (`gmail_messages.lead_id = $lead->id`), and the lead's status is promoted to `replied`.

---

## 5. Inbox Operations & Two-Way Messaging

Inside the Email Hub (`/gmail`):

- **Sidebar Folder Filters**: View all messages, unread only, starred, or messages specifically linked to leads (`filter=leads`).
- **Interactive Message Drawer**: Clicking a message opens the full HTML message view, displaying sender details, lead tags, and previous thread history.
- **In-App Direct Reply (`POST /gmail/messages/{id}/reply`)**:
  - Automatically structures the reply with proper RFC headers:
    - `In-Reply-To: <original-message-id>`
    - `References: <original-message-id>`
  - Sends the reply via the connected account's SMTP or Gmail API.
- **Star / Read / Delete**: Quick actions to keep sales inboxes organized.

---

## 6. Hostinger Shared Hosting Setup & Cron Jobs

To automatically check and fetch incoming emails continuously without having to click "Sync Messages" manually, configure cron jobs in **Hostinger hPanel**.

### ⚠️ Shared Hosting Rule (CloudLinux LVE Limit)
On Hostinger Shared Hosting, **do NOT run persistent background daemons** like `php artisan queue:work --timeout=0` without exit parameters. CloudLinux LVE will terminate long-running processes or exhaust your allocated Entry Processes (causing HTTP 503 errors).

Instead, use **self-terminating workers** (`--stop-when-empty`) scheduled via Hostinger's Cron Manager.

---

### Step-by-Step Hostinger hPanel Setup:

1. Log in to **Hostinger hPanel**.
2. Go to **Advanced** > **Cron Jobs**.
3. Under **Add New Cron Job**, select **Custom** or specify interval.

#### Option A: Recommended — The Master Laravel Scheduler (Every 1 Minute)
Because `routes/console.php` already schedules `email:sync` every 5 minutes (`withoutOverlapping()`), running `schedule:run` every minute handles email syncing and all other background tasks automatically.

- **Schedule**: Every minute (`* * * * *`)
- **Command**:
  ```bash
  /usr/bin/php /home/u407529782/domains/obtainsolutions.com/public_html/leads/artisan schedule:run >> /dev/null 2>&1
  ```

#### Option B: Dedicated Email Sync Cron Job (Every 5 Minutes)
If you want a dedicated cron specifically for email inbox syncing:

- **Schedule**: Every 5 minutes (`*/5 * * * *`)
- **Command**:
  ```bash
  /usr/bin/php /home/u407529782/domains/obtainsolutions.com/public_html/leads/artisan email:sync --limit=50 >> /dev/null 2>&1
  ```

#### Option C: Queue Worker for Background Outreach (Every 2 Minutes)
For processing queued jobs (such as queued email campaigns and data tasks):

- **Schedule**: Every 2 minutes (`*/2 * * * *`)
- **Command**:
  ```bash
  /usr/bin/php /home/u407529782/domains/obtainsolutions.com/public_html/leads/artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1
  ```
- **Why these flags?**:
  - `--stop-when-empty`: Exits immediately as soon as all queued emails are sent.
  - `--tries=3`: Retries failed jobs up to 3 times before moving to `failed_jobs`.
  - `--max-time=50`: Shuts down cleanly after 50 seconds if jobs are continuously being processed, preventing Hostinger from force-killing the process.

