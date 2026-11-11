# NC / Multi-State Wholesale Contract Library

## Purpose

This library is a contract-template registry and workflow configuration, not a substitute for state-specific legal advice or attorney-drafted forms. The CRM should only generate or populate a contract from a template that has been reviewed and approved for the applicable state and transaction structure.

## Contract types to support

1. Purchase & Sale Agreement — Wholesaler as Buyer
   - Seller → wholesaler
   - Used to acquire contractual rights before disposition.
   - Must use a state-appropriate, attorney-reviewed form.

2. Assignment of Contract
   - Wholesaler → end buyer
   - Transfers the wholesaler's contractual interest when assignment is permitted by the underlying contract and applicable law.
   - Assignment-rights confirmation is required before the system marks the deal assignment-ready.

3. Double-Closing / Back-to-Back Transaction
   - Seller → wholesaler → end buyer
   - Used when assignment is not appropriate or the transaction is structured as two closings.
   - Funding and closing mechanics must be confirmed with the closing professional.

4. Novation / Contract Substitution
   - Seller, original buyer, and replacement buyer as applicable.
   - Only available when the applicable state law, original contract, and attorney-approved documentation permit it.

5. Contract Addendum / Amendment
   - Changes dates, parties, terms, or other approved factual/transactional items.
   - Must use an approved state-specific form.

6. Extension Agreement
   - Extends due-diligence, closing, or other contractual deadlines when permitted.
   - Requires the actual contract's amendment/extension mechanism.

7. Termination / Cancellation Agreement
   - Used only when the contract and applicable law permit termination.
   - The system must never automatically cancel solely because a buyer has not been found.

8. Buyer Assignment / Disposition Acknowledgment
   - Transaction-specific acknowledgment or disclosure where counsel-approved documentation calls for it.

9. Joint Venture / Co-Wholesaling Agreement
   - Used only when counsel-approved and appropriate to the parties' relationship and state law.

10. Closing / Settlement Instructions
    - State- and closing-provider-specific instructions.
    - Should identify the selected attorney, title company, escrow company, or other permitted settlement provider.

## Required template metadata

Every template should have:
- Template ID
- State
- County/locality if applicable
- Transaction structure
- Version
- Attorney-approved status
- Approval date
- Approved-by / law firm
- Effective date
- Expiration/review date
- Assignment permitted/conditional/not permitted
- Required disclosures
- Required closing method
- Required signatures
- Required attachments

Example IDs:
- NC-PURCHASE-v1-ATTORNEY-APPROVED
- NC-ASSIGNMENT-v1-ATTORNEY-APPROVED
- NC-DOUBLE-CLOSE-v1-ATTORNEY-APPROVED
- NC-NOVATION-v1-ATTORNEY-APPROVED

The same naming pattern should be used for other states after state-specific review.

## EMD strategy

The CRM should support a target EMD range of $0–$50 for deals where the contract and seller permit it.

This is a workflow preference, not a guarantee that every seller, state, form, or closing professional will accept $0–$50.

Fields:
- emd_amount
- emd_target_min = 0
- emd_target_max = 50
- emd_required = yes/no/unknown
- emd_due_date
- emd_holder
- emd_source = wholesaler / end_buyer / other-approved-source
- buyer_covers_emd = yes/no/pending
- buyer_emd_reimbursement_or_credit = yes/no/pending
- emd_funding_confirmed = yes/no
- emd_exception_reason

### Preferred decision logic

1. If the approved contract permits $0 EMD, allow $0.
2. If EMD is required, target $0–$50 when the seller accepts that amount.
3. If the seller requires more than $50, flag the deal rather than silently increasing the amount.
4. If the end buyer can legitimately fund or reimburse the EMD under the transaction structure, record that separately and require confirmation from the closing professional.
5. Never represent buyer funding as guaranteed until confirmed.
6. Never let the automation invent an EMD term that conflicts with the executed contract.

## Closing-provider flexibility

The system must not assume that every state uses an attorney closing.

Supported provider types:
- Closing attorney
- Title company
- Escrow company
- Settlement company
- Other state-permitted provider

Provider preference order:
1. State/local rules and the executed contract
2. Buyer-preferred closing provider, if permitted and accepted
3. Seller-preferred closing provider, if permitted and accepted
4. Mutually selected provider
5. System recommendation for an appropriate provider, subject to confirmation

The CRM should record both buyer and seller preferences rather than overwriting one with the other.

## No-buyer protection

If no end buyer is found, the system must:
- show no_buyer
- display the contract's actual due-diligence/termination/extension deadlines
- flag the deal for contract review
- route to the approved attorney/closing professional when needed
- never automatically cancel, default, or extend the contract without an authorized action

## Automation rule

The CRM may populate factual fields into an approved template, but it must not create novel legal language or modify legal protections without an attorney-approved template/version.