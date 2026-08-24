# Leads Engine — SaaS Multi-Tenancy & Role-Based Access Control

**Leads Engine** is built from the ground up as a multi-tenant SaaS application. It allows multiple organizations (tenants) to independently use the platform with strict data privacy, isolated lead databases, customizable quotas, and role-based permissions.

---

## 1. User Roles & Permissions Matrix

| Role | Access Level | Description & Capabilities |
| :--- | :--- | :--- |
| 👑 **Super Admin** | Platform-wide | • Global visibility across all SaaS tenants.<br>• Create, suspend, and edit tenant accounts & plan quotas.<br>• Access platform-wide analytics and job history.<br>• Access `/tenants` management panel. |
| 🏢 **Tenant Admin** | Organization-wide | • Administer team members for their organization.<br>• Configure organization settings & custom Google Maps API keys.<br>• Run lead extractions & export leads to Excel (`.xlsx`).<br>• Access `/dashboard`, `/extractor`, `/leads`, `/jobs`, `/users`, and `/settings`. |
| 👤 **Team Member** | Organization-wide (Limited) | • Execute lead extraction tasks within organization quota.<br>• Browse & search tenant lead database.<br>• Export leads to Excel.<br>• Manage personal profile & security settings. |

---

## 2. Multi-Tenant Scoping

### Database Layer Scoping
- Every `User`, `ExtractionJob`, and `ExtractedLead` table record carries a `tenant_id` foreign key.
- Eloquent queries automatically scope data to the authenticated user's `tenant_id`:
  ```php
  // Example tenant scoping
  if (! $user->isSuperAdmin()) {
      $query->where('tenant_id', $user->tenant_id);
  }
  ```

### Tenant Middleware
- `App\Http\Middleware\TenantMiddleware`: Validates that the active tenant is in `active` status before processing requests.
- `App\Http\Middleware\RoleMiddleware`: Validates user role requirements (e.g. `RoleMiddleware:super_admin` for `/tenants`).

---

## 3. Subscription Plans & Quota Management

Tenants are configured with subscription tiers and lead discovery quotas:

| Plan Tier | Monthly Quota | Default Engine | Features Included |
| :--- | :--- | :--- | :--- |
| **Starter** | 10,000 leads / mo | Google Places API | Standard search, Phone & Website extraction, Excel export. |
| **Pro** | 25,000 leads / mo | Google Places API / Browser | High-volume extraction, Auto Email Discovery, Pre-filters. |
| **Enterprise** | 50,000+ leads / mo | Google Places API / Browser | Unlimited team seats, Dedicated API Key support, Priority streaming. |

### Live Quota Tracking
- When a lead is saved, the tenant's `leads_extracted_count` is incremented.
- The dashboard displays a real-time progress bar of quota utilization.
- If quota is exceeded, extraction tasks prompt for quota upgrade.

---

## 4. Default Seeded Accounts

For testing and local development, the database seeder (`DatabaseSeeder.php`) provides default accounts:

| Role | Email | Password | Organization | Plan |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@leads.test` | `password` | *Global Platform Owner* | Enterprise |
| **Tenant Admin** | `admin@acme.com` | `password` | Acme Corporation | Enterprise |
| **Tenant Admin** | `admin@nexus.com` | `password` | Nexus Digital Marketing | Pro |

> [!IMPORTANT]
> Change the default passwords immediately upon deploying to production.

