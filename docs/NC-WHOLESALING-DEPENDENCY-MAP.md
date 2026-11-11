# NC Wholesaling Dependency Map

## Source-of-truth decision

- InsulaCRM: leads, properties, buyers, deals, activities, DNC state, motivation/AI scoring and tenant-scoped records.
- n8n: orchestration, provider adapters, scheduling, routing and retry handling.
- External providers: property data, skip tracing, SMS/email and AI.
- DispoFlow remains a reference implementation. Do not merge it wholesale into InsulaCRM.

## Existing acquisition dependencies observed

| Capability | Current dependency | New target |
|---|---|---|
| Seller response classification | Convex + Close CRM | InsulaCRM webhook/API + n8n + AI provider |
| Seller CRM import | Convex + Close CRM | InsulaCRM lead ingest API |
| Seller follow-up queue | Convex queue + Close CRM | InsulaCRM sequences/tasks + n8n scheduler |
| Skip-trace result routing | Convex + Tracerfy | InsulaCRM APIs + Tracerfy adapter |
| Distressed-property staging | Convex + BatchData + Tracerfy | n8n provider adapters + InsulaCRM APIs |
| SMS cleanup/DNC | Close CRM + Convex | InsulaCRM DNC/compliance state + SMS provider |
| Reporting | Convex + Slack | InsulaCRM stats + n8n/Slack |

## Existing endpoint evidence

The DispoFlow acquisition workflows reference:
- YOUR_CONVEX_DEPLOYMENT.convex.site for classification, DNC, follow-up queues, staging and logging.
- api.close.com for CRM lead/activity/task operations.
- api.batchdata.com/api/v1/property/search for property data.
- tracerfy.com/v1/api/trace/ for skip tracing.
- Slack for operational reporting and alerts.

## Migration rule

1. Identify the business action.
2. Map it to an existing InsulaCRM API/service where possible.
3. Add a small InsulaCRM endpoint only when necessary.
4. Keep provider credentials in n8n credentials/environment variables.
5. Preserve DNC, timezone and opt-out guards before outbound seller contact.

## NC operating markets

- Hickory / Catawba County
- Charlotte
- Raleigh
- Wilmington

## Lead segments

- distressed
- vacant
- vacant land
- absentee owner
- high equity
- tax delinquent
- probate
- pre-foreclosure
- code violations
- tired landlord

## Implementation status

Completed:
- InsulaCRM-centered architecture.
- n8n lead-ingest workflow.
- Lead webhook response-mode correction.
- Acquisition dependency mapping.

Next:
1. Property-data adapter.
2. Skip-trace adapter.
3. Compliance/contact gate.
4. Seller follow-up adapter.
5. Buyer/disposition adapter.
