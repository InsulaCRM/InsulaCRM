# Adaptive Wholesale Offer / MAO Strategy

The property underwriting engine no longer treats 70% of ARV as a universal offer rule.

## Decision model

When buyer-specific economics are available, the system works backward from the end buyer:

- ARV
- repairs
- transaction costs
- holding costs
- financing costs
- risk buffer
- buyer profit target
- assignment fee

Seller contract ceiling:

`buyer max all-in - repairs - assignment fee`

If a buyer buy-box supplies a maximum all-in percentage, that buyer-specific percentage takes precedence.

## 70% benchmark

The classic 70% calculation remains available as a comparison benchmark:

`ARV × 0.70 - repairs`

It is not automatically the recommended seller offer.

The 70% rule is a common wholesaling shortcut, but published investor guidance describes it as a rule of thumb and notes that buyer percentages vary with property price, condition, strategy, and market. The system therefore records the benchmark separately from the buyer-economics result.

## Property fields

- `mao_strategy`: flip or rental
- `buyer_max_all_in_percent`: optional buyer-specific ceiling
- `buyer_profit_target`
- `transaction_costs`
- `holding_costs`
- `financing_costs`
- `risk_buffer`
- `assignment_fee_target`

If these are blank, the service supplies configurable underwriting defaults based on strategy and condition. These defaults are screening assumptions, not appraisals or guarantees.

## Important underwriting rule

The CRM should prefer actual buyer buy-box data, recent comparable sales, verified repair estimates, and property condition over generic percentages.

A property can have strong equity and still be a poor wholesale lead if the seller's required price leaves no buyer spread.

## Example

For a property with an estimated ARV of $330,000 and little/no repair requirement, the engine should not automatically produce a $231,000 seller ceiling simply because 70% equals $231,000.

It should calculate the buyer economics, compare that result with the 70% benchmark, and expose the assumptions so an acquisitions user can decide whether the lead is worth pursuing.

This is intentionally a screening model. Final pricing requires current comps, inspection/condition information, buyer-specific criteria, and appropriate professional review.

## Opportunity scoring integration

The n8n opportunity scorer now uses the adaptive underwriting result as an additional deal-quality signal. A property is not promoted solely because it has equity or distress. The scorer also checks whether the asking price leaves room under the buyer-economics seller contract ceiling.

The score response includes both the legacy 70% benchmark and the adaptive underwriting object, including buyer purchase ceiling, seller contract ceiling, estimated spread, viability, method, and assumptions. This lets acquisitions users see why a lead is attractive or unattractive rather than hiding the pricing model behind a single percentage.

Scoring version: nc-v2-adaptive-mao.
