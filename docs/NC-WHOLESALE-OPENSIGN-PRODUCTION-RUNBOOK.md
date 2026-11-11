# NC Wholesale — OpenSign Production Setup Runbook

This runbook moves the implemented InsulaCRM/OpenSign flow from code-complete to deployable configuration. It does not store credentials or signing secrets in GitHub.

## 1. Sandbox first

Use the sandbox base URL:

`https://sandbox.opensignlabs.com/api/v1.2`

Set:

- `OPENSIGN_BASE_URL=https://sandbox.opensignlabs.com/api/v1.2`
- `OPENSIGN_API_TOKEN=<sandbox token>`
- `OPENSIGN_WEBHOOK_SECRET=<random webhook secret>`
- `OPENSIGN_SENDER_NAME=<sender name>`
- `OPENSIGN_SENDER_EMAIL=<verified sender email>`

The sandbox token is for testing. Do not reuse a sandbox token for production.

## 2. OpenSign reusable template

For production contracts, prefer a reusable OpenSign template with signature widgets already placed.

Create a template for each attorney-approved contract workflow as needed:

- Purchase & Sale
- Assignment
- Double Close
- Novation
- Addendum/Amendment

Each signer role should have its required signature widget(s).

Record the OpenSign template ID in the InsulaCRM signature request option:

`opensign_template_id`

InsulaCRM already stores the template ID on `deal_signature_requests`.

Do not put template IDs, signing URLs, or credentials into source control unless they are explicitly non-secret identifiers.

## 3. OpenSign webhook

Configure OpenSign to send lifecycle events to:

`POST https://<your-insulacrm-domain>/api/v1/webhooks/opensign`

Configure the same webhook secret in both systems.

InsulaCRM verifies:

`X-Webhook-Signature = HMAC-SHA256(raw request body, OPENSIGN_WEBHOOK_SECRET)`

The webhook endpoint intentionally does not use the normal InsulaCRM X-API-Key middleware.

Expected events:

- viewed
- signed
- completed
- declined

The completed event is the important transaction event.

## 4. Completed-contract flow

When OpenSign sends `completed`:

1. InsulaCRM finds the signature request by OpenSign object ID.
2. The request becomes `completed`.
3. InsulaCRM records the external executed-file and certificate URLs.
4. InsulaCRM downloads the executed PDF.
5. InsulaCRM downloads the completion certificate.
6. Files are stored under:

`deals/{deal_id}/executed/`

7. DealDocument records are created.
8. A deal activity records execution.
9. InsulaCRM emits:

`deal.contract.executed`

The archive is tenant-scoped and keyed to the deal ID.

## 5. n8n downstream handoff

Import:

`integrations/n8n/NC-Wholesale-Contract-Executed-Archive.json`

Set the n8n production webhook URL on the tenant's InsulaCRM webhook for:

`deal.contract.executed`

The n8n workflow:

- receives the InsulaCRM webhook envelope
- reloads the deal using `INSULACRM_API_URL` and `INSULACRM_API_KEY`
- confirms the executed-contract archive event
- returns deal/property/seller/buyer context

It does not declare the transaction closed.

## 6. Outbound webhook security

InsulaCRM signs tenant webhooks with the tenant webhook secret.

Use a strong, unique secret for the n8n webhook configuration.

Do not place:

- `INSULACRM_API_KEY`
- webhook secrets
- OpenSign API tokens
- private signing URLs

inside workflow JSON committed to GitHub.

Use n8n credentials/environment variables.

## 7. Production environment

After the sandbox round trip succeeds, change only the deployment environment configuration:

`OPENSIGN_BASE_URL=<production OpenSign API v1.2 URL>`

Then supply the production API token and production webhook secret through the deployment secret manager/environment.

Do not change the InsulaCRM application code merely to switch environments.

## 8. Required live acceptance test

Before using this for a real wholesale transaction, verify one controlled test deal:

- attorney-approved contract/template
- Contract Protection Gate approved
- generated PDF exists
- seller email exists
- OpenSign request created
- signer receives invitation
- signer completes document
- OpenSign sends completed webhook
- InsulaCRM accepts only a correctly signed webhook
- executed PDF appears in `deals/{deal_id}/executed/`
- completion certificate appears in the same directory
- DealDocument records exist
- deal activity records execution
- n8n receives `deal.contract.executed`
- no duplicate DealDocument records are created if OpenSign retries the completed webhook

## 9. Important legal/compliance boundary

The system provides e-signature technology, document storage, workflow automation, and audit information. It does not determine whether a particular contract, electronic signature, disclosure, assignment, or closing procedure satisfies applicable law.

Use the attorney-approved template and the requirements of the selected closing professional.

## Current implementation status

Implemented in the wholesale branch:

- OpenSign API integration
- tenant-scoped signature requests
- HMAC webhook verification
- executed PDF archive
- completion certificate archive
- DealDocument attachment
- `deal.contract.executed` webhook
- n8n archive handoff
- OpenSign service tests
- webhook security tests
- archive retry/idempotency protection
- GitHub Actions Laravel CI workflow

A live sandbox round trip is not claimed until real sandbox credentials and a reachable deployed webhook are configured.
