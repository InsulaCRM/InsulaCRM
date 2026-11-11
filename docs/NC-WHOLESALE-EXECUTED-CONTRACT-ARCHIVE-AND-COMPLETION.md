# NC Wholesale — Executed Contract Archive and Completion

## Executed contract archive

InsulaCRM now exposes a tenant-scoped Contract Archive at `/pipeline/archive`.

The archive only lists deals with a completed OpenSign signature request. Each record shows:
- property address
- seller
- committed buyer / buyer legal name
- transaction structure
- current deal stage
- contract price
- signature completion timestamp
- archive directory
- executed PDF
- OpenSign completion certificate

OpenSign's completed webhook is the trigger that marks the request completed and provides the signed PDF and certificate URLs. InsulaCRM downloads those files into the deal archive path `deals/{deal_id}/executed/` and creates DealDocument records for both files. citeturn524438search1

## Completion lifecycle

Wholesale completion is intentionally explicit.

### Assignment completion

`POST /api/v1/deals/{id}/assignment-complete`

Requires:
- wholesale mode
- Contract Protection Gate approved
- executed contract recorded
- committed buyer
- closing readiness gate passed
- deal in buyer_committed, assigned, or closing

Optional fields: `final_assignment_fee`, `closing_result`, `closing_result_notes`.

The system records `assignment_completed_at`, moves the deal to `assigned`, and emits `deal.assigned.completed`.

### Closing completion

`POST /api/v1/deals/{id}/closing-complete`

Requires:
- wholesale mode
- Contract Protection Gate approved
- executed contract recorded
- committed buyer
- closing readiness gate passed
- deal in assigned or closing
- assignment completion for assignment-structured deals

Optional fields: `final_assignment_fee`, `final_closing_amount`, `closing_result`, `closing_result_notes`.

The system records `closed_won_at`, moves the deal to `closed_won`, and emits `deal.closed`.

No endpoint fabricates a closing, buyer commitment, legal approval, or payment confirmation.

## n8n

`integrations/n8n/NC-Wholesale-Assignment-and-Closing-Completion.json`

Webhook path: `nc-wholesale/completion`.

Send `action=assignment_complete` to record assignment completion. Other completion actions are routed to closing completion.

n8n uses `INSULACRM_API_URL` and `INSULACRM_API_KEY` environment variables. No credentials are committed.

## OpenSign production note

OpenSign documents are created through its API using the `x-api-token` header. The API supports reusable templates and completed-document webhooks. citeturn524438search0turn524438search1

Sandbox webhook support is available for testing. OpenSign's hosted live webhook support may require a paid plan; self-hosting OpenSign remains the open-source path if you want to avoid that hosted-plan dependency. citeturn524438search2

## Recommended production test

1. Create a test wholesale deal.
2. Pass the Contract Protection Gate.
3. Generate the attorney-approved contract PDF.
4. Send it through OpenSign.
5. Complete all required signatures.
6. Confirm the completed webhook reaches InsulaCRM.
7. Confirm the executed PDF and certificate appear in the Contract Archive.
8. Confirm the closing-readiness gate passes.
9. Record assignment completion or closing completion through the explicit endpoint.
10. Confirm the corresponding n8n lifecycle webhook fires.

Do not use unapproved contract language or treat the workflow as legal advice.