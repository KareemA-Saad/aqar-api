## Relations
@real_estate/architecture/real_estate_module_architecture.md
@real_estate/foundation/foundation_overview.md
@tenancy/architecture/architecture_overview.md

## Raw Concept
**Task:**
RealEstate Module Upgrade Implementation Plan

**Changes:**
- Add saved searches and alert digests (daily/weekly)
- Add user-facing inquiry status tracking
- Add appointment booking system (request-confirm flow)
- Add property comparison API
- Add mortgage calculator and price insight indicator
- Add inquiry timeline using activitylog and internal notes
- Add SLA monitoring (computed) and agent reminders
- Implement lead scoring as model accessor
- Enforce tenant isolation and rate limiting on new endpoints
- Switch queue driver to database/redis for notifications

**Files:**
- Modules/RealEstate/Models/Property.php
- Modules/RealEstate/Models/PropertyInquiry.php
- Modules/RealEstate/Services/SearchService.php
- docs/REALESTATE_UPGRADE_VERDICT.md

**Flow:**
Upgrade focuses on engagement and agent CRM tools with significant simplification (Eloquent instead of Meilisearch, computed fields instead of stored). Implementation follows a 3-week sprint plan: Sprint 1 (Quick Wins), Sprint 2 (Engagement/Agent Foundation), Sprint 3 (Agent CRM).

**Timestamp:** 2026-02-10

## Narrative
### Structure
- Modules/RealEstate
- New Tables: `re_saved_searches`, `re_appointments`, `re_inquiry_notes`, `re_reminders`
- New Services: `SavedSearchService`, `AppointmentService`, `TimelineService`, `ReminderService`, `FinanceService`
- New Commands: `realestate:send-daily-alerts`, `realestate:process-reminders`, etc.

### Dependencies
- Spatie Activitylog (already installed)
- Spatie Translatable (already installed)
- Spatie Permission (already installed)
- Stancl Tenancy (already installed)
- Queue driver: switch from `sync` to `database` or `redis` (Mandatory)

### Features
- Saved Searches & Alerts (Daily/Weekly digests, no instant alerts, capped at 10)
- Inquiry Tracking (User-facing status tracking)
- Appointment Booking (Single slot request-confirm flow, merged with Agent Viewing Scheduler)
- Property Comparison (Stateless GET endpoint, max 4 properties)
- Mortgage Calculator (Stateless math endpoint)
- Price Insight (Simplified alternative to AI Valuation)
- Inquiry Timeline & Notes (Leveraging Spatie Activitylog)
- SLA & Reminders (Computed SLA, DB-based reminders)
- Lead Scoring (Computed accessor, not stored)
- Mandatory: Tenant isolation, rate limiting, FormRequest validation, Swagger docs
