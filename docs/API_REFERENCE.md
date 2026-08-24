# Leads Engine — REST & SSE API Reference

This document outlines the internal REST and Server-Sent Events (SSE) streaming API endpoints used by Leads Engine.

---

## 1. Extraction Endpoints

### 1.1 Start Extraction Job
`POST /api/extractor/start`

Initiates an extraction job in either Google Places API mode or Browser mode.

#### Request Body
```json
{
  "prompt": "Find personal injury lawyers in Austin TX",
  "location": "Austin, TX",
  "engineMode": "google_api",
  "limit": 50,
  "apiKey": "AIzaSy...",
  "preFilters": {
    "requireWebsite": true,
    "requirePhone": true,
    "requireEmail": false,
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
  "limit": 50
}
```

---

### 1.2 Real-time SSE Stream
`GET /api/extractor/{jobId}/stream`

Establishes a persistent Server-Sent Events (SSE) connection streaming real-time status updates and discovered leads.

#### SSE Events Emitted

##### `started`
```json
{
  "type": "started",
  "status": "starting",
  "message": "Connected to Google Places API."
}
```

##### `lead`
```json
{
  "type": "lead",
  "lead": {
    "id": 142,
    "name": "Austin Premier Law Group",
    "category": "Personal Injury Attorney",
    "phone": "+1 512-555-0199",
    "email": "contact@austinlaw.com",
    "website": "https://austinlaw.com",
    "rating": 4.9,
    "reviewsCount": 184,
    "address": "701 Brazos St, Austin, TX 78701",
    "avatarUrl": "https://lh3.googleusercontent.com/p/...",
    "googleMapsUrl": "https://maps.google.com/?cid=..."
  },
  "leadsExtracted": 1,
  "totalExtracted": 1
}
```

##### `human_verification_required` *(Browser mode only)*
```json
{
  "type": "human_verification_required",
  "status": "waiting_for_human_verification",
  "message": "Google unusual traffic challenge detected. Please complete verification in the browser window.",
  "timeoutSeconds": 300
}
```

##### `completed`
```json
{
  "type": "completed",
  "status": "completed",
  "leadsExtracted": 50,
  "totalExtracted": 50,
  "emailsFound": 38,
  "websitesFound": 47,
  "durationSeconds": 12.4
}
```

---

### 1.3 Stop Extraction Job
`POST /api/extractor/{jobId}/stop`

Stops an in-progress extraction task immediately while preserving all already-extracted leads in the database.

#### Response (200 OK)
```json
{
  "success": true,
  "status": "stopped",
  "leadsExtracted": 23
}
```

---

## 2. Export Endpoints

### 2.1 Export Job Leads to Excel (`.xlsx`)
`GET /jobs/{jobUuid}/export?format=excel`

Streams a formatted Microsoft Excel (`.xlsx`) spreadsheet containing all leads extracted in that job session.

### 2.2 Export Master Database Leads
`GET /leads/export/excel?category=Dentist&has_email=yes&min_rating=4.0`

Streams a filtered Excel spreadsheet of leads across all completed tasks for the current organization.
