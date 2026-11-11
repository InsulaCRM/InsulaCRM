# Buyer Buy-Box Underwriting

The wholesale disposition engine now uses buyer-specific economics instead of relying only on a generic 70% ARV benchmark.

## Buyer fields

Each buyer can store:

- Maximum purchase price
- Maximum all-in percentage of ARV
- Target profit
- Preferred property types
- Preferred strategies (flip/rental/etc.)
- Preferred property conditions
- Preferred ZIP codes and states
- Transaction-cost percentage
- Holding-cost percentage
- Financing-cost percentage
- Risk-buffer percentage

## Match behavior

When a wholesale deal reaches the configured buyer-match trigger stage:

1. InsulaCRM loads the deal property and buyer database.
2. Each buyer is underwritten against ARV, repairs, contract price, assignment fee, condition, and strategy.
3. A buyer's max purchase price is treated as a hard ceiling when supplied.
4. Buyer-specific underwriting determines whether the contract price fits the buyer's economics.
5. Matching also considers ZIP, property type, state, strategy, and condition.
6. The match record stores the underwriting snapshot and a human-readable reason.

A buyer whose underwriting ceiling is below the current contract price is not returned as a viable wholesale match.

## Important limitation

A match is an underwriting and disposition-priority signal. It is not a buyer commitment, proof of funds, or guarantee that the buyer will close.

The system should only represent a buyer as committed after the buyer has actually confirmed the deal through the applicable disposition workflow.
