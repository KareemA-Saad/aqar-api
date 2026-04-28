## **🔍 Search Endpoints Overview**

---

## **🌟 NEW: Global Search (Unified Search)**
```
GET /api/v1/tenant/{tenant}/realestate/search
```
**Unified search across all entity types** (Properties, Compounds, Areas, Developers) with intelligent relevance scoring.

### **When to Use Global Search:**
- ✅ User wants to search everything with one query
- ✅ Don't know if user is looking for properties, compounds, or areas
- ✅ Want unified, relevant results from all entity types
- ✅ Mobile/simple UI where multiple search types aren't practical

### **Key Features:**
- 🎯 Searches 4 entity types simultaneously
- 📊 Intelligent relevance scoring (exact match, starts-with, contains, description)
- ⚡ Results cached for 5 minutes with tag-based invalidation
- 📱 Responsive: ~10-50ms for cache hits
- 🔗 Unified response format for all entity types

### **Available Filters:**
| Filter | Type | Required | Options |
|--------|------|----------|---------|
| `q` | string | ✅ Yes | Min 2 chars, max 100 chars |
| `entity_type` | string | ❌ No | `all` (default), `properties`, `compounds`, `areas`, `developers` |
| `purpose` | string | ❌ No | `sale`, `rent` |
| `property_type_id` | integer | ❌ No | Any existing property type ID |
| `min_price` | number | ❌ No | Numeric value |
| `max_price` | number | ❌ No | Numeric value |
| `bedrooms` | integer | ❌ No | 0-10 |
| `bathrooms` | integer | ❌ No | 0-10 |
| `area_id` | integer | ❌ No | Any existing area ID |
| `developer_id` | integer | ❌ No | Any existing developer ID |
| `amenities` | string/array | ❌ No | Comma-separated IDs: `1,2,3` |
| `finishing` | string | ❌ No | `unfinished`, `semi_finished`, `finished`, `luxury` |
| `per_page` | integer | ❌ No | 1-100, default 15 |
| `page` | integer | ❌ No | Default 1 |

### **Relevance Scoring Algorithm:**
Results sorted by relevance score (descending):
- **Exact match** on title: +100 points
- **Starts with** search term: +80 points
- **Contains** search term: +60 points
- **Description** contains search term: +40 points
- **Featured** items: +15 bonus points
- **Recently created** (≤30 days): +10 bonus points
- **Has image/logo**: +5 bonus points

### **Example Requests:**

#### Simple search:
```
GET /api/v1/tenant/my-tenant/realestate/search?q=penthouse
```

#### Sale properties only:
```
GET /api/v1/tenant/my-tenant/realestate/search?q=villa&purpose=sale
```

#### Budget search:
```
GET /api/v1/tenant/my-tenant/realestate/search?q=apartment&min_price=100000&max_price=500000
```

#### Advanced filters:
```
GET /api/v1/tenant/my-tenant/realestate/search?q=luxury&purpose=sale&bedrooms=3&min_price=500000&area_id=2&amenities=1,2,3
```

#### Compounds only:
```
GET /api/v1/tenant/my-tenant/realestate/search?q=emirate&entity_type=compounds
```

#### Paginated results:
```
GET /api/v1/tenant/my-tenant/realestate/search?q=apartment&page=2&per_page=20
```

### **Response Format:**
```json
{
  "data": [
    {
      "type": "property",
      "id": 123,
      "title": "Luxury Penthouse",
      "url": "/properties/luxury-penthouse-123",
      "image": "https://cdn.example.com/image.jpg",
      "relevance_score": 95,
      "data": {
        "price": 1500000,
        "purpose": "sale",
        "bedrooms": 4,
        "bathrooms": 3,
        "area": "Downtown Dubai",
        "location": {"latitude": 25.2048, "longitude": 55.2708}
      }
    },
    {
      "type": "compound",
      "id": 45,
      "title": "Emirates Hills",
      "relevance_score": 85,
      "data": {
        "developer": "Emaar Properties",
        "min_price": 500000,
        "max_price": 3000000,
        "properties_count": 245
      }
    }
  ],
  "meta": {
    "total": 2,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "counts_by_type": {
      "properties": 1,
      "compounds": 1,
      "areas": 0,
      "developers": 0
    }
  },
  "filters_applied": {
    "q": "Dubai",
    "entity_type": "all",
    "purpose": null,
    "min_price": null,
    "max_price": null,
    "bedrooms": null
  }
}
```

### **Complete Reference:**
📖 **See [Global Search Documentation](./GLOBAL_SEARCH.md)** for complete parameter descriptions, advanced use cases, and entity-specific behaviors.

---

## **🔄 When to Use Specialized Endpoints Instead:**

| Endpoint | Better For |
|----------|-----------|
| `/search/properties` | Advanced property-only filtering (delivery year, min_area, max_area) |
| `/search/compounds` | Finding communities/projects specifically |
| `/search/autocomplete` | Quick suggestions while typing |
| `/search/facets` | Building complex filter UI with facet options |
| `/search/popular` | Trending searches |
| `/search/nearby` | GPS-based location search |
| `/map/*` | Map-based visualization |

---

### **1. General Property Search**
```
GET /api/v1/tenant/{tenant}/realestate/search/properties
```
**Full-text search across properties** with advanced filtering:

#### **Available Filters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `q` | string | Full-text search in title & description | `villa new cairo` |
| `area_id` | integer | Filter by area/location (includes children) | `5` |
| `compound_id` | integer | Filter by specific compound | `12` |
| `property_type_id` | integer | Filter by property type | `3` |
| `developer_id` | integer | Filter by developer | `8` |
| `purpose` | enum | Sale or rent | `sale`, `rent` |
| `min_price` | number | Minimum price | `2000000` |
| `max_price` | number | Maximum price | `5000000` |
| `min_area` | number | Minimum area in sqm | `100` |
| `max_area` | number | Maximum area in sqm | `500` |
| `bedrooms` | integer | Number of bedrooms (>=) | `3` |
| `bathrooms` | integer | Number of bathrooms (>=) | `2` |
| `finishing` | enum | Finishing level | `unfinished`, `semi_finished`, `fully_finished`, `furnished` |
| `delivery_year` | integer | Expected delivery year | `2025` |
| `amenities` | string/array | Comma-separated amenity IDs | `1,2,5,8` |
| `featured` | boolean | Featured properties only | `true` |
| `sort` | enum | Sort field | `created_at`, `price`, `area`, `bedrooms`, `views_count` |
| `direction` | enum | Sort direction | `asc`, `desc` |
| `per_page` | integer | Results per page (max 100) | `15` |
| `page` | integer | Page number | `1` |

**Example:**
```
?q=villa+new+cairo&min_price=2000000&bedrooms=4&purpose=sale&amenities=1,5,8
```

---

### **2. Compound/Project Search**
```
GET /api/v1/tenant/{tenant}/realestate/search/compounds
```
**Search compounds/projects** with comprehensive filtering.

#### **Available Filters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `q` | string | Full-text search in name & description | `palm hills` |
| `area_id` | integer | Filter by area/location (includes children) | `5` |
| `developer_id` | integer | Filter by developer | `8` |
| `min_price` | number | Minimum starting price | `1500000` |
| `max_price` | number | Maximum starting price | `8000000` |
| `featured` | boolean | Featured compounds only | `true` |
| `sort` | enum | Sort field | `created_at`, `name`, `min_price`, `total_units` |
| `direction` | enum | Sort direction | `asc`, `desc` |
| `per_page` | integer | Results per page | `15` |

**Example:**
```
?q=palm+hills&area_id=5&developer_id=3&min_price=2000000
```

---

### **3. Autocomplete (Smart Suggestions)**
```
GET /api/v1/tenant/{tenant}/realestate/search/autocomplete
```
**Instant search suggestions** for areas, compounds, developers, and properties.

#### **Available Filters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `q` | string | Search query (min 2 characters) | `new ca` |
| `limit` | integer | Max suggestions to return (max 20) | `10` |

**Returns suggestions for:**
- 🏘️ Areas/locations
- 🏢 Compounds/projects  
- 👷 Developers
- 🏠 Properties

**Example:**
```
?q=new+ca&limit=10
```

**Response:**
```json
{
  "data": [
    {"type": "area", "id": 5, "title": "New Cairo", "slug": "new-cairo", "url": "/areas/5-new-cairo"},
    {"type": "compound", "id": 12, "title": "New Capital Gardens", "slug": "new-capital-gardens"},
    {"type": "property", "id": 150, "title": "Villa in New Cairo"}
  ]
}
```

---

### **4. Search Facets (Dynamic Filters)**
```
GET /api/v1/tenant/{tenant}/realestate/search/facets
```
**Get available filter options with counts** for building dynamic filter UI.

#### **Available Filters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `area_id` | integer | Scope facets to specific area | `5` |
| `purpose` | enum | Filter by purpose | `sale`, `rent` |

**Returns facets with counts for:**
- Property types (with property counts)
- Areas/locations (with property counts)
- Developers (with property counts)
- Price ranges (with distribution)
- Bedroom counts (with property counts)

**Perfect for building UI like:** "Show 145 properties in New Cairo"

**Example:**
```
?area_id=5&purpose=sale
```

---

### **5. Popular Searches**
```
GET /api/v1/tenant/{tenant}/realestate/search/popular
```
**Get trending search terms** based on user activity.

#### **Available Filters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `limit` | integer | Number of results (max 20) | `10` |

**Example:**
```
?limit=10
```

---

### **6. Nearby Properties (Geo-Search)**
```
GET /api/v1/tenant/{tenant}/realestate/search/nearby
```
**Find properties near a GPS location** using coordinates.

#### **Required Parameters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `latitude` | float | Latitude coordinate | `30.0444` |
| `longitude` | float | Longitude coordinate | `31.2357` |
| `radius` | integer | Search radius in km (1-100) | `5` |
| `limit` | integer | Maximum results (max 50) | `10` |

**Example:**
```
?latitude=30.0444&longitude=31.2357&radius=5&limit=20
```

**Response includes distance:**
```json
{
  "data": [
    {
      "id": 123,
      "title": "Villa...",
      "distance_km": 2.5
    }
  ]
}
```

---

### **7. Map-Based Search**

#### **A. Properties in Map Viewport**
```
GET /api/v1/tenant/{tenant}/realestate/map/properties
```
**Get all properties visible in a map view** using viewport bounds.

##### **Required Parameters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `ne_lat` | float | Northeast corner latitude | `30.1` |
| `ne_lng` | float | Northeast corner longitude | `31.5` |
| `sw_lat` | float | Southwest corner latitude | `29.9` |
| `sw_lng` | float | Southwest corner longitude | `31.1` |

##### **Optional Filters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `property_type_id` | integer | Filter by property type | `3` |
| `min_price` | number | Minimum price | `2000000` |
| `max_price` | number | Maximum price | `5000000` |
| `bedrooms` | integer | Number of bedrooms | `3` |
| `purpose` | enum | Sale or rent | `sale`, `rent` |

**Example:**
```
?ne_lat=30.1&ne_lng=31.5&sw_lat=29.9&sw_lng=31.1&property_type_id=3&purpose=sale
```

---

#### **B. Property Clusters**
```
GET /api/v1/tenant/{tenant}/realestate/map/clusters
```
**Get clustered markers for map display** with counts and price statistics.

##### **Required Parameters:**
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `zoom` | integer | Map zoom level (1-20) | `12` |
| `ne_lat` | float | Northeast corner latitude | `30.1` |
| `ne_lng` | float | Northeast corner longitude | `31.5` |
| `sw_lat` | float | Southwest corner latitude | `29.9` |
| `sw_lng` | float | Southwest corner longitude | `31.1` |

**Zoom Level Guidelines:**
- **1-5**: Country level
- **6-10**: City level
- **11-15**: Neighborhood level
- **16+**: Street level

**Returns:**
- Property counts per cluster
- Average/min/max prices
- Cluster coordinates

**Perfect for interactive maps (Google Maps/Leaflet)!**

**Example:**
```
?zoom=12&ne_lat=30.1&ne_lng=31.5&sw_lat=29.9&sw_lng=31.1
```

**Response:**
```json
{
  "data": [
    {
      "latitude": 30.0444,
      "longitude": 31.2357,
      "count": 15,
      "avg_price": 2500000,
      "min_price": 1800000,
      "max_price": 3500000
    }
  ]
}
```

---

## **📊 Summary**

| Feature | Endpoint | Use Case |
|---------|----------|----------|
| Full-text search | `/search/properties` | Main search bar |
| Autocomplete | `/search/autocomplete` | Search suggestions |
| Facets | `/search/facets` | Dynamic filter UI |
| Popular | `/search/popular` | Trending searches |
| Nearby | `/search/nearby` | Location-based search |
| Map view | `/map/properties` | Map listings |
| Map clusters | `/map/clusters` | Map markers |

All endpoints are **fully documented in Swagger** and ready for Postman/Apidog! 🎯