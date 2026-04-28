# 🚀 Global Search Usage Guide

## Table of Contents
1. [Quick Start](#quick-start)
2. [Frontend Integration Examples](#frontend-integration-examples)
3. [Common Search Patterns](#common-search-patterns)
4. [API Client Examples](#api-client-examples)
5. [UI Component Guidelines](#ui-component-guidelines)
6. [Performance Tips](#performance-tips)
7. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Minimum Required Request
```
GET /api/v1/tenant/my-tenant/realestate/search?q=villa
```

**Response includes:**
- Results from all 4 entity types (Properties, Compounds, Areas, Developers)
- Sorted by relevance score
- Pagination metadata
- Applied filters

---

## Frontend Integration Examples

### JavaScript/Fetch

#### Basic Search
```javascript
// Simple search with default parameters
async function searchRealEstate(query) {
  const response = await fetch(
    `/api/v1/tenant/my-tenant/realestate/search?q=${encodeURIComponent(query)}`,
    {
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  if (response.ok) {
    return data.data; // Array of results
  } else {
    throw new Error(data.message);
  }
}

// Usage
searchRealEstate('penthouse').then(results => {
  console.log(`Found ${results.length} results`);
  results.forEach(result => {
    console.log(`[${result.type}] ${result.title} (score: ${result.relevance_score})`);
  });
});
```

#### Advanced Search with Filters
```javascript
async function advancedSearch(params) {
  // Build query string from parameters
  const queryParams = new URLSearchParams({
    q: params.query,
    entity_type: params.entityType || 'all',
    purpose: params.purpose,
    min_price: params.minPrice,
    max_price: params.maxPrice,
    bedrooms: params.bedrooms,
    bathrooms: params.bathrooms,
    area_id: params.areaId,
    developer_id: params.developerId,
    amenities: Array.isArray(params.amenities) 
      ? params.amenities.join(',') 
      : params.amenities,
    finishing: params.finishing,
    per_page: params.perPage || 15,
    page: params.page || 1
  });

  // Remove undefined parameters
  for (const [key, value] of queryParams.entries()) {
    if (value === 'undefined' || value === '' || value === null) {
      queryParams.delete(key);
    }
  }

  const response = await fetch(
    `/api/v1/tenant/my-tenant/realestate/search?${queryParams}`,
    {
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      }
    }
  );

  return response.json();
}

// Usage
advancedSearch({
  query: 'luxury apartment',
  purpose: 'sale',
  minPrice: 500000,
  maxPrice: 2000000,
  bedrooms: 3,
  areaId: 5,
  amenities: [1, 2, 3] // Pool, Gym, Security
}).then(response => {
  console.log('Results:', response.data);
  console.log('Pagination:', response.meta);
  console.log('Filters Applied:', response.filters_applied);
});
```

#### Pagination Helper
```javascript
async function getSearchResults(query, page = 1, perPage = 15) {
  const response = await fetch(
    `/api/v1/tenant/my-tenant/realestate/search?q=${encodeURIComponent(query)}&page=${page}&per_page=${perPage}`,
    {
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    }
  );

  const data = await response.json();
  
  return {
    results: data.data,
    pagination: data.meta,
    hasMore: data.meta.current_page < data.meta.last_page,
    total: data.meta.total
  };
}

// Usage with pagination
const results1 = await getSearchResults('villa', 1, 20);
console.log(`Page 1: ${results1.results.length} results (${results1.total} total)`);

if (results1.hasMore) {
  const results2 = await getSearchResults('villa', 2, 20);
  console.log(`Page 2: ${results2.results.length} results`);
}
```

### React Example

#### Search Hook
```jsx
// Custom React Hook
import { useState, useCallback } from 'react';

export function useGlobalSearch(tenantId) {
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState(null);

  const search = useCallback(async (query, filters = {}) => {
    if (!query || query.length < 2) {
      setResults([]);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams({
        q: query,
        ...filters,
        amenities: Array.isArray(filters.amenities) 
          ? filters.amenities.join(',') 
          : filters.amenities
      });

      // Remove undefined values
      params.forEach((value, key) => {
        if (!value) params.delete(key);
      });

      const response = await fetch(
        `/api/v1/tenant/${tenantId}/realestate/search?${params}`,
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      if (!response.ok) throw new Error('Search failed');

      const data = await response.json();
      setResults(data.data);
      setPagination(data.meta);
    } catch (err) {
      setError(err.message);
      setResults([]);
    } finally {
      setLoading(false);
    }
  }, [tenantId]);

  return { results, loading, error, pagination, search };
}

// Component using the hook
function SearchComponent() {
  const { results, loading, error, search } = useGlobalSearch('my-tenant');
  const [query, setQuery] = useState('');

  const handleSearch = (e) => {
    setQuery(e.target.value);
    search(e.target.value, {
      per_page: 20
    });
  };

  return (
    <div>
      <input 
        type="text"
        placeholder="Search properties, areas, developers..."
        value={query}
        onChange={handleSearch}
      />
      
      {loading && <p>Searching...</p>}
      {error && <p className="error">{error}</p>}
      
      <div className="results">
        {results.map(result => (
          <SearchResultCard key={`${result.type}-${result.id}`} result={result} />
        ))}
      </div>
    </div>
  );
}

// Result Card Component
function SearchResultCard({ result }) {
  const getIcon = (type) => {
    const icons = {
      property: '🏠',
      compound: '🏢',
      area: '🗺️',
      developer: '👷'
    };
    return icons[type] || '📍';
  };

  return (
    <a href={result.url} className="search-result">
      {result.image && <img src={result.image} alt={result.title} />}
      <div>
        <span className="icon">{getIcon(result.type)}</span>
        <h3>{result.title}</h3>
        <p className="type">{result.type}</p>
        <p className="score">Relevance: {result.relevance_score}</p>
      </div>
    </a>
  );
}
```

#### Advanced React Component
```jsx
import React, { useState, useEffect } from 'react';

function AdvancedPropertySearch() {
  const [filters, setFilters] = useState({
    query: '',
    entityType: 'all',
    purpose: '',
    minPrice: '',
    maxPrice: '',
    bedrooms: '',
    bathrooms: '',
    areaId: '',
    amenities: [],
    page: 1,
    perPage: 15
  });

  const [results, setResults] = useState({
    data: [],
    meta: null,
    filters_applied: {}
  });

  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (filters.query.length >= 2) {
      performSearch();
    }
  }, [filters]);

  async function performSearch() {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== 0 && (!Array.isArray(value) || value.length > 0)) {
          if (Array.isArray(value)) {
            params.set(key, value.join(','));
          } else {
            params.set(key, value);
          }
        }
      });

      const response = await fetch(
        `/api/v1/tenant/my-tenant/realestate/search?${params}`,
        {
          headers: { 'Authorization': `Bearer ${token}` }
        }
      );

      const data = await response.json();
      setResults(data);
    } finally {
      setLoading(false);
    }
  }

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value,
      page: 1 // Reset to first page on filter change
    }));
  };

  const handleAmenityToggle = (amenityId) => {
    setFilters(prev => ({
      ...prev,
      amenities: prev.amenities.includes(amenityId)
        ? prev.amenities.filter(a => a !== amenityId)
        : [...prev.amenities, amenityId],
      page: 1
    }));
  };

  return (
    <div className="search-container">
      {/* Search Bar */}
      <div className="search-bar">
        <input
          type="text"
          placeholder="Search for properties, compounds, areas..."
          value={filters.query}
          onChange={(e) => handleFilterChange('query', e.target.value)}
        />
      </div>

      {/* Filters */}
      <div className="filters">
        <select 
          value={filters.entityType}
          onChange={(e) => handleFilterChange('entityType', e.target.value)}
        >
          <option value="all">All Entity Types</option>
          <option value="properties">Properties Only</option>
          <option value="compounds">Compounds Only</option>
          <option value="areas">Areas Only</option>
          <option value="developers">Developers Only</option>
        </select>

        <select 
          value={filters.purpose}
          onChange={(e) => handleFilterChange('purpose', e.target.value)}
        >
          <option value="">Any Purpose</option>
          <option value="sale">Sale</option>
          <option value="rent">Rent</option>
        </select>

        <input
          type="number"
          placeholder="Min Price"
          value={filters.minPrice}
          onChange={(e) => handleFilterChange('minPrice', e.target.value)}
        />

        <input
          type="number"
          placeholder="Max Price"
          value={filters.maxPrice}
          onChange={(e) => handleFilterChange('maxPrice', e.target.value)}
        />

        <input
          type="number"
          placeholder="Bedrooms (min)"
          value={filters.bedrooms}
          onChange={(e) => handleFilterChange('bedrooms', e.target.value)}
        />

        <input
          type="number"
          placeholder="Bathrooms (min)"
          value={filters.bathrooms}
          onChange={(e) => handleFilterChange('bathrooms', e.target.value)}
        />
      </div>

      {/* Results */}
      <div className="results">
        <p className="result-count">
          Found {results.meta?.total || 0} results
          {results.meta?.counts_by_type && (
            <span className="breakdown">
              ({results.meta.counts_by_type.properties} properties, 
               {results.meta.counts_by_type.compounds} compounds,
               {results.meta.counts_by_type.areas} areas,
               {results.meta.counts_by_type.developers} developers)
            </span>
          )}
        </p>

        {loading && <div className="loading">Searching...</div>}

        {!loading && results.data.length > 0 ? (
          <>
            <div className="result-list">
              {results.data.map(result => (
                <ResultCard key={`${result.type}-${result.id}`} result={result} />
              ))}
            </div>

            {/* Pagination */}
            {results.meta && results.meta.last_page > 1 && (
              <div className="pagination">
                <button
                  disabled={results.meta.current_page === 1}
                  onClick={() => handleFilterChange('page', results.meta.current_page - 1)}
                >
                  Previous
                </button>
                <span>Page {results.meta.current_page} of {results.meta.last_page}</span>
                <button
                  disabled={results.meta.current_page === results.meta.last_page}
                  onClick={() => handleFilterChange('page', results.meta.current_page + 1)}
                >
                  Next
                </button>
              </div>
            )}
          </>
        ) : (
          !loading && <p className="no-results">No results found</p>
        )}
      </div>
    </div>
  );
}

function ResultCard({ result }) {
  const typeColors = {
    property: '#3498db',
    compound: '#e74c3c',
    area: '#f39c12',
    developer: '#9b59b6'
  };

  return (
    <a href={result.url} className="result-card" style={{ borderLeftColor: typeColors[result.type] }}>
      {result.image && <img src={result.image} alt={result.title} />}
      <div className="info">
        <h3>{result.title}</h3>
        <p className="meta">
          <span className="type">{result.type}</span>
          <span className="score">Relevance: {result.relevance_score}</span>
        </p>
        {result.data && (
          <p className="details">
            {result.type === 'property' && `${result.data.bedrooms}BD | ${result.data.bathrooms}BA | ${result.data.purpose}`}
            {result.type === 'compound' && `${result.data.properties_count} properties | ${result.data.developer}`}
            {result.type === 'area' && `${result.data.properties_count} properties in area`}
            {result.type === 'developer' && `${result.data.compounds_count} compounds | ${result.data.properties_count} properties`}
          </p>
        )}
      </div>
    </a>
  );
}

export default AdvancedPropertySearch;
```

### Vue 3 Example

```vue
<template>
  <div class="global-search">
    <!-- Search Input -->
    <div class="search-box">
      <input 
        v-model="query"
        type="text"
        placeholder="Search properties, compounds, areas..."
        @input="handleSearch"
      />
    </div>

    <!-- Entity Type Filter -->
    <select v-model="entityType" @change="search">
      <option value="all">All Types</option>
      <option value="properties">Properties</option>
      <option value="compounds">Compounds</option>
      <option value="areas">Areas</option>
      <option value="developers">Developers</option>
    </select>

    <!-- Results -->
    <div v-if="loading" class="loading">Searching...</div>
    <div v-else-if="results.length > 0" class="results">
      <div 
        v-for="result in results" 
        :key="`${result.type}-${result.id}`"
        class="result-item"
      >
        <img :src="result.image" :alt="result.title" />
        <div>
          <h3>{{ result.title }}</h3>
          <p class="type">{{ result.type }} | Score: {{ result.relevance_score }}</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination" class="pagination">
        <button :disabled="pagination.current_page === 1" @click="previousPage">
          Previous
        </button>
        <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button :disabled="pagination.current_page === pagination.last_page" @click="nextPage">
          Next
        </button>
      </div>
    </div>
    <div v-else class="no-results">No results found</div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const query = ref('');
const entityType = ref('all');
const results = ref([]);
const pagination = ref(null);
const loading = ref(false);
const currentPage = ref(1);

// Debounce search
let searchTimeout;
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    search();
  }, 300);
};

async function search() {
  if (query.value.length < 2) {
    results.value = [];
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({
      q: query.value,
      entity_type: entityType.value,
      page: currentPage.value,
      per_page: 20
    });

    const response = await fetch(
      `/api/v1/tenant/my-tenant/realestate/search?${params}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    const data = await response.json();
    results.value = data.data;
    pagination.value = data.meta;
  } finally {
    loading.value = false;
  }
}

function previousPage() {
  if (pagination.value.current_page > 1) {
    currentPage.value--;
    search();
  }
}

function nextPage() {
  if (pagination.value.current_page < pagination.value.last_page) {
    currentPage.value++;
    search();
  }
}
</script>

<style scoped>
.global-search {
  max-width: 600px;
  margin: 0 auto;
}

.search-box input {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.results {
  margin-top: 20px;
}

.result-item {
  display: flex;
  padding: 12px;
  border: 1px solid #eee;
  border-radius: 4px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: background 0.2s;
}

.result-item:hover {
  background: #f9f9f9;
}

.result-item img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 12px;
}

.result-item h3 {
  margin: 0;
  font-size: 16px;
}

.type {
  color: #666;
  font-size: 12px;
}
</style>
```

---

## Common Search Patterns

### 1. Real Estate Buyer Search
```javascript
// User looking to buy a villa
search({
  query: 'villa',
  purpose: 'sale',
  minPrice: 500000,
  maxPrice: 2000000,
  bedrooms: 3,
  bathrooms: 2,
  areaId: 5
});
```

### 2. Apartment for Rent
```javascript
// User looking for rental apartment
search({
  query: 'apartment',
  purpose: 'rent',
  minPrice: 5000,
  maxPrice: 15000,
  bedrooms: 2,
  area_id: 8
});
```

### 3. Luxury Property Search
```javascript
// Premium buyer looking for luxury properties
search({
  query: 'luxury',
  purpose: 'sale',
  minPrice: 1000000,
  finishing: 'luxury',
  amenities: [1, 2, 3, 4], // Pool, Gym, Security, Parking
  bedrooms: 4
});
```

### 4. Family House Search
```javascript
// Family looking for spacious house
search({
  query: 'villa',
  bedrooms: 4,
  bathrooms: 3,
  amenities: [4, 5] // Parking, Garden
});
```

### 5. Compound/Community Search
```javascript
// Investor looking for compounds
search({
  query: 'compound',
  entity_type: 'compounds',
  developerId: 3,
  minPrice: 500000,
  page: 1,
  perPage: 10
});
```

### 6. Area Exploration
```javascript
// User exploring a specific area
search({
  query: 'new cairo',
  entity_type: 'areas'
});
```

### 7. Developer Properties
```javascript
// Investor looking for specific developer's properties
search({
  query: 'emaar',
  developerId: 2,
  purpose: 'sale'
});
```

---

## Performance Tips

### 1. Debounce Search Input
```javascript
function debounceSearch(fn, delay = 300) {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

const debouncedSearch = debounceSearch(performSearch);
```

### 2. Limit Queries in Flight
```javascript
let isSearching = false;

async function performSearch(query) {
  if (isSearching) return;
  
  isSearching = true;
  try {
    const results = await search(query);
    renderResults(results);
  } finally {
    isSearching = false;
  }
}
```

### 3. Cache Recent Searches
```javascript
const searchCache = new Map();

async function cachedSearch(query) {
  if (searchCache.has(query)) {
    return searchCache.get(query);
  }
  
  const results = await search(query);
  searchCache.set(query, results);
  
  // Keep cache size limited
  if (searchCache.size > 50) {
    const firstKey = searchCache.keys().next().value;
    searchCache.delete(firstKey);
  }
  
  return results;
}
```

### 4. Batch Filter Updates
```javascript
// Bad: Each filter change triggers search
onFilterChange = (filter) => {
  search(); // Called multiple times
};

// Good: Batch updates
const pendingFilters = {};

onFilterChange = (filter, value) => {
  pendingFilters[filter] = value;
  // Debounce the actual search
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    search(pendingFilters);
  }, 300);
};
```

### 5. Efficient Pagination
```javascript
// Load next page data while displaying current page
async function prefetchNextPage(currentPage) {
  const nextPageData = await fetch(
    `/api/v1/tenant/my-tenant/realestate/search?q=${query}&page=${currentPage + 1}`,
    { headers: { 'Authorization': `Bearer ${token}` } }
  ).then(r => r.json());
  
  // Cache it for when user clicks next
  return nextPageData;
}
```

---

## Troubleshooting

### "The q field is required"
```
❌ GET /api/v1/tenant/my-tenant/realestate/search

✅ GET /api/v1/tenant/my-tenant/realestate/search?q=villa
```
The `q` parameter is required. Provide at least 2 characters.

### "The q field must be at least 2 characters"
```
❌ ?q=a

✅ ?q=villa
```
Search term must be 2+ characters.

### No results despite existing data
**Check filter constraints:**
- Are you filtering by `purpose=sale` but property is for rent?
- Is `area_id` specified but property is in different area?
- Is `developer_id` filtering out your target?

**Solution:** Try search with fewer filters:
```javascript
// Start simple
search({ q: 'villa' });

// Then add filters one by one
search({ q: 'villa', purpose: 'sale' });
search({ q: 'villa', purpose: 'sale', minPrice: 100000 });
```

### Results are irrelevant
The global search scores results by relevance. Adjust your search term:
```
❌ q=3 (too generic)
✅ q=3 bedroom villa (more specific)

❌ q=property (too generic)
✅ q=penthouse downtown (specific enough)
```

### Performance is slow on first search
First searches hit the database. Subsequent same queries use cache (5 min TTL).

**Tips:**
- Use more specific search terms
- Add filters to narrow down results
- Increase results per cache hit by using smaller `per_page` first

### Getting 422 validation errors
Check error response:
```javascript
response.json().then(error => {
  console.log(error.errors); // Shows which fields failed validation
});
```

Common validation issues:
- `min_price`/`max_price` must be numeric
- `bedrooms`/`bathrooms` must be 0-10
- `purpose` must be `sale` or `rent`
- IDs must exist in database (`area_id`, `developer_id`, `property_type_id`)

---

## FAQ

**Q: Should I use Global Search or specialized endpoints?**
A: Use Global Search for general users, specialized endpoints for advanced filtering.

**Q: How long does search take?**
A: ~10-50ms (cached), ~200-500ms (fresh, depends on dataset).

**Q: Can I cache results?**
A: Yes! Results are automatically cached for 5 minutes by the API.

**Q: What's the maximum number of results?**
A: Limited by `per_page` (1-100, default 15) and pagination.

**Q: Does it support geospatial search?**
A: Use `/search/nearby` endpoint for GPS-based search.

**Q: Can I sort by price?**
A: Coming soon (Phase 2.5+). Currently sorted by relevance.

**Q: How does relevance scoring work?**
A: See [Global Search Documentation](./Search_Types.md) for detailed scoring algorithm.

