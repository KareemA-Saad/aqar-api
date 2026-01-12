## Relations
@tenancy/architecture.md

## Raw Concept
**Task:**
Implement tenant lifecycle management with dynamic migrations and token-based scoping.

**Changes:**
- Implemented dynamic module migration logic based on price plan features
- Added asynchronous database creation support via CreateTenantDatabase job
- Implemented Sanctum token generation with 'tenant:{id}' abilities for scoped access
- Added support for module-specific migrations during plan upgrades

**Files:**
- app/Services/TenantService.php
- app/Jobs/CreateTenantDatabase.php

**Flow:**
Create Tenant -> Dispatch Job (Optional) -> Create DB -> Run Base Migrations -> Run Module Migrations -> Generate Token

**Timestamp:** 2026-01-11

## Narrative
### Structure
Implemented in App\Services\TenantService. Handles the full lifecycle from tenant creation to token generation and context switching.

### Dependencies
Integrates with nwidart/laravel-modules for feature-based migrations. Uses Laravel Sanctum for token generation.

### Features
Tenant creation supports both synchronous and asynchronous (queued) database setup via the CreateTenantDatabase job. Migration logic is dynamic, running base migrations plus module-specific migrations (e.g., hotel-booking) determined by the tenant's subscription plan features. Supports plan upgrades by running migrations for newly enabled modules.
