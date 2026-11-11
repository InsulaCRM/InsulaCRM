# NC Wholesale Contract & Transaction Risk Controls

## Important
This system reduces operational risk but cannot guarantee immunity from a lawsuit, liability, licensing enforcement, or a failed closing. North Carolina transaction documents and special provisions should be drafted or reviewed by a North Carolina real-estate attorney before production use.

The North Carolina Real Estate Commission says a bona fide buyer may assign rights under a purchase contract without a real-estate license, but warns that conduct can become unlicensed brokerage activity when a wholesaler markets a property they do not intend to purchase, misrepresents an ownership interest, handles third-party funds, negotiates contracts between seller and buyer, or markets buyers generally rather than in connection with a specific assigned contract.

## Recommended structures

### 1. Assignment of purchase contract
Use when counsel-approved purchase documents expressly permit assignment and the seller is properly informed.

### 2. Double close / back-to-back closing
Use when assignment is not appropriate or the transaction needs the wholesaler to acquire title before resale. This needs funding, title/closing coordination, and attorney approval. It is not automatically safer; it adds capital, timing, tax, title, and closing-cost issues.

### 3. Novation / contract substitution
Use only when a North Carolina attorney prepares or approves the structure for the specific transaction. Do not let the automation invent a novation agreement.

## Protections counsel should evaluate

The attorney-reviewed contract should address, as applicable:
- buyer identity and seller identity
- assignment rights or an attorney-approved alternative
- due-diligence/inspection termination rights and deadlines
- title and marketable-title requirements
- closing attorney/title-company process
- financing or transactional-funding contingencies where appropriate
- property access and inspection rights
- seller representations and disclosures appropriate to the property
- written notice and communication requirements
- closing-date extension mechanics where appropriate
- treatment of earnest money/due-diligence money
- default and remedies
- allocation of closing costs
- no promise that a third-party buyer will be found
- no promise of closing outside the actual contract terms
- disclosure or acknowledgment of intended assignment/resale strategy when counsel determines it is appropriate

No clause should be treated as a blanket waiver of liability for fraud, misrepresentation, statutory violations, intentional misconduct, or other non-waivable obligations.

## System controls

The deal handoff creates an InsulaCRM deal at offer_presented. It does not generate a binding seller contract.

The contract protection gate requires:
- attorney-approved template ID
- executed contract
- assignment-rights confirmation for assignment transactions
- closing attorney confirmation
- due-diligence end date
- closing date

Only after these checks pass does the workflow move the deal to under_contract.

## Template library

Maintain versioned attorney-approved templates such as:
- NC-ASSIGNMENT-v1-ATTORNEY-APPROVED
- NC-DOUBLE-CLOSE-v1-ATTORNEY-APPROVED
- NC-NOVATION-v1-ATTORNEY-APPROVED

The automation should store the template identifier/version and factual transaction data. It should not generate new legal clauses with AI.

## Failed-buyer / failed-closing handling

If no end buyer is found, the workflow must not invent a buyer or misrepresent buyer status. The deal stays subject to the actual purchase contract and its deadlines.

If the wholesaler cannot close, the system should route to the attorney-approved termination/default process based on the actual contract rather than automatically declaring the contract cancelled.

## Sources

North Carolina Real Estate Commission: wholesaling brokerage activities warning.

North Carolina General Statute 25-2-210: contractual rights can generally be assigned unless the contract or applicable rules provide otherwise.
