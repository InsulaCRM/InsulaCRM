# NC Wholesale Seller Appointment & Offer Readiness

## Workflow
- **NC Wholesale - Seller Appointment & Offer Readiness**
- n8n webhook: `POST nc-wholesale/seller-readiness`

## Purpose
Turns a qualified seller response into an operational readiness state instead of continuing the seller through the same generic SMS sequence.

The workflow:
1. Reloads the lead and property from InsulaCRM.
2. Reads the latest seller-response classification and opportunity score.
3. Determines whether the seller is ready for a call.
4. Calculates wholesale deal numbers when property inputs are available.
5. Determines whether an offer package is ready for human review.
6. Persists the readiness state to the lead.
7. Logs the readiness event as an InsulaCRM activity.

## Appointment readiness
The workflow considers these seller-response classifications for call readiness:
- MOTIVATED
- INTERESTED
- CALLBACK
- PRICE_OBJECTION

A seller is marked `appointment_ready=true` when:
- the lead is not marked DNC, and
- the opportunity score is at least 55, or the response is MOTIVATED, INTERESTED, or CALLBACK.

This is an operational qualification signal, not a legal or consent determination.

## Offer readiness
The workflow calculates:

`MAO = (ARV × 0.70) - repairs`

It marks `offer_ready=true` only when:
- appointment readiness is true,
- ARV/estimated value is available,
- repair estimate is available,
- asking price is available,
- the calculated spread is positive.

The workflow **does not send or make a binding offer automatically**. It prepares the numbers and flags the lead for human offer review.

## Lead custom field
The workflow stores the result under:

`custom_fields.nc_wholesale_readiness`

Example structure:
- `appointment_ready`
- `offer_ready`
- `action`
- `classification`
- `opportunity_score`
- `offer_inputs.arv`
- `offer_inputs.repair_estimate`
- `offer_inputs.asking_price`
- `offer_inputs.mao`
- `offer_inputs.estimated_spread`
- `missing_inputs`
- `ready_at`
- `version`

Actions:
- `schedule_call`
- `prepare_offer`
- `nurture`
- `do_not_contact`

## n8n environment variables
- `INSULACRM_API_URL`
- `INSULACRM_API_KEY`

No provider secrets are committed.

## Integration point
The seller response classifier should call this webhook after it persists the inbound seller classification. The readiness workflow can then become the handoff point between seller conversation automation and acquisition/offer workflow.

## Production guardrails
- Keep DNC state authoritative in InsulaCRM.
- Run campaign outreach through the existing seller contact gate before sending SMS.
- Do not automatically transmit a purchase offer to a seller from this workflow.
- Human review remains required before any binding offer or contract action.
