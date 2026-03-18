# Nawy.com Analysis

## Overview
Analysis of [Nawy.com](https://www.nawy.com/) to understand its data model, user experience, and URL structure for real estate applications.

## Core Entities & Hierarchy
The platform revolves around two primary entities with a strict One-to-Many relationship:

1.  **Areas (Locations)**
    *   **Description**: Geographical regions (e.g., New Cairo, North Coast) and specific districts (e.g., 6th Settlement).
    *   **Role**: Top-level container. Acts as a catalog for Compounds.
    *   **Hierarchy**: Super Area (e.g. New Cairo) -> Area (e.g. 6th Settlement).

2.  **Compounds (Projects)**
    *   **Description**: Large-scale developments or gated communities.
    *   **Role**: Acts as a parent container for properties.
    *   **Parent**: Belongs to an Area.
    *   **Key Attributes**: Developer, Location, Amenities, Master Plan.

3.  **Properties (Units)**
    *   **Description**: Individual sellable units (Apartments, Villas, Duplexes, etc.).
    *   **Role**: Child entity belonging to a specific Compound.
    *   **Key Attributes**: Price, Area, Bedroom/Bathroom count, Floor plan, Delivery Date.

## URL Structure (SEO Friendly)
Nawy uses a hierarchical URL structure that reinforces the relationship between Compounds and Properties:

*   **Area Profile**:
    `https://www.nawy.com/area/{slug}`
    *   *Example*: `/area/new-cairo`

*   **Compound Profile**:
    `https://www.nawy.com/compound/{id}-{slug}`
    *   *Example*: `/compound/1834-tierra`

*   **Property Detail**:
    `https://www.nawy.com/compound/{id}-{slug}/property/{propId}-{slug}`
    *   *Example*: `/compound/1834-tierra/property/107694-duplex-for-sale-in-tierra-compound`
    *   *Note*: The Property is nested *under* the Compound path.

## Search & Filtering Experience
*   **Global Search**:
    *   Supports searching by **Area**, **Compound**, or **Developer**.
    *   **Autocomplete**: Typing queries (e.g., "Tierra") instantly suggests matching Compounds with their location.
*   **Filters (Sidebar)**:
    *   **Areas**: (CheckBox list) e.g., New Cairo, 6th of October.
    *   **Developers**: (CheckBox list).
    *   **Bedrooms/Bathrooms**: Number selectors.
    *   **Property Types**: Apartment, Villa, etc.

## Recommendations for Tenant App
Based on this analysis, the Tenant application should support:

1.  **Area-Based Navigation**: Create landing pages for Areas (`/areas/[slug]`) to list Compounds within them.
2.  **Compound-First Navigation**: Allow users to browse by Compound, then drill down to Properties.
3.  **Nested Routing (Frontend)**: 
    *   `/areas/[slug]`
    *   `/compounds/[slug]` (or `/areas/[area_slug]/compounds/[slug]` if strict hierarchy is desired, but Nawy uses flat `/compound/` URL for shorter links).
    *   `/compounds/[slug]/properties/[property_slug]`.
4.  **Advanced Search API**:
    *   **Autocomplete Endpoint**: To support the predictive search dropdown.
    *   **Developer Filtering**: Add `developer_id` to property filters.
    *   **Compound Filtering**: (Already added) Crucial for the "Explore Properties in [Compound]" section.
