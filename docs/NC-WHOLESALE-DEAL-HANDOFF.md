# NC Wholesale Deal Creation & Disposition Handoff

Flow:
Seller response -> readiness -> offer_presented deal -> attorney contract gate -> under_contract -> dispositions/buyer matching.

The handoff does not move a deal to under_contract merely because an offer is ready.

Guards:
- no DNC flag
- offer readiness
- property address
- asking price
- ARV

The workflow creates the InsulaCRM property and deal record and stores nc_wholesale_deal state.

Disposition should begin only after the contract protection gate confirms an executed, attorney-approved transaction and moves the deal to under_contract.