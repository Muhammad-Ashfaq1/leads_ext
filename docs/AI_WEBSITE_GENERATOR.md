# Leads Engine — AI Spec Website Generator & Demo Preview Guide

The **AI Spec Website Generator** is a conversion feature inside **Leads Engine (VektorLeads)**. It uses **Google Gemini 2.5 Flash** (`gemini-2.5-flash`) to generate high-converting, tailored specification websites for prospects lacking an online presence or possessing an outdated website.

---

## 1. Overview & Value Proposition

When pitching cold B2B prospects, sending a generic sales email yields low conversion rates. By contrast, sending a prospect a personalized live link to an interactive website built specifically for their brand and city creates an immediate "aha!" moment.

```
Discovered Business Lead
(e.g., "Austin Premier Roofing")
           │
           ▼
Gemini 2.5 Flash AI Engine
(Analyzes niche, location, ratings, services)
           │
           ▼
Custom Design Tokens & Conversion Copy
(Tailwind color schemes, hero layouts, testimonials)
           │
           ▼
Live Interactive Spec Website Preview
(Rendered at /preview/{uuid})
           │
           ▼
Direct Outreach with Dynamic Link
("Check your new site: {{demo_website_url}}")
```

---

## 2. Technical Architecture & Gemini Service

The generator is powered by `App\Services\GeminiWebsiteService`:

### API Communication:
- Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={API_KEY}`
- Payload structure: Strictly structured JSON prompt specifying required design tokens, headline styles, niche-specific services, trust indicators, and social proof.

### Dynamic Niche Design Token Mapping:
The service maps business categories to industry-tailored design systems:

| Category Vertical | Primary Tailwind Color | Font Family | Hero Section Layout |
| :--- | :--- | :--- | :--- |
| **Auto Repair / Mechanics** | `bg-red-600` | `font-sans` | `split-with-form` (Instant Booking) |
| **Real Estate & Penthouses** | `bg-slate-900` | `font-serif` | `gallery-grid` (Property Showcase) |
| **Law Firms & Legal** | `bg-amber-800` | `font-serif` | `centered-bold` (Authority & Trust) |
| **Roofing / Plumbing / Trades** | `bg-blue-600` | `font-sans` | `split-with-form` (Quote Request) |
| **Landscaping / Health / Eco** | `bg-emerald-600` | `font-sans` | `split-with-form` (Service Estimate) |
| **Fine Dining / Restaurants** | `bg-orange-600` | `font-serif` | `gallery-grid` (Menu & Ambiance) |

---

## 3. Schema & Generated Data Structure

Gemini returns an exhaustive JSON object stored in `extracted_leads.generated_website_content`:

```json
{
  "design_tokens": {
    "primary_color": "bg-red-600",
    "text_color": "text-red-600",
    "accent_color": "bg-amber-500",
    "font_family": "font-sans",
    "hero_layout": "split-with-form"
  },
  "copy": {
    "hero_badge": "Family Owned & Operated in Austin",
    "hero_headline": "Austin’s #1 Trusted Roofing Specialists",
    "hero_subheadline": "Precision residential roof replacements and storm repairs built to withstand Texas heat.",
    "primary_cta": "Request Free Estimate",
    "secondary_cta": "Explore Services",
    "urgency_note": "⚡ Same-Day Storm Inspections Available",
    "stats": [
      {"value": "15+", "label": "Years in Business"},
      {"value": "4.9 ★", "label": "Google Rating"},
      {"value": "2,400+", "label": "Roofs Repaired"}
    ],
    "about_text": "Austin Premier Roofing delivers exceptional roof restorations...",
    "niche_features": [
      {
        "title": "Emergency Leak Tarping",
        "description": "Rapid 2-hour response for severe storm leaks.",
        "icon_name": "shield",
        "badge": "Priority 24/7",
        "bullet_points": ["Zero deductible assistance", "Full documentation", "Trained technicians"]
      }
    ],
    "process_steps": [
      {"step": "01", "title": "Comprehensive Inspection", "description": "Digital aerial drone and attic inspection."},
      {"step": "02", "title": "Transparent Proposal", "description": "Itemized scope of work with fixed pricing."}
    ],
    "testimonials": [
      {
        "name": "Marcus V.",
        "rating": 5,
        "role": "Homeowner in Travis County",
        "comment": "Incredible turnaround after the spring hail storm!"
      }
    ]
  }
}
```

---

## 4. Public Spec Website Preview Route (`/preview/{uuid}`)

The public preview route [LeadPreviewController](file:///Users/macbookpro2019/Projects/leads-info/app/Http/Controllers/LeadPreviewController.php) serves the generated landing page:

- **Zero Authentication Required**: Designed to be clicked by prospects receiving cold emails.
- **Conversion Claim Header**: Displays a sticky top banner:
  > *"Are you the business owner of {Business Name}? This interactive spec website was designed for your brand. Claim this website or request custom changes."*
- **Device Simulator Controls**: Allows toggling between Desktop, Tablet, and Mobile viewport modes.
- **Interactive Forms**: Simulated service request and estimate booking forms that demonstrate high-converting lead capture.

---

## 5. Cold Outreach Integration (`{{demo_website_url}}`)

Sales teams can inject the generated preview link directly into their email templates:

```html
<p>Hi {{business_name}} team,</p>
<p>I noticed your listing in {{city}} has great customer reviews, but your online presence isn't capturing the traffic it deserves.</p>
<p>Our team took the liberty of building a full interactive website demo for your business:</p>
<p><a href="{{demo_website_url}}" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;border-radius:6px;text-decoration:none;font-weight:600;">View Your Demo Website ➔</a></p>
<p>Would love to hear your thoughts on this design!</p>
```

When sent, `EmailOutreachService` automatically replaces `{{demo_website_url}}` with the lead's unique public preview link (`https://leads.obtainsolutions.com/preview/{uuid}`).
