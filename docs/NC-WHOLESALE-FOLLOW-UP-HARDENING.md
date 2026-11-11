# NC Wholesale Follow-Up Production Hardening

## Changes
- Seller follow-up now invokes the existing seller contact gate before SMS delivery.
- The gate enforces the configured 08:00-21:00 local operational window, DNC state, blocked statuses, and phone presence.
- The dispatcher requests up to 100 leads per run instead of relying on the default page size.
- Dispatcher cadence now uses the seller response's stored `follow_up_days` when available rather than always forcing 7 days.
- Dispatcher skips explicit seller stop/not-for-sale classifications.

## Required environment variable
`NC_WHOLESALE_CONTACT_GATE_WEBHOOK_URL` — active n8n webhook URL for the seller contact gate.

## Important
This remains an operational workflow guard, not a legal determination of TCPA, federal DNC, state-law, consent, or carrier compliance. Provider-level suppression and applicable legal review remain required before production outreach.
