## Relations
@realestate/api_routes/real_estate_api_structure_md/real_estate_api_structure.md
@realestate/architecture/property_service_implementation_md/property_service_implementation.md

## Raw Concept
**Task:**
Implement RealEstate mortgage calculator (F3.1) with property integration.

**Changes:**
- Added MortgageCalculatorService with financial formulas.
- Added MortgageCalculatorController with 5 stateless endpoints + 1 property-specific endpoint.
- Injected PropertyService into MortgageCalculatorController to support property-specific math.
- Implemented comparison logic for multiple loan terms (15, 20, 25, 30 years) in property-specific calc.
- Registered MortgageCalculatorService as singleton in RealEstateServiceProvider.
- Implemented comprehensive logging for all calculation requests.

**Files:**
- Modules/RealEstate/Services/MortgageCalculatorService.php
- Modules/RealEstate/Http/Controllers/Frontend/MortgageCalculatorController.php
- Modules/RealEstate/Providers/RealEstateServiceProvider.php

**Flow:**
User -> POST /mortgage/for-property/{id} -> Controller -> PropertyService (fetch price) -> MortgageService (calculate) -> JSON Response with options

**Timestamp:** 2026-02-11

## Narrative
### Structure
- Service: Modules/RealEstate/Services/MortgageCalculatorService.php
- Controller: Modules/RealEstate/Http/Controllers/Frontend/MortgageCalculatorController.php
- Routes: Modules/RealEstate/Routes/api.php (under 'mortgage' prefix)

### Dependencies
- Modules/RealEstate/Services/MortgageCalculatorService.php (Singleton)
- Modules/RealEstate/Services/PropertyService.php
- Modules/RealEstate/Http/Controllers/Frontend/MortgageCalculatorController.php
- Public routes (TIER 1)

### Features
- Mortgage Calculator (F3.1): Stateless financial calculation suite for real estate.
- Property-Specific Calculation: POST /mortgage/for-property/{property} retrieves actual price and returns comparative options (15/20/25/30 years).
- Monthly Payment: Calculates M = P [ r(1 + r)^n ] / [ (1 + r)^n - 1 ].
- Loan Amount: Reverse calculation from desired monthly payment.
- Amortization Schedule: Yearly/Monthly breakdown of principal and interest.
- Affordability: Estimates max loan based on income and DTI ratio (default 43%).
- Down Payment: Calculates amount and resulting loan from percentage.
- Precision: Uses native PHP math with 2-decimal precision.
- Security: Public access (TIER 1), no auth required.
