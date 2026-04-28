# RealEstate Module - User and Agent Capabilities

This document captures the current user and agent capabilities in the RealEstate module, along with gaps, near-future ideas, why they matter, and proposed endpoints.

All current endpoints reference the RealEstate module routes defined under:
- /api/v1/tenant/{tenant}/realestate
- /api/v1/tenant/{tenant}/agent/realestate

---

## User (Authenticated)

### All User Endpoints (Public + Authenticated)

Public (Tenant Context):
- GET /api/v1/tenant/{tenant}/realestate/properties
- GET /api/v1/tenant/{tenant}/realestate/properties/featured
- GET /api/v1/tenant/{tenant}/realestate/properties/{property}
- GET /api/v1/tenant/{tenant}/realestate/properties/{property}/similar
- GET /api/v1/tenant/{tenant}/realestate/compounds
- GET /api/v1/tenant/{tenant}/realestate/compounds/featured
- GET /api/v1/tenant/{tenant}/realestate/compounds/{compound}
- GET /api/v1/tenant/{tenant}/realestate/compounds/{compound}/properties
- GET /api/v1/tenant/{tenant}/realestate/areas/tree
- GET /api/v1/tenant/{tenant}/realestate/areas/cities
- GET /api/v1/tenant/{tenant}/realestate/areas/featured
- GET /api/v1/tenant/{tenant}/realestate/areas/{slug}
- GET /api/v1/tenant/{tenant}/realestate/areas/{area}/children
- GET /api/v1/tenant/{tenant}/realestate/areas/{area}/compounds
- GET /api/v1/tenant/{tenant}/realestate/areas/{area}/properties
- GET /api/v1/tenant/{tenant}/realestate/areas/{area}/breadcrumbs
- GET /api/v1/tenant/{tenant}/realestate/developers
- GET /api/v1/tenant/{tenant}/realestate/developers/featured
- GET /api/v1/tenant/{tenant}/realestate/developers/{developer}
- GET /api/v1/tenant/{tenant}/realestate/developers/{developer}/compounds
- GET /api/v1/tenant/{tenant}/realestate/property-types
- GET /api/v1/tenant/{tenant}/realestate/property-types/{slug}
- GET /api/v1/tenant/{tenant}/realestate/amenities
- GET /api/v1/tenant/{tenant}/realestate/search/properties
- GET /api/v1/tenant/{tenant}/realestate/search/compounds
- GET /api/v1/tenant/{tenant}/realestate/search/autocomplete
- GET /api/v1/tenant/{tenant}/realestate/search/facets
- GET /api/v1/tenant/{tenant}/realestate/search/popular
- GET /api/v1/tenant/{tenant}/realestate/search/nearby
- GET /api/v1/tenant/{tenant}/realestate/map/properties
- GET /api/v1/tenant/{tenant}/realestate/map/clusters
- GET /api/v1/tenant/{tenant}/realestate/gallery/properties/{property}
- GET /api/v1/tenant/{tenant}/realestate/gallery/compounds/{compound}
- POST /api/v1/tenant/{tenant}/realestate/inquiries/property/{property}
- POST /api/v1/tenant/{tenant}/realestate/inquiries/compound/{compound}
- POST /api/v1/tenant/{tenant}/realestate/inquiries/general

Authenticated User:
- GET /api/v1/tenant/{tenant}/realestate/saved-properties
- POST /api/v1/tenant/{tenant}/realestate/saved-properties/{property}
- DELETE /api/v1/tenant/{tenant}/realestate/saved-properties/{property}
- POST /api/v1/tenant/{tenant}/realestate/saved-properties/{property}/toggle
- GET /api/v1/tenant/{tenant}/realestate/saved-properties/{property}/check
- POST /api/v1/tenant/{tenant}/realestate/saved-properties/check-multiple

### Current Capabilities and Endpoints

Saved Properties (Favorites):
- List saved properties: GET /api/v1/tenant/{tenant}/realestate/saved-properties
- Save a property: POST /api/v1/tenant/{tenant}/realestate/saved-properties/{property}
- Remove a saved property: DELETE /api/v1/tenant/{tenant}/realestate/saved-properties/{property}
- Toggle saved status: POST /api/v1/tenant/{tenant}/realestate/saved-properties/{property}/toggle
- Check saved status: GET /api/v1/tenant/{tenant}/realestate/saved-properties/{property}/check
- Check multiple saved statuses: POST /api/v1/tenant/{tenant}/realestate/saved-properties/check-multiple

Browsing and Search (Public, but used by authenticated users too):
- Properties list: GET /api/v1/tenant/{tenant}/realestate/properties
- Property details: GET /api/v1/tenant/{tenant}/realestate/properties/{property}
- Featured properties: GET /api/v1/tenant/{tenant}/realestate/properties/featured
- Similar properties: GET /api/v1/tenant/{tenant}/realestate/properties/{property}/similar
- Compounds list: GET /api/v1/tenant/{tenant}/realestate/compounds
- Compound details: GET /api/v1/tenant/{tenant}/realestate/compounds/{compound}
- Compound properties: GET /api/v1/tenant/{tenant}/realestate/compounds/{compound}/properties
- Featured compounds: GET /api/v1/tenant/{tenant}/realestate/compounds/featured
- Areas tree: GET /api/v1/tenant/{tenant}/realestate/areas/tree
- Cities (root areas): GET /api/v1/tenant/{tenant}/realestate/areas/cities
- Area details: GET /api/v1/tenant/{tenant}/realestate/areas/{slug}
- Area children: GET /api/v1/tenant/{tenant}/realestate/areas/{id}/children
- Area compounds: GET /api/v1/tenant/{tenant}/realestate/areas/{id}/compounds
- Area properties: GET /api/v1/tenant/{tenant}/realestate/areas/{id}/properties
- Developers list: GET /api/v1/tenant/{tenant}/realestate/developers
- Developer details: GET /api/v1/tenant/{tenant}/realestate/developers/{developer}
- Developer compounds: GET /api/v1/tenant/{tenant}/realestate/developers/{developer}/compounds
- Featured developers: GET /api/v1/tenant/{tenant}/realestate/developers/featured
- Property types list: GET /api/v1/tenant/{tenant}/realestate/property-types
- Property type details: GET /api/v1/tenant/{tenant}/realestate/property-types/{slug}
- Amenities list: GET /api/v1/tenant/{tenant}/realestate/amenities
- Search properties: GET /api/v1/tenant/{tenant}/realestate/search/properties
- Search compounds: GET /api/v1/tenant/{tenant}/realestate/search/compounds
- Search autocomplete: GET /api/v1/tenant/{tenant}/realestate/search/autocomplete
- Search facets: GET /api/v1/tenant/{tenant}/realestate/search/facets
- Popular searches: GET /api/v1/tenant/{tenant}/realestate/search/popular
- Nearby properties: GET /api/v1/tenant/{tenant}/realestate/search/nearby
- Map properties in bounds: GET /api/v1/tenant/{tenant}/realestate/map/properties
- Map clusters: GET /api/v1/tenant/{tenant}/realestate/map/clusters
- Property gallery: GET /api/v1/tenant/{tenant}/realestate/gallery/properties/{property}
- Compound gallery: GET /api/v1/tenant/{tenant}/realestate/gallery/compounds/{compound}

Inquiries (Public submission, but often used by authenticated users):
- Submit property inquiry: POST /api/v1/tenant/{tenant}/realestate/inquiries/property/{property}
- Submit compound inquiry: POST /api/v1/tenant/{tenant}/realestate/inquiries/compound/{compound}
- Submit general inquiry: POST /api/v1/tenant/{tenant}/realestate/inquiries/general

### Gaps

- No saved searches or alert subscriptions.
- No inquiry tracking or inquiry history for authenticated users.
- No appointment booking or preferred contact scheduling.
- No property comparison or curated shortlists.

### Near-Future Ideas (Feasible) + Why It Matters + Proposed Endpoints

1) Saved Searches and Alerts
- Why it matters: Drives return traffic and reduces user effort in re-searching.
- Proposed endpoints:
  - GET /api/v1/tenant/{tenant}/realestate/saved-searches
  - POST /api/v1/tenant/{tenant}/realestate/saved-searches
  - DELETE /api/v1/tenant/{tenant}/realestate/saved-searches/{id}
  - POST /api/v1/tenant/{tenant}/realestate/saved-searches/{id}/alerts

2) Inquiry Tracking for Authenticated Users
- Why it matters: Builds trust and reduces duplicate inquiries.
- Proposed endpoints:
  - GET /api/v1/tenant/{tenant}/realestate/my-inquiries
  - GET /api/v1/tenant/{tenant}/realestate/my-inquiries/{id}

3) Viewing Appointment Booking
- Why it matters: Improves conversion and agent coordination.
- Proposed endpoints:
  - POST /api/v1/tenant/{tenant}/realestate/appointments
  - GET /api/v1/tenant/{tenant}/realestate/appointments
  - PATCH /api/v1/tenant/{tenant}/realestate/appointments/{id}
  - DELETE /api/v1/tenant/{tenant}/realestate/appointments/{id}

4) Property Comparison
- Why it matters: Helps shortlisting and decision-making.
- Proposed endpoints:
  - POST /api/v1/tenant/{tenant}/realestate/compare
  - GET /api/v1/tenant/{tenant}/realestate/compare

5) Shareable Shortlists
- Why it matters: Enables family decision-making and organic referrals.
- Proposed endpoints:
  - POST /api/v1/tenant/{tenant}/realestate/shortlists
  - GET /api/v1/tenant/{tenant}/realestate/shortlists/{id}
  - POST /api/v1/tenant/{tenant}/realestate/shortlists/{id}/items
  - DELETE /api/v1/tenant/{tenant}/realestate/shortlists/{id}/items/{property}

---

## Agent (Authenticated)

### All Agent Endpoints

- GET /api/v1/tenant/{tenant}/agent/realestate/dashboard
- GET /api/v1/tenant/{tenant}/agent/realestate/statistics
- GET /api/v1/tenant/{tenant}/agent/realestate/properties
- GET /api/v1/tenant/{tenant}/agent/realestate/inquiries
- PUT /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}
- POST /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/contact

### Current Capabilities and Endpoints

Agent Dashboard and Stats:
- Dashboard overview: GET /api/v1/tenant/{tenant}/agent/realestate/dashboard
- Agent statistics: GET /api/v1/tenant/{tenant}/agent/realestate/statistics

Assigned Properties:
- List assigned properties: GET /api/v1/tenant/{tenant}/agent/realestate/properties

Assigned Inquiries:
- List assigned inquiries: GET /api/v1/tenant/{tenant}/agent/realestate/inquiries
- Update inquiry status and notes: PUT /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}
- Mark contacted: POST /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/contact

### Gaps

- No task or reminder system for follow-ups or SLA timing.
- No activity timeline or contact log per inquiry.
- No viewing schedule management tools.
- No lead scoring or priority queue.

### Near-Future Ideas (Feasible) + Why It Matters + Proposed Endpoints

1) Follow-up Reminders and SLA Timers
- Why it matters: Increases conversion by improving response time.
- Proposed endpoints:
  - POST /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/reminders
  - GET /api/v1/tenant/{tenant}/agent/realestate/reminders
  - PATCH /api/v1/tenant/{tenant}/agent/realestate/reminders/{id}

2) Inquiry Timeline and Notes History
- Why it matters: Preserves context and reduces missed follow-ups.
- Proposed endpoints:
  - GET /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/timeline
  - POST /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/notes

3) Viewing Scheduling
- Why it matters: Streamlines scheduling and reduces manual coordination.
- Proposed endpoints:
  - POST /api/v1/tenant/{tenant}/agent/realestate/viewings
  - GET /api/v1/tenant/{tenant}/agent/realestate/viewings
  - PATCH /api/v1/tenant/{tenant}/agent/realestate/viewings/{id}
  - DELETE /api/v1/tenant/{tenant}/agent/realestate/viewings/{id}

4) Lead Scoring and Priority Queue
- Why it matters: Helps agents focus on high-value leads.
- Proposed endpoints:
  - GET /api/v1/tenant/{tenant}/agent/realestate/inquiries/priority
  - PATCH /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/score

5) Canned Responses and Follow-up Templates
- Why it matters: Ensures consistent, fast responses.
- Proposed endpoints:
  - GET /api/v1/tenant/{tenant}/agent/realestate/templates
  - POST /api/v1/tenant/{tenant}/agent/realestate/templates
  - POST /api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/send-template
