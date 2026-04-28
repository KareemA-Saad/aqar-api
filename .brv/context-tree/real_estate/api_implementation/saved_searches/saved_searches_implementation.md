## Relations
@real_estate/architecture/real_estate_module_architecture.md
@real_estate/api_routes/real_estate_api_structure_md/real_estate_api_structure.md

## Raw Concept
**Task:**
Implement Saved Searches with simplified alerts and Eloquent matching

**Changes:**
- Implemented SavedSearchService for CRUD and property matching
- Created SavedSearchController with 7 endpoints for frontend management
- Added SavedSearch entity with alert frequency and status scopes
- Enforced 10 searches per user limit and 4KB criteria JSON limit
- Implemented simplified alerts (daily/weekly) using Eloquent instead of Meilisearch

**Files:**
- Modules/RealEstate/Services/SavedSearchService.php
- Modules/RealEstate/Http/Controllers/Frontend/SavedSearchController.php
- Modules/RealEstate/Entities/SavedSearch.php

**Flow:**
User Request -> Controller -> SavedSearchService -> Eloquent Matching -> Property Results

**Timestamp:** 2026-02-15

## Narrative
### Structure
The implementation follows the module pattern with business logic encapsulated in SavedSearchService. Controllers handle request validation and response formatting following project standards.

### Dependencies
Relies on Laravel Eloquent for property matching, Laravel Log for comprehensive logging, and OpenApi/Attributes for documentation.

### Features
Max 10 searches per user, 4KB criteria limit, daily/weekly email alerts, and property match preview.

### Rules
Rule 1: Maximum 10 saved searches allowed per user.
Rule 2: Maximum criteria JSON size is 4096 bytes (4KB).
Rule 3: Alerts are simplified to daily or weekly frequencies only.
Rule 4: Property matching uses Eloquent queries against the database (no Meilisearch).
Rule 5: Duplicate search names (case-insensitive) are prohibited per user.

### Examples
Match Count Preview: GET /api/v1/tenant/{tenant}/realestate/saved-searches/{id}/matches?limit=10
