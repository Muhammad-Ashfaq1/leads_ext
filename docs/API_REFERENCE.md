# Leads Engine — Complete REST & SSE API Reference

This document provides the complete API specification for all internal REST, Server-Sent Events (SSE), and webhook endpoints in Leads Engine / VektorLeads.

---

## 📑 Table of Contents

1. [Authentication & Session](#1-authentication--session)
2. [Lead Discovery & Extraction Engine](#2-lead-discovery--extraction-engine)
3. [Leads Management & CRM](#3-leads-management--crm)
4. [AI Spec Website Generator & Preview](#4-ai-spec-website-generator--preview)
5. [Email Templates & Outreach](#5-email-templates--outreach)
6. [Unified Email Inbox Hub (Gmail & Hostinger)](#6-unified-email-inbox-hub-gmail--hostinger)
7. [Team Members & Invitations](#7-team-members--invitations)
8. [Multi-Tenant Administration (Super Admin)](#8-multi-tenant-administration-super-admin)
9. [Workspace Settings & Profile](#9-workspace-settings--profile)

---

## 1. Authentication & Session

### 1.1 Login
`POST /login`
- **Access**: Public / Guest
- **Parameters**:
  - `email` (string, required): User email address.
  - `password` (string, required): User password.
  - `remember` (boolean, optional): Maintain persistent session.
- **Response**: Redirect to `/dashboard` on success, or redirect with error bag on 422.

### 1.2 Logout
`POST /logout`
- **Access**: Authenticated
- **Response**: Invalidate session, regenerate CSRF token, redirect to `/login`.

### 1.3 Stop Impersonation
`GET /impersonate/stop`
- **Access**: Authenticated with active impersonation session
- **Response**: Reverts session to original Super Admin or Workspace Admin account.

---

## 2. Lead Discovery & Extraction Engine

### 2.1 Start Extraction Job
`POST /api/extractor/start`
- **Access**: Authenticated (All Roles)
- **Rate Limit**: 20 requests / minute

#### Request Payload
```json
{
  "prompt": "Personal Injury Lawyers in Miami FL",
  "location": "Miami, FL",
  "engineMode": "google_api", // "google_api" | "live" (Chromium) | "mock"
  "limit": 100,
  "apiKey": "AIzaSy...", // Optional; defaults to tenant or system key
  "preFilters": {
    "requireWebsite": true,
    "requirePhone": true,
    "requireEmail": false,
    "withoutWebsite": false,
    "minRating": 4.0
  }
}
```

#### Response (200 OK)
```json
{
  "success": true,
  "jobId": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "streamUrl": "/api/extractor/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d/stream",
  "status": "starting",
  "mode": "google_api",
  "limit": 100
}
```

---

### 2.2 Real-Time SSE Stream
`GET /api/extractor/{jobUuid}/stream`
- **Access**: Authenticated (Workspace Owner / Tenant)
- **Protocol**: Server-Sent Events (`text/event-stream`)

#### Emitted Events:
1. **`started`**: Emitted when search query is initialized with Google Places API or Python browser crawler.
   ```json
   {
     "type": "started",
     "status": "starting",
     "message": "Connected to Google Places API."
   }
   ```
2. **`lead`**: Emitted for each discovered and enriched business lead.
   ```json
   {
     "type": "lead",
     "lead": {
       "id": 142,
       "uuid": "8b9e9df4-6d9b-4682-84db-6be545f94bfa",
       "name": "Miami Premier Law Group",
       "category": "Personal Injury Attorney",
       "phone": "+1 305-555-0199",
       "email": "contact@miamilaw.com",
       "emails": ["contact@miamilaw.com", "intake@miamilaw.com"],
       "website": "https://miamilaw.com",
       "rating": 4.9,
       "reviewsCount": 184,
       "address": "701 Brickell Ave, Miami, FL 33131",
       "avatarUrl": "https://lh3.googleusercontent.com/p/...",
       "googleMapsUrl": "https://maps.google.com/?cid=...",
       "social_links": {
         "linkedin": "https://linkedin.com/company/miamilaw",
         "facebook": "https://facebook.com/miamilaw"
       },
       "email_deliverable": true,
       "mx_valid": true
     },
     "leadsExtracted": 1,
     "totalExtracted": 1
   }
   ```
3. **`human_verification_required`** *(Browser Mode only)*: Emitted if Google presents a CAPTCHA.
   ```json
   {
     "type": "human_verification_required",
     "status": "waiting_for_human_verification",
     "message": "Google unusual traffic challenge detected. Please complete verification.",
     "timeoutSeconds": 300
   }
   ```
4. **`completed`**: Emitted when extraction finishes.
   ```json
   {
     "type": "completed",
     "status": "completed",
     "leadsExtracted": 100,
     "totalExtracted": 100,
     "emailsFound": 74,
     "websitesFound": 92,
     "durationSeconds": 14.8
   }
   ```

---

### 2.3 Job Status & Controls
- **`GET /api/extractor/{jobUuid}/status`**: Returns current progress and counts.
- **`POST /api/extractor/{jobUuid}/stop`**: Immediately halts an active job while retaining discovered leads.
- **`POST /api/extractor/{jobUuid}/focus`**: Brings browser crawler window to focus (local dev mode).
- **`POST /api/extractor/{jobUuid}/verify-complete`**: Signals manual CAPTCHA completion.

---

## 3. Leads Management & CRM

### 3.1 Leads Directory
`GET /leads`
- **Query Parameters**:
  - `q` (string): Search across business name, address, email, phone.
  - `category` (string): Filter by business niche.
  - `status` (string): Filter by lead status (`lead`, `contacted`, `replied`, `closed`, `discarded`).
  - `has_email` (`yes` | `no`): Filter leads with or without verified email.
  - `has_website` (`yes` | `no`): Filter leads with or without website.
  - `min_rating` (float): Minimum star rating (e.g. `4.0`).
  - `is_saved` (`yes` | `no`): Filter saved leads.

### 3.2 Bulk Lead Actions
`POST /api/leads/bulk-action`
- **Request Body**:
  ```json
  {
    "action": "save", // "save" | "discard" | "delete" | "save_all_job"
    "lead_ids": [142, 143, 144],
    "job_id": 48 // Required only when action is "save_all_job"
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "affected_count": 3,
    "message": "3 leads successfully updated."
  }
  ```

### 3.3 Selective Export
`POST /api/leads/export-selected`
- **Request Body**:
  ```json
  {
    "lead_ids": [142, 143, 144],
    "format": "excel" // "excel" | "csv" | "json"
  }
  ```
- **Response**: Streamed file download (`.xlsx` or `.csv`) or JSON payload.

### 3.4 Full Dataset Export
- `GET /leads/export/excel`: Streamed `.xlsx` export of current filtered master database.
- `GET /jobs/{jobId}/export?format=excel`: Streamed `.xlsx` export for a specific extraction job.

---

## 4. AI Spec Website Generator & Preview

### 4.1 Generate Spec Website Demo
`POST /api/leads/{id}/generate-demo`
- **Access**: Authenticated workspace member
- **Description**: Sends the lead's business profile to Gemini AI (`gemini-2.5-flash`), generating design tokens, value propositions, service items, and landing page copy.
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "lead_id": 142,
    "preview_url": "https://leads.obtainsolutions.com/preview/8b9e9df4-6d9b-4682-84db-6be545f94bfa"
  }
  ```

### 4.2 Public Spec Website Preview
`GET /preview/{uuid}`
- **Access**: Public / Unauthenticated
- **Description**: Renders the complete, high-converting interactive spec website generated by Gemini AI with niche-specific Tailwind styling, interactive service cards, dynamic hero section, and "Claim This Website" conversion header.

---

## 5. Email Templates & Outreach

### 5.1 List Templates
- `GET /email-templates`: Web management view.
- `GET /api/email-templates/list`: Returns JSON list of available templates for outreach modal dropdowns.

### 5.2 Create / Edit / Delete Template
- `POST /email-templates`: Create new template (`name`, `subject`, `body_html`, `category`).
- `PUT /email-templates/{id}`: Update existing template.
- `DELETE /email-templates/{id}`: Delete custom template.
- `POST /email-templates/{id}/default`: Mark template as the workspace default.
- `POST /email-templates/restore-defaults`: Re-provisions system POS default templates.

### 5.3 Send Outreach Email
`POST /api/leads/send-email`
- **Request Body**:
  ```json
  {
    "lead_id": 142,
    "template_id": 3, // Optional if subject and body provided
    "subject": "Quick question regarding {{business_name}}'s digital presence",
    "body_html": "<p>Hi team at {{business_name}}, ...</p>",
    "account_id": 1 // Optional connected Gmail or Hostinger account ID
  }
  ```
- **Supported Dynamic Tags**:
  - `{{business_name}}` — Business name
  - `{{email}}` — Target email address
  - `{{phone}}` — Business phone number
  - `{{city}}` — Discovered city
  - `{{category}}` — Industry category
  - `{{website}}` — Existing website URL
  - `{{demo_website_url}}` — AI-generated spec website demo link
  - `{{pos_url}}` — Obtain Solutions POS platform URL
  - `{{app_url}}` — Current platform application URL
  - `{{sender_name}}` — Sending user's full name

---

## 6. Unified Email Inbox Hub (Gmail & Hostinger)

### 6.1 View Inbox
`GET /gmail`
- **Access**: All workspace members
- **Query Parameters**:
  - `account_id` (integer, optional): Filter by connected email account.
  - `filter` (string, optional): `all`, `unread`, `starred`, `leads`.
  - `q` (string, optional): Search keyword across sender, recipient, subject, snippet.

### 6.2 Connect Accounts
- `GET /gmail/connect`: Redirects to Google OAuth consent screen for Gmail permissions.
- `GET /gmail/callback`: Google OAuth callback handling token exchange and refresh token storage.
- `POST /gmail/connect-hostinger`: Connects Hostinger / custom Webmail via IMAP & SMTP.
  - Parameters: `email`, `password`, `imap_host`, `imap_port`, `smtp_host`, `smtp_port`.
- `POST /gmail/disconnect/{account}`: Disconnects account and removes credentials.

### 6.3 Message Operations
- `POST /gmail/sync/{account?}`: Triggers immediate IMAP / Gmail API synchronization.
- `GET /gmail/messages/{message}`: Returns JSON details of email message, marks as read, and displays thread history.
- `POST /gmail/messages/{message}/reply`: Sends reply email via SMTP/Gmail API with proper `In-Reply-To` and `References` headers.
- `POST /gmail/messages/{message}/star`: Toggles message starred status.
- `POST /gmail/messages/{message}/read`: Toggles read/unread status.
- `DELETE /gmail/messages/{message}`: Deletes cached email message.

---

## 7. Team Members & Invitations

### 7.1 Send Invitation
`POST /invitations`
- **Access**: Workspace Admin / Super Admin
- **Request Body**:
  ```json
  {
    "email": "sarah@agency.com",
    "role": "member" // "admin" | "member"
  }
  ```
- **Behavior**: Verifies workspace staff seat limit, generates 64-character secure token, and dispatches invitation email.

### 7.2 Revoke Invitation
`DELETE /invitations/{id}`
- **Access**: Workspace Admin / Super Admin

### 7.3 Accept Invitation (Guest Flow)
- `GET /invitation/{token}`: Renders registration form for invited user.
- `POST /invitation/{token}`:
  - Parameters: `name`, `password`, `password_confirmation`.
  - Creates active user assigned to the inviting workspace and redirects to dashboard.

---

## 8. Multi-Tenant Administration (Super Admin)

### 8.1 Manage Tenants
- `GET /tenants`: View all workspaces, usage metrics, and subscription plans.
- `POST /tenants`: Create new tenant workspace.
- `GET /tenants/{id}`: Detailed tenant metrics and configuration.
- `PUT /tenants/{id}`: Update tenant quota, subscription plan, active status.
- `GET /tenants/{id}/impersonate`: Start impersonation session as tenant admin.

### 8.2 Manage Subscription Plans
- `GET /plans`: List all SaaS tiers.
- `POST /plans`: Create new plan with monthly lead quota, price, and feature flags.
- `PUT /plans/{id}`: Update plan details.
- `DELETE /plans/{id}`: Soft-delete/deactivate plan.

---

## 9. Workspace Settings & Profile

### 9.1 Workspace Settings
`GET /settings` & `PUT /settings`
- **Access**: Workspace Admin & Super Admin
- **Configurable Options**:
  - Organization Name and Slug.
  - Dedicated Google Maps Places API key.
  - Default search limit and extraction pre-filters.
  - Connected email accounts management.
  - Team member seats and pending invitations.

### 9.2 User Profile
- `GET /profile`: User account view.
- `PUT /profile`: Update name and contact email.
- `PUT /profile/password`: Update security credentials.
