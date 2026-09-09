# Leads Engine — Team Invitations & Workspace Management

This document details the team member invitation system, security token lifecycle, seat enforcement, and workspace user management in **Leads Engine** (VektorLeads).

---

## 1. Overview & Invitation Architecture

Workspaces in Leads Engine allow agency owners and sales directors to collaborate with team members under a shared lead database while maintaining role security and individual quotas.

```
Workspace Admin
(Navigates to /settings#team)
       │
       ▼
Issues Invitation: POST /invitations
(Validates seat quota & generates 64-char crypto token)
       │
       ▼
No-Reply Mailer Dispatches Email
(Dedicated SMTP: noreply@obtainsolutions.com)
       │
       ▼
Invited User Receives Registration Link
(https://leads.obtainsolutions.com/invitation/{token})
       │
       ▼
Guest Form & Password Creation
(Account automatically activated & linked to tenant)
```

---

## 2. Token Security & Lifecycle

The invitation pipeline is governed by `App\Models\UserInvitation` and `App\Http\Controllers\InvitationController`:

### Token Generation:
- Generates a cryptographically secure 64-character token via `\Illuminate\Support\Str::random(64)`.
- Associated fields: `tenant_id`, `email`, `role`, `token`, `expires_at`, `created_at`.
- Default expiration: **48 hours**.

### Security Rules:
1. **Registered User Guard**: If an email is already associated with an active `User` record on the platform, invitation creation is blocked with a 422 error.
2. **Pending Duplicate Guard**: If a pending, unexpired invitation already exists for that email, duplicate dispatches are prevented.
3. **Seat Limit Enforcement**: Each tenant's subscription plan defines `max_users` (e.g. 5 seats for Growth, 10 for Agency). If current active users + pending invitations $\ge$ plan seat limit, the invitation is rejected with an upgrade notification.

---

## 3. Dedicated No-Reply Mailer Delivery

To preserve the reputation of primary customer-facing support mailboxes, invitations are delivered using a dedicated **No-Reply SMTP channel**:

```dotenv
MAIL_NOREPLY_HOST=smtp.hostinger.com
MAIL_NOREPLY_PORT=465
MAIL_NOREPLY_USERNAME=noreply@obtainsolutions.com
MAIL_NOREPLY_PASSWORD=YourPassword
MAIL_NOREPLY_ADDRESS=noreply@obtainsolutions.com
MAIL_NOREPLY_NAME="VektorLeads Invitations"
```

The invitation email template incorporates clean Vuexy / POS branding with a prominent Call to Action button: **"Accept Invitation & Join Workspace"**.

---

## 4. Invitation Acceptance & Registration Flow

1. The invitee clicks the link: `GET /invitation/{token}`.
2. If the token is invalid or expired, the user is presented with an error page offering to request a new invitation from their administrator.
3. If valid, the user sees an invitation acceptance form with their email address and organization pre-filled and locked.
4. The user enters their full name and chooses a secure password.
5. On `POST /invitation/{token}`:
   - A new `User` record is created with the pre-assigned `tenant_id` and `role`.
   - The used `UserInvitation` record is immediately deleted.
   - The user is automatically authenticated and redirected to `/dashboard`.

---

## 5. Team Management & Impersonation (`/settings#team`)

Inside the workspace settings:
- **Active Team Roster**: Lists all members, their roles, email addresses, and last active dates.
- **Pending Invitations**: Displays sent invitations with status and expiration timer. Admins can click **"Revoke"** to cancel the invite and free the seat slot immediately.
- **Staff Impersonation**: Admins can click **"Impersonate"** next to any team member to view the dashboard from their perspective. A persistent top bar allows exiting impersonation at any time.
