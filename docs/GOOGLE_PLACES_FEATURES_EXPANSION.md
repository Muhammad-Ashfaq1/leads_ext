# VektorLeads — Google Maps & Places API Feature Expansion Roadmap

This document outlines high-impact, revenue-generating features and technical architecture specifications that can be built on top of **Google Maps & Places APIs (New Places API v1)** inside **VektorLeads**.

---

## 🧭 1. Overview & Competitive Advantage

Google Maps is the most accurate, real-time database of commercial entities worldwide. By combining Google Places API with **VektorLeads' proprietary Geospatial Grid Matrix**, **deep website enrichment**, **3-Tier DNS/MX email verification**, and **automated outreach**, the platform can provide high-ticket marketing agencies and sales teams with unmatched prospecting intelligence.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                 VektorLeads Architecture                              │
├─────────────────────────┬─────────────────────────────┬────────────────────────────────┤
│ 🛰️ Discovery Layer      │ 🧠 Intelligence Layer       │ 🚀 Conversion Layer            │
│  • Places API (New v1)  │  • Tech Stack Fingerprint   │  • 1-Click AI Cold Pitcher     │
│  • Geospatial Subgrids  │  • DNS / MX Live Validator  │  • Multi-Step SMTP Drip        │
│  • Price Level / Hours  │  • Reputation & Sentiment   │  • "No Website" Pitch Engine   │
└─────────────────────────┴─────────────────────────────┴────────────────────────────────┘
```

---

## 💎 2. High-Value Features & Technical Specifications

### Feature 1: "Without Website" Opportunity & Pitch Engine
* **Target Audience**: Web design agencies, SEO freelancers, digital marketing agencies, SaaS website builders.
* **How It Works**:
  - Leverages VektorLeads' pre-extraction `without_website` filter to isolate verified Google Business profiles lacking a URL.
  - Automatically compiles local search metrics: Category ranking, review score, estimated missed monthly mobile searches.
  - **1-Click Pitch Generator**: Injects dynamic data into an outreach email:
    > *"Hi [Business Name], noticed your Google listing in [City] has a [Rating]★ rating from [Reviews] customers, but lacks an official website. You're likely losing ~200+ local customers every month to competitors..."*
* **API Fields Used**: `places.displayName`, `places.rating`, `places.userRatingCount`, `places.websiteUri`.

---

### Feature 2: Price Level & High-Ticket Tier Targeting (`priceLevel`)
* **Target Audience**: High-ticket B2B service providers, enterprise software vendors, luxury goods suppliers.
* **How It Works**:
  - Google Places returns `priceLevel` (`PRICE_LEVEL_INEXPENSIVE`, `PRICE_LEVEL_MODERATE`, `PRICE_LEVEL_EXPENSIVE`, `PRICE_LEVEL_VERY_EXPENSIVE`).
  - Add a **Budget Tier Filter** in VektorLeads so users can target businesses with high average ticket sizes (e.g., luxury aesthetic clinics, fine dining, commercial contractors).
* **API Fields Used**: `places.priceLevel`.

---

### Feature 3: Business Hours & Operational Health Scanner
* **Target Audience**: Booking software vendors, POS systems, after-hours answering services, delivery partners.
* **How It Works**:
  - Inspect `places.currentOpeningHours` and `places.regularOpeningHours`.
  - Filter by businesses open on weekends, 24/7 businesses, or businesses with limited operational hours to pitch automation, scheduling, or virtual receptionist tools.
* **API Fields Used**: `places.currentOpeningHours`, `places.regularOpeningHours`, `places.businessStatus`.

---

### Feature 4: Review Sentiment & Reputation Gap Analyzer
* **Target Audience**: Reputation management agencies, review automation SaaS, local SEO agencies.
* **How It Works**:
  - **Reputation Repair Leads**: Target businesses with `rating < 4.0` or businesses with high review counts but declining recent feedback.
  - **Review Growth Leads**: Target businesses with high ratings (`>= 4.8`) but low review counts (`< 10 reviews`) to pitch review-generation QR codes and SMS follow-ups.
* **API Fields Used**: `places.rating`, `places.userRatingCount`, `places.reviews`.

---

### Feature 5: Interactive Geospatial Radar & Heatmap Explorer
* **Target Audience**: Sales teams targeting specific commercial corridors, enterprise territory managers.
* **How It Works**:
  - Render an interactive MapLibre / Leaflet map directly in the extractor workspace.
  - Plot discovered businesses with color-coded pins:
    - 🟢 Green Pin: Verified Email + Website Available.
    - 🟡 Yellow Pin: Phone Only (No Website).
    - 🔵 Blue Pin: High-Ticket Tier / 4.5+ ★.
  - Allows spatial polygon drawing (*"Draw a circle around Downtown Chicago to extract all businesses in this radius"*).
* **API Fields Used**: `places.location.latitude`, `places.location.longitude`, `places.viewport`.

---

### Feature 6: Storefront Visual Preview & Photo Gallery
* **Target Audience**: Commercial signage companies, storefront renovation contractors, photographers.
* **How It Works**:
  - Google Places API returns `places.photos` references.
  - Fetch high-res storefront images using the Places Photo media endpoint:
    `https://places.googleapis.com/v1/{name=places/*/photos/*}/media?maxHeightPx=400&maxWidthPx=400&key=API_KEY`
  - Display thumbnail galleries in the lead profile drawer so users can visually verify physical locations before calling.
* **API Fields Used**: `places.photos`.

---

### Feature 7: Multi-Location & Franchise Chain Identifier
* **Target Audience**: Enterprise B2B SaaS, multi-location POS vendors, franchise consultants.
* **How It Works**:
  - Automatically detect multiple branches with matching brand names across distinct zip codes / cities (*e.g. "Apex Dental - North", "Apex Dental - West"*).
  - Group them under a **Parent Brand Portfolio** so agencies can pitch multi-location enterprise contracts rather than single-unit sales.

---

### Feature 8: Tech Stack & Digital Maturity Index
* **Target Audience**: Web development agencies, cybersecurity auditors, CRM resellers.
* **How It Works**:
  - When a website is discovered, VektorLeads' crawler fingerprints:
    - **CMS**: WordPress, Shopify, Squarespace, Wix, Webflow, Custom PHP.
    - **Analytics & Ad Pixels**: Google Analytics 4, Meta Pixel, TikTok Pixel.
    - **SSL & Speed**: SSL validity, mobile responsiveness.
  - Generates a **Digital Maturity Score (1–100)** to help users prioritize the most tech-forward or tech-deficient prospects.

---

## 🛠️ 3. Recommended Places API v1 Field Mask Configuration

To maximize data richness while maintaining optimal API cost efficiency, use the following field masks for Google Places Text Search (New):

```http
POST https://places.googleapis.com/v1/places:searchText
X-Goog-Api-Key: YOUR_API_KEY
X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress,places.location,places.rating,places.userRatingCount,places.priceLevel,places.websiteUri,places.nationalPhoneNumber,places.internationalPhoneNumber,places.currentOpeningHours,places.businessStatus,places.types,places.photos
Content-Type: application/json

{
  "textQuery": "Plumbing Contractors in Dallas, TX",
  "pageSize": 20
}
```

---

## 📈 4. Monetization & Pricing Tier Packaging

| Feature Module | Starter Plan | Professional Plan | Agency / Enterprise |
| :--- | :---: | :---: | :---: |
| **Cloud Matrix Discovery** | 500 Leads / mo | 5,000 Leads / mo | Unlimited |
| **"Without Website" Filter** | ✅ Included | ✅ Included | ✅ Included |
| **MX Email Deliverability Engine** | ✅ Included | ✅ Included | ✅ Included |
| **Price Tier / Rating Filters** | ❌ | ✅ Included | ✅ Included |
| **Custom SMTP Outreach** | 1 Account | 5 Accounts | Unlimited Workspaces |
| **Interactive Map Explorer** | ❌ | ✅ Included | ✅ Included |
| **AI Custom Pitch Generator** | ❌ | 100 Pitches / mo | Unlimited |
| **White-Label Client Reports** | ❌ | ❌ | ✅ Included |

---

*Document maintained for VektorLeads Core Product Architecture.*
