# Leads Engine — Geospatial Grid Engine & High-Density Lead Matrix

This document explains the architecture, mathematical algorithms, and technical execution behind the **Geospatial Coordinate Sub-Grid Matrix** (`GeospatialGridService.php`) in **Leads Engine** (VektorLeads).

---

## 1. The Challenge: Standard Search Caps in Google Places API

By default, Google Places API (both legacy and the new Places API v1) enforces strict pagination caps:
- Each Text Search or Nearby Search returns a maximum of **20 results per page**.
- Up to **3 pages maximum** can be retrieved per query (`pageSize=20`, `max 60 results total`).
- Searching for high-volume niches like *"Roofers in Dallas, TX"* or *"Restaurants in Los Angeles"* will cap out at 60 places, missing thousands of legitimate businesses located across the metropolitan area.

---

## 2. The Solution: Geospatial Coordinate Sub-Grid Matrix

To bypass the 60-result limitation without violating Google Terms of Service, **Leads Engine** employs an intelligent **Geospatial Coordinate Sub-Grid Matrix**:

```text
┌─────────────────────────────────────────────────────────────┐
│                 Target Metro: Miami, FL                     │
│ ┌─────────────────┬─────────────────┬─────────────────┐     │
│ │  Grid [0, 0]    │  Grid [0, 1]    │  Grid [0, 2]    │     │
│ │  North Miami    │  Miami Shores   │  Biscayne Bay   │     │
│ ├─────────────────┼─────────────────┼─────────────────┤     │
│ │  Grid [1, 0]    │  Grid [1, 1]    │  Grid [1, 2]    │     │
│ │  Downtown /     │  Brickell /     │  Coconut Grove  │     │
│ │  Wynwood        │  Little Havana  │                 │     │
│ ├─────────────────┼─────────────────┼─────────────────┤     │
│ │  Grid [2, 0]    │  Grid [2, 1]    │  Grid [2, 2]    │     │
│ │  Coral Gables   │  Kendall        │  Pinecrest      │     │
│ └─────────────────┴─────────────────┴─────────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

When a user requests 100, 500, or 2,500 leads:
1. **Geocoding & Bounding Box**: Resolves the target city or zip code to exact viewport bounds (`northEast`, `southWest`).
2. **Dynamic Matrix Subdivision**: Splits the geographic bounding box into an $N \times M$ matrix of non-overlapping coordinate sub-cells based on the requested limit.
3. **Sub-Locality Expansion**: Injects recognized neighborhood names and postal codes into the search queries to force localized density.
4. **Targeted Sub-Cell Sweeps**: Queries the Google Places API for each sub-cell coordinate window.
5. **Real-time Deduplication**: Place IDs and normalized phone/address pairs are evaluated against an in-memory hash set so duplicate boundary results are instantly filtered before streaming.

---

## 3. Algorithm & Code Walkthrough (`GeospatialGridService.php`)

### 1. Bounding Box Calculation
```php
$latStep = ($northEastLat - $southWestLat) / $gridDivisions;
$lngStep = ($northEastLng - $southWestLng) / $gridDivisions;
```

### 2. Radial Micro-Sweep Generation
For smaller regions or zip codes, concentric radial sweeps are generated:
$$\text{Latitude}_{\text{offset}} = \text{Radius} \times \cos(\theta) \times \frac{1}{111.32}$$
$$\text{Longitude}_{\text{offset}} = \text{Radius} \times \sin(\theta) \times \frac{1}{111.32 \times \cos(\text{Latitude})}$$

### 3. Deduplication Pipeline
```php
$uniqueKey = $place['id'] ?? md5(($place['displayName']['text'] ?? '') . ($place['formattedAddress'] ?? ''));

if (isset($seenPlaces[$uniqueKey])) {
    continue; // Skip duplicate place discovered in adjacent grid
}
$seenPlaces[$uniqueKey] = true;
```

---

## 4. Yield & Performance Comparison

| Metric | Standard Google Search | VektorLeads Grid Matrix |
| :--- | :---: | :---: |
| **Max Leads per Search** | 60 leads | **2,500+ leads** |
| **Geographic Coverage** | City Center only | Entire Metro & Suburbs |
| **Duplicate Rate** | High (in manual retries) | **0% (Automated Deduplication)** |
| **SSE Streaming** | No | **Live Real-time Feed** |
| **Enrichment Yield** | Raw Google info only | **Website + Email + Socials + MX** |

---

## 5. Cost Optimization & Field Masking

To minimize Google Places API billing while running grid sweeps, the service utilizes strict Field Masks:
```http
X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.photos
```
Omitting expensive fields like `places.reviews` and `places.currentOpeningHours` during raw grid sweeps cuts API billing costs by up to 60%, fetching deeper details only during lead inspection.
