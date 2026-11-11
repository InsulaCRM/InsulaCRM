# Wholesale Intake → n8n Wiring

InsulaCRM is the source of truth. The wholesale deal intake now emits tenant-scoped webhooks so n8n can orchestrate the existing acquisition/disposition workflows.

## Events

### deal.intake.created

Emitted after a wholesale deal intake is saved.

Use the existing workflow:
- integrations/n8n/NC-Wholesale-Deal-Creation-Disposition-Handoff.json
- webhook path: nc-wholesale/deal-handoff

The webhook body follows the InsulaCRM webhook envelope: event, timestamp, data. The workflow accepts the envelope and uses data.lead_id.

The workflow reloads the lead, checks DNC and offer-readiness safeguards, detects an existing active deal, creates or updates the deal, persists wholesale deal state, and logs the handoff.

### deal.contract.ready

Emitted when intake contains executed contract status = executed, contract review status = approved, due-diligence end date, and closing date.

Use:
- integrations/n8n/NC-Wholesale-Contract-Protection-Gate.json
- webhook path: nc-wholesale/contract-readiness

The gate independently verifies DNC protection, transaction structure, attorney-approved template reference, executed contract, assignment rights when applicable, permitted closing-provider type and confirmation, due-diligence and closing dates, and EMD controls. Only after those checks pass does n8n move the InsulaCRM deal to under_contract.

## Tenant webhook configuration

Create two active InsulaCRM webhooks for the tenant:

| Event | n8n workflow |
|---|---|
| deal.intake.created | Deal Creation & Disposition Handoff |
| deal.contract.ready | Contract Protection Gate |

Use the n8n production webhook URLs after the workflows are imported and activated.

Do not commit n8n secrets, API keys, or webhook signing secrets to the repository.

## Contract/legal guardrail

The intake stores an attorney-approved template reference; it does not generate legal clauses. State/local requirements and the executed contract control the closing workflow.

## Current state

This wiring is implemented on feat/nc-wholesale-automation-foundation. No passing CI result is claimed unless GitHub reports one.

## Disposition-ready buyer package

When a wholesale deal changes to `dispositions`, InsulaCRM now dispatches the `deal.disposition.ready` webhook event. Configure that tenant webhook to point to the n8n workflow:

`integrations/n8n/NC-Wholesale-Disposition-Ready-Buyer-Package.json`

The workflow calls:

`GET /api/v1/deals/{deal_id}/disposition-package`

It ranks viable buyer matches using the stored buyer-specific underwriting snapshot, records a disposition-readiness activity, and returns the top buyer plus viable buyer count.

This workflow prepares the disposition package; it does not represent a buyer as committed and does not automatically send buyer outreach.


## Buyer outreach and response

- `deal.disposition.ready` can trigger `NC-Wholesale-Buyer-Outreach.json`.
- The outreach workflow uses the ranked viable buyer matches and sends through the configured `BUYER_SMS_PROVIDER_URL`.
- Buyer sends are guarded to 8 AM–9 PM Eastern time in the workflow. Provider-level compliance settings should also be enabled.
- `NC-Wholesale-Buyer-Response-Classifier.json` accepts inbound buyer messages, classifies common intents (interested, question, not interested, callback, opt-out), and updates the matching record.
- `STOP`, `UNSUBSCRIBE`, `CANCEL`, `REMOVE`, and `DO NOT CONTACT` stop the matching outreach record.
- A buyer response is never treated as a binding commitment automatically. Explicit confirmation remains required.


## Buyer commitment

- `NC-Wholesale-Buyer-Commitment-Handoff.json` accepts an explicit buyer confirmation.
- The CRM endpoint `POST /api/v1/deals/{id}/buyer-commitment` requires the matching buyer/match, an eligible prior response, an approved Contract Protection Gate, and explicit confirmation.
- A buyer commitment cannot be created from an opt-out or stopped outreach record.
- Only one buyer can be committed to a deal at a time.
- Successful commitment stores `committed_buyer_id` and `buyer_committed_at`, moves the wholesale deal to `buyer_committed`, and emits `deal.buyer.committed`.
- This does not represent a completed assignment or closing; those remain separate authorized transaction steps.


## Closing readiness and handoff

- `GET /api/v1/deals/{id}/closing-readiness` evaluates the wholesale closing gate.
- The gate requires an approved Contract Protection Gate, a committed buyer, transaction structure, confirmed closing provider, closing date, required assignment rights, confirmed buyer-funded EMD when applicable, and completed/waived transaction checklist items.
- `PUT /api/v1/deals/{id}/checklist/{itemId}` records checklist progress.
- `POST /api/v1/deals/{id}/advance-closing` can move a buyer-committed/assigned deal to `closing` only when the readiness gate passes.
- Successful advancement emits `deal.closing.ready`.
- `NC-Wholesale-Closing-Ready-Handoff.json` verifies readiness and logs the handoff; it does not fabricate legal documents, alter the closing provider, or declare a closing completed.
