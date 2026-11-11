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