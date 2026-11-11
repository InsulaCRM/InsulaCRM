# NC Wholesale Automation Foundation

## Purpose

This branch establishes the integration boundary for the North Carolina virtual-wholesaling system without replacing InsulaCRM or modifying the production `main` branch.

## System roles

- **InsulaCRM** — system of record for leads, properties, deals, buyers, activities, scoring, DNC/compliance state, and tenant data.
- **n8n** — orchestration layer for scheduled imports, enrichment, seller/buyer messaging, routing, retries, and external API calls.
- **AI** — qualification, conversation classification, deal analysis, follow-up drafting, and buyer/deal matching assistance.
- **External data providers** — property/owner data and skip tracing. Provider adapters stay outside the CRM core.
- **Notifications** — operational alerts/reports through configurable webhook destinations.

## What the existing DispoFlow workflows already provide

The existing `dispoflow-disposition` repository contains 23 n8n workflows covering acquisition, disposition, and buyer intelligence. They currently assume:

- Convex as the data/brain layer
- Close CRM as CRM
- Twilio for messaging
- BatchData for property/buyer data
- Tracerfy for enrichment
- Slack for reporting

Those workflows are valuable as automation references, but they should not be imported unchanged into the production system because they depend on proprietary Convex schemas/functions that are not included in that repository.

## Target architecture

```
Property / owner source
        |
        v
n8n ingestion + dedup + compliance gate
        |
        v
InsulaCRM API  <---->  AI services
        |
        +----> seller workflow / SMS provider
        |
        +----> deal qualification
        |
        +----> buyer database + buyer matching
        |
        +----> disposition / buyer outreach
        |
        +----> reporting + alerts
```

## First integration contract

InsulaCRM already exposes authenticated REST endpoints under `/api/v1/` using the `X-API-Key` header.

Relevant endpoints:

- `POST /api/v1/leads`
- `PUT /api/v1/leads/{id}`
- `POST /api/v1/properties`
- `PUT /api/v1/properties/{id}`
- `POST /api/v1/deals`
- `PUT /api/v1/deals/{id}`
- `POST /api/v1/buyers`
- `PUT /api/v1/buyers/{id}`
- `POST /api/v1/activities`

Lead ingestion already performs duplicate checks, timezone detection from ZIP, property creation, AI auto-qualification, lead distribution, motivation scoring, audit logging, and a `lead.created` webhook.

## Data mapping

### Acquisition lead

External source -> InsulaCRM:

- owner first/last name -> `first_name` / `last_name`
- phone -> `phone`
- email -> `email`
- source/list name -> `source`
- owner/property notes -> `notes`
- property address/city/state/ZIP -> property fields
- source record identifier -> `external_id`

### Property enrichment

Provider -> InsulaCRM property:

- property type
- bedrooms
- bathrooms
- square footage
- year built
- lot size
- estimated value
- repair estimate
- after-repair value
- asking price
- distress markers
- listing/sale data

InsulaCRM's wholesale property API already calculates maximum allowable offer when ARV and repairs are supplied.

### Buyer

Buyer provider / manual onboarding -> InsulaCRM:

- name
- company
- phone/email
- maximum purchase price
- preferred property types
- preferred states
- preferred ZIP codes
- notes

### Deal

Qualified lead/property -> InsulaCRM:

- lead ID
- contract price
- earnest money
- contract/closing dates
- assignment fee
- inspection period
- deal stage
- notes

## Compliance boundary

No automated seller or buyer outreach should bypass the CRM's DNC state, send-window controls, opt-out handling, or provider-specific compliance requirements.

The n8n layer should treat InsulaCRM as the authoritative contact/compliance state rather than maintaining a second DNC database.

## Migration rule

Do not migrate Convex schemas into InsulaCRM blindly. Instead:

1. Keep the original DispoFlow workflows intact.
2. Identify each Convex read/write contract.
3. Map only the required records to InsulaCRM entities.
4. Replace Convex calls with InsulaCRM API calls where an equivalent endpoint exists.
5. Add a small InsulaCRM endpoint only when the existing API cannot represent a required workflow operation.
6. Keep external provider credentials in n8n/environment secrets, never in committed workflow JSON.

## North Carolina operating focus

Initial market configuration should support:

- Hickory / Catawba County
- Charlotte / Mecklenburg and surrounding counties
- Raleigh / Wake and surrounding counties
- Wilmington / New Hanover and surrounding counties

Lead segments should include:

- distressed properties
- vacant properties
- vacant land
- absentee owners
- high equity
- tax delinquent
- probate/inherited
- pre-foreclosure
- code violations
- tired landlords
- other configurable distress signals

## Next implementation phases

### Phase 1 — foundation
- Import external leads into InsulaCRM.
- Normalize addresses and phone numbers.
- Enforce duplicate/DNC/compliance gates.
- Capture source and external IDs.
- Establish webhook events back to n8n.

### Phase 2 — enrichment
- Add provider adapters for property data and skip tracing.
- Enrich property records before paid downstream actions.
- Store provider IDs and provenance.

### Phase 3 — acquisition automation
- Seller response classification.
- Follow-up sequencing.
- Appointment/task creation.
- Motivation scoring and deal qualification.

### Phase 4 — disposition
- Buyer onboarding and verification.
- Buyer preference normalization.
- Deal scoring.
- Buyer matching and tiered distribution.
- Buyer response classification and follow-up.

### Phase 5 — analytics
- Pipeline conversion
- lead source ROI
- response rate
- appointment rate
- contract rate
- assignment spread
- buyer response rate
- market-level performance

## Important

This branch is intentionally an integration foundation. It does not modify `main`, delete source workflows, or touch the separate `haven-heartfelt-connections` repository.
