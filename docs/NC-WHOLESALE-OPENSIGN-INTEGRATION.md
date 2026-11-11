# NC Wholesale — OpenSign E-Signature Integration

InsulaCRM remains the source of truth for wholesale deals and transaction history. OpenSign is the e-signature engine; GitHub stores the integration code, not executed contracts.

## Implemented

- Tenant-scoped signature requests linked to a wholesale deal.
- Generated PDFs can be sent to OpenSign with one or more signers.
- OpenSign returns the document object ID and signer URLs; InsulaCRM stores both.
- OpenSign webhook events are authenticated with HMAC-SHA256 using the x-webhook-signature header.
- viewed, signed, completed, and declined events update the InsulaCRM signature request.
- On completed, InsulaCRM downloads the executed PDF and completion certificate into a predictable deal archive:
  deals/{deal_id}/executed/
- Executed PDFs and completion certificates are attached to the deal's existing document records.
- InsulaCRM emits deal.contract.executed for n8n orchestration.
- API credentials and webhook secrets are environment variables only.

## API

Authenticated endpoint:

POST /api/v1/deals/{id}/signature-requests

Required:
- generated_document_id
- signers[]
- widgets[] when signer widgets are not already included on the signer records

Each signer:
- name
- email
- optional phone
- optional role
- optional widgets

The request is sent by email through OpenSign. Phone numbers are retained for signer identity and future link-sharing/SMS workflows; InsulaCRM does not assume that SMS delivery is free or native to the selected OpenSign deployment.

List requests:

GET /api/v1/deals/{id}/signature-requests

Webhook endpoint:

POST /api/v1/webhooks/opensign

The webhook is intentionally outside the normal X-API-Key middleware because OpenSign authenticates it with its own HMAC signature. Set OPENSIGN_WEBHOOK_SECRET to the same secret configured for the OpenSign webhook.

## Environment

Set these values outside Git:

- OPENSIGN_BASE_URL
- OPENSIGN_API_TOKEN
- OPENSIGN_WEBHOOK_SECRET
- OPENSIGN_SENDER_NAME
- OPENSIGN_SENDER_EMAIL

OpenSign documents a sandbox API and sandbox tokens for development. The current integration defaults to the sandbox API so production signing is not accidentally enabled before testing.

## Archive

The transaction archive is keyed by deal ID for tenant isolation:

deals/{deal_id}/executed/

The deal record retains:
- property and seller lead
- committed buyer
- contract price
- assignment fee
- transaction structure
- closing provider
- activities
- signature request records
- executed document attachments

Do not store executed contracts, API tokens, webhook secrets, or private signing URLs in GitHub.

## Legal/compliance guardrail

This integration provides e-signature technology and audit information. It does not determine whether a particular contract or signing method is legally sufficient. Use the attorney-approved contract/template already recorded by the wholesale Contract Protection Gate and follow applicable law and closing-provider requirements.

## Testing

Use the OpenSign sandbox first. Verify:
1. PDF is generated and stored.
2. Signature request is created.
3. Signer receives the email.
4. Signer completes the document.
5. OpenSign sends the completed webhook.
6. InsulaCRM validates the webhook signature.
7. Executed PDF and certificate are archived.
8. deal.contract.executed is emitted.

No CI or production e-sign test is claimed until GitHub/InsulaCRM reports one.
