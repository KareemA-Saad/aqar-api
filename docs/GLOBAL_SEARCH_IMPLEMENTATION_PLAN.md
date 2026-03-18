# 🌐 Global Search Implementation Plan

## **Overview**
A unified search endpoint that searches across **ALL entities** in the RealEstate module (Properties, Compounds, Areas, Developers) and returns mixed results with proper filtering.

---

## **Endpoint Specification**

### **Route**
```
GET /api/v1/tenant/{tenant}/realestate/search
```

### **Purpose**
Universal search across all real estate entities in a single request.

---

## **Search Scope (What to Search)**

The global search will query across:

1. **Properties** 
   - Fields: `title`, `description`, `reference_number`
   
2. **Compounds** 
   - Fields: `name`, `description`
   
3. **Areas** 
   - Fields: `name`, `description`
   
4. **Developers** 
   - Fields: `name`, `description`

---

## **Available Filters**

### **Primary Filters**

| Filter | Type | Description | Values | Priority |
|--------|------|-------------|--------|----------|
| `q` | string | Search query (min 2 chars) | Any text | **REQUIRED** |
| `purpose` | enum | Listing type (Rent/Sale) | `sale`, `rent` | **HIGH** |
| `property_type_id` | integer | Property type filter | Property type IDs | HIGH |
| `min_price` | number | Minimum price | Any number | HIGH |
| `max_price` | number | Maximum price | Any number | HIGH |
| `bedrooms` | integer | Number of bedrooms | 1, 2, 3, 4, 5+ | HIGH |
| `bathrooms` | integer | Number of bathrooms | 1, 2, 3, 4+ | MEDIUM |
| `area_id` | integer | Filter by location | Area IDs | MEDIUM |
| `developer_id` | integer | Filter by developer | Developer IDs | MEDIUM |
| `amenities` | string/array | Amenity filters | Comma-separated IDs | MEDIUM |
| `finishing` | enum | Finishing level | `unfinished`, `semi_finished`, `fully_finished`, `furnished` | LOW |

### **Result Control**

| Filter | Type | Description | Default |
|--------|------|-------------|---------|
| `entity_type` | enum | Filter by entity type | `all`, `properties`, `compounds`, `areas`, `developers` | `all` |
| `per_page` | integer | Results per page | `15` |
| `page` | integer | Page number | `1` |
| `sort` | enum | Sort field | `relevance`, `created_at`, `price` | `relevance` |

---

## **Response Structure**

### **Unified Response Format**

```json
{
  "data": [
    {
      "type": "property",
      "id": 123,
      "title": "Luxury Villa in New Cairo",
      "description": "...",
      "price": 5000000,
      "purpose": "sale",
      "bedrooms": 4,
      "bathrooms": 3,
      "area": 350,
      "location": {
        "area": "New Cairo",
        "compound": "Palm Hills"
      },
      "image": "https://...",
      "url": "/properties/123-luxury-villa",
      "relevance_score": 95.5
    },
    {
      "type": "compound",
      "id": 45,
      "name": "Palm Hills October",
      "description": "...",
      "developer": "Palm Hills Developments",
      "min_price": 2000000,
      "properties_count": 150,
      "location": {
        "area": "6th of October"
      },
      "image": "https://...",
      "url": "/compounds/45-palm-hills-october",
      "relevance_score": 88.2
    },
    {
      "type": "area",
      "id": 12,
      "name": "New Cairo",
      "properties_count": 1250,
      "compounds_count": 45,
      "url": "/areas/12-new-cairo",
      "relevance_score": 75.0
    },
    {
      "type": "developer",
      "id": 8,
      "name": "Palm Hills Developments",
      "compounds_count": 12,
      "properties_count": 850,
      "url": "/developers/8-palm-hills",
      "relevance_score": 70.5
    }
  ],
  "meta": {
    "total": 245,
    "per_page": 15,
    "current_page": 1,
    "last_page": 17,
    "counts_by_type": {
      "properties": 150,
      "compounds": 45,
      "areas": 25,
      "developers": 25
    }
  },
  "filters_applied": {
    "q": "palm hills",
    "purpose": "sale",
    "min_price": 2000000,
    "entity_type": "all"
  }
}
```

---

## **Implementation Strategy**

### **Reuse Existing Code ✅**

1. **SearchService** - Already has:
   - `searchProperties()` - Reuse for property filtering
   - `searchCompounds()` - Reuse for compound filtering
   - Full-text search logic
   - Area hierarchy handling

2. **No Need to Modify:**
   - Existing search endpoints
   - Current transformers
   - Service methods

### **New Code Required 🆕**

1. **New Method in SearchService:**
   ```php
   public function globalSearch(array $params): array
   ```
   - Parallel queries to all entities
   - Merge results
   - Apply cross-entity filters
   - Score results by relevance

2. **New Controller Method:**
   ```php
   public function global(Request $request): JsonResponse
   ```

3. **New Resource Transformer:**
   ```php
   UnifiedSearchResultResource
   ```
   - Transforms different entity types to unified format
   - Adds type, relevance score, and URLs

---

## **Filter Application Logic**

### **How Filters Apply to Each Entity**

| Filter | Properties | Compounds | Areas | Developers |
|--------|-----------|-----------|-------|------------|
| `q` | ✅ Title, description | ✅ Name, description | ✅ Name | ✅ Name, description |
| `purpose` | ✅ listing_type | ❌ N/A | ❌ N/A | ❌ N/A |
| `property_type_id` | ✅ Direct | ❌ N/A | ❌ N/A | ❌ N/A |
| `min_price`/`max_price` | ✅ price | ✅ min_price/max_price | ❌ N/A | ❌ N/A |
| `bedrooms` | ✅ bedrooms | ❌ N/A | ❌ N/A | ❌ N/A |
| `bathrooms` | ✅ bathrooms | ❌ N/A | ❌ N/A | ❌ N/A |
| `area_id` | ✅ Via compound | ✅ area_id | ✅ Self/parent | ✅ Via compounds |
| `developer_id` | ✅ Via compound | ✅ developer_id | ❌ N/A | ✅ Self |
| `amenities` | ✅ Relation | ✅ Relation | ❌ N/A | ❌ N/A |
| `finishing` | ✅ finishing | ❌ N/A | ❌ N/A | ❌ N/A |

**Key Logic:**
- Filters that don't apply to an entity type will be ignored for that type
- Results are merged and sorted by relevance score
- Entity type can be filtered using `entity_type` parameter

---

## **Relevance Scoring**

### **Scoring Algorithm**

Base score calculation per entity:

1. **Exact Match** (100 points):
   - Query matches entity name/title exactly

2. **Starts With** (80 points):
   - Entity name/title starts with query

3. **Contains** (60 points):
   - Entity name/title contains query

4. **Description Match** (40 points):
   - Query found in description field

5. **Bonus Points:**
   - Featured entity: +15 points
   - Recently created (<30 days): +10 points
   - Has images: +5 points
   - High view count: +5 points

**Final Score:** Normalized to 0-100 range

---

## **Performance Considerations**

### **Optimization Strategies**

1. **Parallel Queries:**
   - Use Laravel's `parallel()` helper for concurrent DB queries
   - Query all entity types simultaneously

2. **Result Limits:**
   - Limit results per entity type (e.g., max 50 each)
   - Prevents overwhelming responses

3. **Caching:**
   - Cache popular search queries for 5 minutes
   - Cache key: `global_search:{tenant}:{query_hash}`

4. **Indexing:**
   - Ensure full-text indexes on:
     - `re_properties.title`, `re_properties.description`
     - `re_compounds.name`, `re_compounds.description`
     - `re_areas.name`
     - `re_developers.name`

5. **Pagination:**
   - Paginate merged results
   - Default: 15 items per page

---

## **Error Handling**

### **Validation Rules**

```php
[
    'q' => 'required|string|min:2|max:100',
    'purpose' => 'nullable|in:sale,rent',
    'property_type_id' => 'nullable|integer|exists:re_property_types,id',
    'min_price' => 'nullable|numeric|min:0',
    'max_price' => 'nullable|numeric|min:0|gte:min_price',
    'bedrooms' => 'nullable|integer|min:0|max:10',
    'bathrooms' => 'nullable|integer|min:0|max:10',
    'area_id' => 'nullable|integer|exists:re_areas,id',
    'developer_id' => 'nullable|integer|exists:re_developers,id',
    'amenities' => 'nullable|string',
    'finishing' => 'nullable|in:unfinished,semi_finished,fully_finished,furnished',
    'entity_type' => 'nullable|in:all,properties,compounds,areas,developers',
    'per_page' => 'nullable|integer|min:1|max:100',
    'page' => 'nullable|integer|min:1',
    'sort' => 'nullable|in:relevance,created_at,price',
]
```

### **Error Responses**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "q": ["The q field is required."],
    "max_price": ["The max price must be greater than or equal to min price."]
  }
}
```

---

## **OpenAPI Documentation**

### **Swagger Annotation Structure**

```php
#[OA\Get(
    path: '/api/v1/tenant/{tenant}/realestate/search',
    summary: 'Global search across all entities',
    description: 'Universal search endpoint that searches properties, compounds, areas, and developers in a single request with unified filtering',
    tags: ['Search'],
    parameters: [
        // All parameters with proper descriptions
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Mixed search results with relevance scoring'
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error'
        ),
    ]
)]
```

---

## **Testing Strategy**

### **Test Cases**

1. **Basic Search:**
   - Search with only `q` parameter
   - Verify all entity types returned

2. **Filtered Search:**
   - Search with `purpose=sale`
   - Verify only sale properties returned
   - Compounds and other entities still included

3. **Entity Type Filter:**
   - Search with `entity_type=properties`
   - Verify only properties returned

4. **Price Range:**
   - Search with price filters
   - Verify properties and compounds filtered correctly

5. **Empty Results:**
   - Search for non-existent term
   - Verify empty array returned

6. **Pagination:**
   - Search with large result set
   - Verify pagination works correctly

7. **Relevance Scoring:**
   - Verify exact matches score highest
   - Verify featured items get bonus points

---

## **Migration Path**

### **No Breaking Changes**

- Existing endpoints remain unchanged
- New endpoint is additive
- Frontend can migrate gradually:
  1. Use new global search for main search bar
  2. Keep specialized searches for advanced filtering
  3. Eventually consolidate if needed

---

## **Future Enhancements (Out of Scope)**

1. **Elasticsearch Integration:**
   - Advanced full-text search
   - Fuzzy matching
   - Typo tolerance

2. **Search Analytics:**
   - Track popular searches
   - User search behavior
   - Click-through rates

3. **AI-Powered Suggestions:**
   - Smart query interpretation
   - "Did you mean..." suggestions
   - Natural language processing

4. **Save Searches:**
   - Allow users to save search criteria
   - Email notifications for new matches

---

## **Summary**

### **What We're Building**

✅ Single endpoint for searching everything  
✅ Unified response format with type indicators  
✅ Smart filtering that applies contextually  
✅ Relevance-based scoring  
✅ Reuses existing search logic  
✅ No modifications to current endpoints  
✅ Fully documented in Swagger  

### **What We're NOT Building**

❌ Elasticsearch/advanced search engine  
❌ Modifications to existing endpoints  
❌ AI/ML features  
❌ Search analytics (yet)  

---

**Next Step:** See `GLOBAL_SEARCH_TODO.md` for implementation checklist.
