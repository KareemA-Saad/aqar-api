# Global Search - Complete API Reference

## Endpoint Details

### HTTP Method & Path
```
GET /api/v1/tenant/{tenant}/realestate/search
```

### Route Name
```
api.v1.tenant.realestate.search.global
```

### Authentication
Required: Bearer token (Sanctum guard: `sanctum_tenant_user`)

---

## Query Parameters Reference

### Parameter Index

| Parameter | Type | Required | Default | Range/Values | Notes |
|-----------|------|----------|---------|-------|-------|
| `q` | string | ✅ Yes | - | Min 2, Max 100 | Search term |
| `entity_type` | string | ❌ No | `all` | all, properties, compounds, areas, developers | Filter by type |
| `purpose` | string | ❌ No | - | sale, rent | Listing purpose |
| `property_type_id` | integer | ❌ No | - | 1-unlimited | Property type ID |
| `min_price` | decimal | ❌ No | - | 0-unlimited | Minimum price |
| `max_price` | decimal | ❌ No | - | 0-unlimited | Maximum price |
| `bedrooms` | integer | ❌ No | - | 0-10 | Minimum bedrooms |
| `bathrooms` | integer | ❌ No | - | 0-10 | Minimum bathrooms |
| `area_id` | integer | ❌ No | - | 1-unlimited | Area ID (includes children) |
| `developer_id` | integer | ❌ No | - | 1-unlimited | Developer ID |
| `amenities` | string/array | ❌ No | - | 1-unlimited | Comma-separated IDs or array |
| `finishing` | string | ❌ No | - | unfinished, semi_finished, finished, luxury | Finishing level |
| `per_page` | integer | ❌ No | 15 | 1-100 | Results per page |
| `page` | integer | ❌ No | 1 | 1-unlimited | Page number |

---

## Detailed Parameter Descriptions

### `q` (Required)
**Search query term**

- **Type:** String
- **Min length:** 2 characters
- **Max length:** 100 characters
- **Case sensitivity:** Case-insensitive
- **Empty value handling:** Returns 422 validation error
- **Examples:**
  - `q=villa` → Searches "Villa" in all fields
  - `q=3+bedroom` → Multi-word search
  - `q=penthouse+dubai` → Location + type

**What it searches:**
- Properties: `title`, `description`
- Compounds: `title`, `description`
- Areas: `name`
- Developers: `name`, `description`

---

### `entity_type` (Optional)
**Limit results to specific entity type(s)**

- **Type:** String (single value only)
- **Valid values:**
  - `all` (default) - All entity types
  - `properties` - Only properties/units
  - `compounds` - Only compounds/projects
  - `areas` - Only areas/neighborhoods
  - `developers` - Only developers/builders
- **Effect on counts:**
  - Only returns counts for requested type
  - Unused types show count: 0
- **Examples:**
  - `entity_type=properties` → Only property results
  - `entity_type=compounds` → Only compound results
  - No param → All types (default)

---

### `purpose` (Optional, Properties Only)
**Filter by listing purpose**

- **Type:** String (single value)
- **Valid values:** `sale`, `rent`
- **Applies to:** Properties only
- **Ignored for:** Compounds, Areas, Developers
- **Logic:** OR logic (sale OR rent if not specified)
- **Examples:**
  - `purpose=sale` → Buy properties only
  - `purpose=rent` → Rent properties only
  - No param → Both sale and rent properties

---

### `property_type_id` (Optional, Properties Only)
**Filter by property type**

- **Type:** Integer (single value)
- **Valid values:** Any existing property type ID in `re_property_types` table
- **Applies to:** Properties only
- **Common IDs:**
  - `1` - Apartment
  - `2` - Villa
  - `3` - Townhouse
  - `4` - Penthouse
  - `5` - Studio
  - (Check your `re_property_types` table for complete list)
- **Validation:** Must exist in database (422 if not)
- **Examples:**
  - `property_type_id=2` → Villas only
  - `property_type_id=4` → Penthouses only

---

### `min_price` & `max_price` (Optional)
**Filter by price range**

- **Type:** Decimal/Float
- **Valid values:** Numeric, 0 or positive
- **Validation rules:**
  - `max_price >= min_price` (if both provided)
  - Must be numeric (422 if not)
- **Applies to:**
  - **Properties:** Direct property price
  - **Compounds:** Compound's `max_price >= min_price` (price range)
  - **Areas:** Not applied
  - **Developers:** Not applied
- **Examples:**
  - `min_price=100000&max_price=500000` → Price range
  - `min_price=1000000` → Minimum price only
  - `max_price=5000000` → Maximum price only

**Price Range Logic:**
- For properties: `property.price >= min_price AND property.price <= max_price`
- For compounds: `compound.max_price >= min_price AND compound.min_price <= max_price`
  - This allows results where price ranges overlap

---

### `bedrooms` & `bathrooms` (Optional, Properties Only)
**Filter by number of rooms**

- **Type:** Integer
- **Valid range:** 0-10
- **Behavior:** Returns properties with **>= specified count**
- **Validation:** Must be 0-10 (422 if not)
- **Applies to:** Properties only
- **Examples:**
  - `bedrooms=3` → 3+ bedroom properties
  - `bathrooms=2` → 2+ bathroom properties
  - `bedrooms=4&bathrooms=3` → 4+ BD and 3+ BA

**Interpretation:**
- `bedrooms=0` → Studio or unfurnished properties
- `bedrooms=4` → 4, 5, 6+ bedroom properties are included

---

### `area_id` (Optional)
**Filter by geographic area**

- **Type:** Integer
- **Valid values:** Any existing area ID in `re_areas` table
- **Validation:** Must exist in database (422 if not)
- **Area hierarchy:** Includes area **and all child areas**
- **Applies to:**
  - **Properties:** Through compound's area
  - **Compounds:** Direct area filter
  - **Areas:** Specified area + children
  - **Developers:** Not applied
- **Examples:**
  - `area_id=5` → Area 5 and all its sub-areas
  - `area_id=12` → Area 12 and children

**How hierarchy works:**
- If Area 5 (Dubai) has children: 5.1 (New Dubai), 5.2 (Downtown)
- `area_id=5` returns results in Dubai, New Dubai, AND Downtown

---

### `developer_id` (Optional)
**Filter by developer**

- **Type:** Integer
- **Valid values:** Any existing developer ID in `re_developers` table
- **Validation:** Must exist in database (422 if not)
- **Applies to:**
  - **Properties:** Through compound's developer
  - **Compounds:** Direct developer filter
  - **Areas:** Not applied
  - **Developers:** Direct filter (returns specified developer only)
- **Examples:**
  - `developer_id=3` → Properties/compounds from developer 3
  - `developer_id=7` → Developer 7 and their properties

---

### `amenities` (Optional)
**Filter by available amenities**

- **Type:** String (comma-separated) OR Array (JSON)
- **Format options:**
  - String: `"1,2,3"`
  - Array: `[1, 2, 3]`
- **Valid values:** Any existing amenity ID in `re_amenities` table
- **Logic:** Properties/compounds having **ALL** specified amenities
- **Applies to:**
  - **Properties:** Checked via `property_amenities` pivot
  - **Compounds:** Checked via `compound_amenities` pivot
  - **Areas:** Not applied
  - **Developers:** Not applied
- **Common amenities:**
  - `1` - Swimming Pool
  - `2` - Gym/Fitness
  - `3` - Security
  - `4` - Parking
  - `5` - Garden
  - (Check `re_amenities` table for full list)
- **Examples:**
  - `amenities=1,3,4` → Pool AND Security AND Parking
  - `amenities=2` → Gym only

**Important:** AND logic means property must have ALL amenities

---

### `finishing` (Optional, Properties Only)
**Filter by finishing level**

- **Type:** String (single value)
- **Valid values:**
  - `unfinished` - Raw/skeleton
  - `semi_finished` - Partial finishing
  - `finished` - Complete finishing
  - `luxury` - Premium luxury finishing
- **Applies to:** Properties only
- **Ignored for:** Compounds, Areas, Developers
- **Example:**
  - `finishing=luxury` → Luxury finished properties only
  - `finishing=finished` → Completed properties

---

### `per_page` (Optional)
**Results per page**

- **Type:** Integer
- **Valid range:** 1-100
- **Default:** 15
- **Effect:** Controls pagination slice size
- **Examples:**
  - `per_page=20` → 20 results per page
  - `per_page=100` → Maximum 100 per page
  - No param → 15 results per page

---

### `page` (Optional)
**Page number**

- **Type:** Integer
- **Valid range:** >= 1
- **Default:** 1
- **Calculation:**
  - Offset = (page - 1) * per_page
  - Results = merged_results[offset : offset + per_page]
- **Examples:**
  - `page=1` → First page
  - `page=2` → Second page
  - `page=5&per_page=20` → Results 81-100

**Pagination metadata returned:**
- `current_page` - Your requested page
- `per_page` - Your requested per_page value
- `last_page` - Maximum page number available
- `total` - Total results across all pages
- `has_next` - (Derived: current_page < last_page)

---

## Response Format

### Success Response (HTTP 200)

```json
{
  "data": [
    {
      "type": "property|compound|area|developer",
      "id": 123,
      "title": "Display name/title",
      "url": "/path/to/entity",
      "image": "https://cdn.example.com/image.jpg",
      "relevance_score": 95,
      "data": {
        // Entity-specific fields (see below)
      }
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 15,
    "current_page": 1,
    "last_page": 10,
    "counts_by_type": {
      "properties": 50,
      "compounds": 30,
      "areas": 15,
      "developers": 5
    }
  },
  "filters_applied": {
    "q": "villa",
    "purpose": "sale",
    "min_price": 100000,
    "max_price": 500000,
    // ... other applied filters
  }
}
```

### Entity-Specific Response Fields

#### Property Object
```json
{
  "type": "property",
  "id": 123,
  "title": "Luxury Penthouse",
  "url": "/properties/luxury-penthouse-123",
  "image": "https://...",
  "relevance_score": 95,
  "data": {
    "price": 1500000,
    "purpose": "sale",
    "bedrooms": 4,
    "bathrooms": 3,
    "area": "Downtown Dubai",
    "location": {
      "latitude": 25.2048,
      "longitude": 55.2708
    }
  }
}
```

#### Compound Object
```json
{
  "type": "compound",
  "id": 45,
  "title": "Emirates Hills",
  "url": "/compounds/emirates-hills-45",
  "image": "https://...",
  "relevance_score": 85,
  "data": {
    "developer": "Emaar Properties",
    "min_price": 500000,
    "max_price": 3000000,
    "properties_count": 245,
    "location": "Downtown Dubai"
  }
}
```

#### Area Object
```json
{
  "type": "area",
  "id": 12,
  "title": "Downtown Dubai",
  "url": "/areas/downtown-dubai-12",
  "image": "https://...",
  "relevance_score": 75,
  "data": {
    "type": "district",
    "properties_count": 1250,
    "compounds_count": 45
  }
}
```

#### Developer Object
```json
{
  "type": "developer",
  "id": 8,
  "title": "Emaar Properties",
  "url": "/developers/emaar-properties-8",
  "image": "https://...",
  "relevance_score": 70,
  "data": {
    "compounds_count": 15,
    "properties_count": 580,
    "website": "https://www.emaar.com"
  }
}
```

---

### Validation Error Response (HTTP 422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "q": [
      "The q field is required.",
      "The q field must be at least 2 characters."
    ],
    "min_price": [
      "The min price field must be numeric."
    ],
    "bedrooms": [
      "The bedrooms field must be a number between 0 and 10."
    ],
    "area_id": [
      "The selected area id is invalid."
    ]
  }
}
```

---

## Relevance Scoring Details

### Score Calculation Steps

**1. Primary Field Matching (title/name)**
- **Exact equality:** +100 points
  - Database: `LOWER(field) = LOWER(search_term)`
- **Starts with:** +80 points
  - Database: `LOWER(field) LIKE LOWER(concat(search_term, '%'))`
- **Contains:** +60 points
  - Database: `LOWER(field) LIKE concat('%', LOWER(search_term), '%')`

**2. Secondary Field Matching (description)**
- **Contains search term:** +40 points
  - Checks: `LOWER(description) LIKE concat('%', LOWER(search_term), '%')`

**3. Bonuses Applied**
- **Featured/promoted:** +15 points
  - Only if `is_featured = true`
- **Recently created:** +10 points
  - If created within last 30 days
- **Has media:** +5 points
  - Properties/Compounds: Has `primaryImage`
  - Developers: Has `logo`

### Score Range
- **Minimum:** 5 points (contains match + recent + has image)
- **Typical:** 60-100 points
- **Maximum:** 155 points (exact exact match + all bonuses)

### Sorting
Results sorted by:
1. **Primary:** `relevance_score DESC`
2. **Secondary:** `created_at DESC` (when scores tie)

---

## Caching

### Cache Configuration
- **Supported Drivers:** Redis, Memcached, File, Database, Array
- **TTL:** 300 seconds (5 minutes)
- **Tags (Redis/Memcached only):** `['global_search', 'search_results']`
- **Key format:** `global_search:{MD5_HASH}`

### Driver Behavior

#### Redis / Memcached (Recommended)
- ✅ Uses cache tagging for instant invalidation
- ✅ Can flush all search results instantly with tags
- ✅ Better performance with large result sets
- ✅ Shared cache across multiple servers

#### File / Database Cache
- ✅ Basic caching without tags (still works!)
- ⚠️ Cannot invalidate by tags (auto-expires after 5 min TTL)
- ⚠️ Manual full cache flush needed for instant updates: `Cache::flush()`
- ℹ️ Suitable for single-server deployments

### Cache Key Generation
```php
// Pseudocode
$params_sorted = sort($params);
$key = 'global_search:' . md5(json_encode($params_sorted));
```

### Cache Invalidation
```php
// Method available:
$searchService->invalidateGlobalSearchCache();
// Flushes all results with tags: ['global_search', 'search_results']

// Automated when:
// - Property created/updated/deleted
// - Compound created/updated/deleted
// - Area created/updated/deleted
// - Developer created/updated/deleted
```

### Performance Impact
- **Cache hit:** ~10-50ms response time
- **Cache miss:** ~200-500ms (depends on dataset)
- **Cache ratio:** Typically 70-80% hit rate for common queries

---

## Special Behaviors

### Area Hierarchy Processing
When `area_id` is specified:
1. Fetch area with given ID
2. Get all child areas recursively
3. Search properties/compounds in parent + all children
4. Ancestors are NOT included, only descendants

### Price Range Validation
- `max_price` must be >= `min_price` (if both provided)
- Decimal values allowed (e.g., `99999.99`)
- Currency agnostic (API doesn't care about currency)

### Multiple Entity Type Search (Default)
When searching all entity types:
1. Properties limited to 50 results
2. Compounds limited to 50 results
3. Areas limited to 20 results
4. Developers limited to 20 results
5. All merged and sorted by relevance
6. Pagination applied to merged results

### Entity Type Filtering
When `entity_type=properties`:
1. ONLY properties database queried
2. Results limited to 50
3. Paginated normally
4. Other entity type counts show as 0

---

## Error Codes & Messages

| HTTP Code | Condition | Message |
|-----------|-----------|---------|
| 200 | Success | Results returned |
| 401 | Unauthorized | No/invalid token |
| 403 | Forbidden | Not tenant user |
| 422 | Validation error | See errors object |
| 500 | Server error | Contact support |

### Validation Error Examples

**Missing 'q' parameter:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "q": ["The q field is required."]
  }
}
```

**'q' too short:**
```json
{
  "errors": {
    "q": ["The q field must be at least 2 characters."]
  }
}
```

**Invalid entity_type:**
```json
{
  "errors": {
    "entity_type": ["The entity type field must be one of: all, properties, compounds, areas, developers."]
  }
}
```

**Non-existent area_id:**
```json
{
  "errors": {
    "area_id": ["The selected area id is invalid."]
  }
}
```

**Invalid price format:**
```json
{
  "errors": {
    "min_price": ["The min price field must be numeric."]
  }
}
```

---

## Rate Limiting

Applied: Standard Laravel rate limiting (no special limits for search)

---

## Authentication

**Required:** Tenant user authentication
- **Guard:** `sanctum_tenant_user`
- **Token:** Bearer token from login endpoint
- **Header:** `Authorization: Bearer {TOKEN}`

---

## Database Indexes

Queries optimized with indexes:
- Full-text: `re_properties` (title, description)
- Full-text: `re_compounds` (title, description)
- Regular: `re_areas` (name)
- Regular: `re_developers` (name)

Query execution time typically < 500ms on fresh (non-cached) queries.

