## Relations
@realestate/api_routes/real_estate_api_structure_md/real_estate_api_structure.md
@realestate/api_implementation/api_resources_and_transformers_md/real_estate_api_resources_and_transformers.md

## Raw Concept
**Task:**
Implement RealEstate property comparison (F1.4).

**Changes:**
- Added PropertyComparisonController with session-based logic.
- Implemented add, remove, and clear methods for comparison management.
- Added authenticated comparison routes in api.php.

**Files:**
- Modules/RealEstate/Http/Controllers/Frontend/PropertyComparisonController.php
- Modules/RealEstate/Routes/api.php

**Flow:**
User -> POST /comparison/{id} -> Controller -> Session storage -> GET /comparison -> Property::whereIn(ids) -> Resource Response

**Timestamp:** 2026-02-11

## Narrative
### Structure
- Controller: Modules/RealEstate/Http/Controllers/Frontend/PropertyComparisonController.php
- Routes: Modules/RealEstate/Routes/api.php (under 'comparison' prefix)
- Max Items: 4 (constant MAX_COMPARISON_ITEMS)

### Dependencies
- Modules/RealEstate/Services/PropertyService.php
- Modules/RealEstate/Entities/Property.php
- Modules/RealEstate/Transformers/PropertyResource.php
- Laravel Session (re_property_comparison key)
- auth:api_tenant_user middleware (TIER 2)

### Features
- Session-Based Comparison (F1.4): Allows users to compare up to 4 properties side-by-side.
- GET /comparison: Retrieves the full details of all properties in the current comparison session.
- POST /comparison/{property}: Adds a published property to the session (enforces max 4 limit).
- DELETE /comparison/{property}: Removes a specific property from the session.
- DELETE /comparison: Clears the entire comparison list.
- Security: Requires tenant user authentication (TIER 2).
- Validation: Ensures only published properties can be added.
