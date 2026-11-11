# NC Wholesale Seller Communication Engine

## Workflows
- NC Wholesale - Seller Response Classifier
- NC Wholesale - Seller Follow-Up Engine

## Required n8n environment variables
- INSULACRM_API_URL — base URL for the InsulaCRM deployment, without a trailing slash.
- INSULACRM_API_KEY — tenant API key used by the InsulaCRM X-API-Key middleware.
- SELLER_SMS_WEBHOOK_URL — provider adapter endpoint that accepts {to,message,lead_id,source}. Keep provider credentials inside the provider/n8n credential system; do not commit secrets.
- NC_WHOLESALE_READINESS_WEBHOOK_URL — n8n webhook URL for the seller appointment/offer readiness workflow.

## Inbound payload
POST `nc-wholesale/seller-response` with:
`{ lead_id, phone, message_text, received_at?, source_message_id? }`

Explicit opt-outs (STOP, UNSUBSCRIBE, CANCEL, END, QUIT, REMOVE, DO NOT CONTACT, DO NOT TEXT, WRONG NUMBER) are treated as hard stops. Hard stops set `do_not_contact=true` in InsulaCRM and do not schedule follow-up.

## Follow-up payload
POST `nc-wholesale/seller-follow-up` with `{ lead_id, follow_up_days? }`.

The engine reloads the lead from InsulaCRM, honors the DNC/hard-stop state, creates a response-aware message, optionally calls the configured SMS adapter, persists follow-up state into lead custom fields, and logs the outbound SMS as an InsulaCRM activity.

## Operational notes
- This is an orchestration layer, not a legal compliance guarantee.
- The existing seller contact gate remains the pre-send compliance check for campaign-style outreach.
- Provider adapters should enforce their own suppression/consent rules.
- Never commit provider API keys or phone-number lists to workflow JSON.
