Tenant Real Estate Endpoints

This document outlines the API endpoints required for the Tenant Real Estate website (the "real estate itself").

**Base URL**: `/api/v1`
**Common Headers**:
- `X-Tenant-ID`: `{subdomain}` (Required for all tenant-specific requests)
- `Accept`: `application/json`

## Properties

### 1. List All Properties
Fetch a paginated list of properties with optional filters.

**Endpoint**: `GET /tenant/{subdomain}/properties`

**Query Parameters**:
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 15)
- `sort_by` (string): `newest`, `price_low`, `price_high`, `area_low`, `area_high`
- `keyword` (string): Search term
- `type_id` (integer): Filter by property type (e.g., Apartment, Villa)
- `compound_id` (integer): Filter by compound
- `developer_id` (integer): Filter by developer
- `status` (string): `sale`, `rent`
- `min_price` (number)
- `max_price` (number)
- `min_area` (number)
- `max_area` (number)
- `bedrooms` (integer)
- `bathrooms` (integer)
- `location_id` (integer): Filter by city/location
- `is_featured` (boolean): Filter for featured properties only

**Response Example**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Modern Apartment in Downtown",
      "slug": "modern-apartment-in-downtown",
      "price": 500000,
      "currency": "USD",
      "price_formatted": "$500,000",
      "type": { "id": 1, "name": "Apartment" },
      "status": "sale",
      "thumbnail_url": "https://...",
      "location": {
        "id": 10,
        "city": "New York",
        "state": "NY"
      },
      "features": {
        "bedrooms": 2,
        "bathrooms": 2,
        "area": 1200,
        "area_unit": "sqft"
      },
      "created_at": "2024-01-01T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 75
  }
}
```

### 2. Get Property Details
Fetch full details for a single property.

**Endpoint**: `GET /tenant/{subdomain}/properties/{id}`
*(Or `GET /tenant/{subdomain}/properties/{slug}` if using slugs)*

**Response Example**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "usr_id": 5,
    "title": "Modern Apartment in Downtown",
    "description": "Full description of the property...",
    "price": 500000,
    "price_formatted": "$500,000",
    "type": { "id": 1, "name": "Apartment" },
    "status": "sale",
    "images": [
      { "id": 1, "url": "https://..." },
      { "id": 2, "url": "https://..." }
    ],
    "amenities": [
      { "id": 1, "name": "Swimming Pool", "icon": "pool" },
      { "id": 2, "name": "Gym", "icon": "dumbbell" }
    ],
    "location": {
      "id": 10,
      "city": "New York",
      "address": "123 Main St",
      "latitude": 40.7128,
      "longitude": -74.0060
    },
    "agent": {
      "id": 5,
      "name": "Jane Smith",
      "photo_url": "https://...",
      "phone": "+1234567890",
      "email": "jane@example.com"
    },
    "video_url": "https://youtube.com/...",
    "virtual_tour_url": "https://...",
    "created_at": "2024-01-01T12:00:00Z"
  }
}
```

### 3. Get Featured Properties
Quick access to featured properties (can also be handled by the List endpoint with `?is_featured=1`).

**Endpoint**: `GET /tenant/{subdomain}/properties/featured`

---

## Compounds

### 4. List All Compounds
Fetch a list of compounds (projects).

**Endpoint**: `GET /tenant/{subdomain}/compounds`

**Response Example**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Green Valley Compound",
      "slug": "green-valley",
      "image": "https://...",
      "property_count": 15,
      "location": { "id": 1, "city": "Cairo" }
    }
  ]
}
```

### 5. Get Compound Details
**Endpoint**: `GET /tenant/{subdomain}/compounds/{id}`

---

## Filter Data & Lookups

### 6. Get Property Types
List available property types.

**Endpoint**: `GET /tenant/{subdomain}/property-types`

### 7. Get Developers
List available real estate developers.

**Endpoint**: `GET /tenant/{subdomain}/developers`

**Response Example**:
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "SODIC", "logo": "..." },
    { "id": 2, "name": "Palm Hills", "logo": "..." }
  ]
}
```

### 8. Search Autocomplete
Predictive search for compounds, locations, and developers.

**Endpoint**: `GET /tenant/{subdomain}/search/autocomplete`

**Query Parameters**:
- `query` (string): The search term (e.g., "Tie")

**Response Example**:
```json
{
  "success": true,
  "data": [
    { "type": "compound", "id": 1, "name": "Tierra", "location": "6th Settlement" },
    { "type": "location", "id": 5, "name": "Tierra Alta", "location": "Spain" }
  ]
}
```

### 9. Get Areas / Locations
List available locations/areas with hierarchy support.

**Endpoint**: `GET /tenant/{subdomain}/locations`

**Query Parameters**:
- `parent_id` (integer): Filter by parent area (e.g., get sub-areas of New Cairo).
- `is_root` (boolean): If true, returns only top-level areas (Super Areas).

**Response Example**:
```json
{
  "success": true,
  "data": [
    { 
      "id": 1, 
      "name": "New Cairo", 
      "slug": "new-cairo", 
      "type": "super_area",
      "parent_id": null, 
      "count": 150 
    },
    { 
      "id": 5, 
      "name": "6th Settlement", 
      "slug": "6th-settlement", 
      "type": "area",
      "parent_id": 1, 
      "count": 20 
    }
  ]
}
```

### 10. Get Area Details
Fetch details for a specific area/location by slug, including SEO data.

**Endpoint**: `GET /tenant/{subdomain}/locations/{slug}`

**Response Example**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "New Cairo",
    "slug": "new-cairo",
    "description": "One of the most prestigious areas...",
    "parent_id": null,
    "compounds_count": 45,
    "properties_count": 1200,
    "children": [
        { "id": 5, "name": "6th Settlement", "slug": "6th-settlement" }
    ]
  }
}
```

### 11. Get Amenities
List all available amenities.

**Endpoint**: `GET /tenant/{subdomain}/amenities`

---

## Inquiries & Contact

### 12. Send Property Inquiry
Submit a lead/inquiry form for a specific property.

**Endpoint**: `POST /tenant/{subdomain}/properties/{id}/inquiry`

**Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+123456789",
  "message": "I am interested in this property."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Inquiry sent successfully."
}
```

### 13. General Contact
Submit existing contact page form.

**Endpoint**: `POST /tenant/{subdomain}/contact`

**Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "subject": "General Question",
  "message": "..."
}

