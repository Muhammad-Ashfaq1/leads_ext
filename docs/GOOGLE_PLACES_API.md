# Leads Engine — Google Places API Integration Guide

The **Google Places Platform API** is the primary, production-ready extraction engine in Leads Engine. It allows users to query Google Maps' complete global database of businesses directly using standard HTTPS API requests with **zero Python code and zero VPS dependencies**.

---

## 1. How It Works

1. **Search Request**: The client submits a prompt (e.g. `Roofing Contractors`) and location (e.g. `Dallas, TX` or `75001`).
2. **Google Places Text Search API**:
   - Query: `https://places.googleapis.com/v1/places:searchText`
   - Field Mask: `places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.primaryTypeDisplayName,places.photos`
3. **Pre-Extraction Criteria (API Filters)**:
   - **Require Website**: Skips places without an official website.
   - **Require Phone**: Skips places without phone numbers.
   - **Require Email**: Scrapes the discovered website to ensure email exists before saving.
   - **Min Star Rating**: Filters out businesses below specified rating threshold (e.g. 4.0+).
4. **Website Email Discovery**:
   - For every business with a website, Laravel inspects the homepage and `/contact` pages in the background using regex email pattern extraction (`[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}`).
5. **Real-time SSE Streaming**:
   - Each lead is formatted with company name, category, phone, email, rating, address, and avatar, saved to MySQL, and streamed to the UI.

---

## 2. API Key Hierarchy

Leads Engine supports both **Global API Keys** and **Tenant-Specific API Keys**:

1. **Tenant API Key (Highest Priority)**:
   - Configured by Tenant Admins in the UI under **Extractor Settings** (`/settings`).
   - Encrypted and stored in the `tenants.google_maps_api_key` database column.
2. **Global API Key (Fallback)**:
   - Configured in the server `.env` file (`GOOGLE_MAPS_API_KEY=AIzaSy...`).
   - If a tenant has not configured their own key, the system automatically uses the platform's global shared key.
3. **Prompt-level API Key**:
   - Users can temporarily supply an API key in the search UI without saving it permanently.

---

## 3. How to Obtain a Google Maps API Key

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new Project (or select an existing one).
3. Navigate to **APIs & Services** > **Library**.
4. Search for and enable:
   - **Places API (New)**
   - **Maps JavaScript API** (optional, for client previews)
   - **Geocoding API**
5. Navigate to **APIs & Services** > **Credentials**.
6. Click **+ Create Credentials** > **API Key**.
7. *(Recommended)* Click **Edit API Key** and restrict key usage to **Places API**.
8. Paste the API key into your `.env` file or into the **Extractor Settings** page in Leads Engine.

---

## 4. Google Places API Pricing & Free Tier

- Google provides a **$200 monthly free credit** on Google Cloud Platform for every billing account.
- **Places Text Search (New)** costs approximately $0.032 per request.
- With the $200 free credit, you can perform thousands of lead extraction queries monthly at zero out-of-pocket cost.
