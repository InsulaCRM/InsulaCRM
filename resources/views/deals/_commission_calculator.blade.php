{{-- Commission Calculator Card --}}
{{-- Include: @include('deals._commission_calculator', ['deal' => $deal]) --}}

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="3" width="16" height="18" rx="2"/><rect x="8" y="7" width="8" height="3" rx="1"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/><line x1="8" y1="17" x2="8" y2="17.01"/><line x1="12" y1="17" x2="12" y2="17.01"/><line x1="16" y1="17" x2="16" y2="17.01"/></svg>
            {{ __('Commission Calculator') }}
        </h3>
    </div>
    <div class="card-body">
        <div class="mb-2">
            <label class="form-label">{{ __('Sale Price ($)') }}</label>
            <input type="number" class="form-control form-control-sm" id="calc-sale-price" step="0.01" min="0" value="{{ $deal->contract_price ?? '' }}">
        </div>
        <div class="mb-2">
            <label class="form-label">{{ __('Listing Commission (%)') }}</label>
            <input type="number" class="form-control form-control-sm" id="calc-listing-pct" step="0.01" min="0" max="100" value="{{ $deal->listing_commission_pct ?? '' }}">
        </div>
        <div class="mb-2">
            <label class="form-label">{{ __('Buyer Commission (%)') }}</label>
            <input type="number" class="form-control form-control-sm" id="calc-buyer-pct" step="0.01" min="0" max="100" value="{{ $deal->buyer_commission_pct ?? '' }}">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Brokerage Split (%)') }}</label>
            <input type="number" class="form-control form-control-sm" id="calc-brokerage-split" step="0.01" min="0" max="100" value="{{ $deal->brokerage_split_pct ?? '' }}">
        </div>

        <hr>

        <div class="mb-2 d-flex justify-content-between">
            <span class="text-secondary">{{ __('Gross Commission:') }}</span>
            <strong id="calc-gross-commission">-</strong>
        </div>
        <div class="mb-2 d-flex justify-content-between">
            <span class="text-secondary">{{ __('Listing Side:') }}</span>
            <span id="calc-listing-side">-</span>
        </div>
        <div class="mb-2 d-flex justify-content-between">
            <span class="text-secondary">{{ __('Buyer Side:') }}</span>
            <span id="calc-buyer-side">-</span>
        </div>
        <div class="mb-2 d-flex justify-content-between">
            <span class="text-secondary">{{ __('Brokerage Cut:') }}</span>
            <span class="text-red" id="calc-brokerage-cut">-</span>
        </div>
        <div class="mb-3 d-flex justify-content-between">
            <span class="fw-bold">{{ __('Net to Agent:') }}</span>
            <strong class="text-green fs-4" id="calc-net-agent">-</strong>
        </div>

        <button type="button" class="btn btn-primary w-100 btn-sm" id="calc-save-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><circle cx="12" cy="14" r="2"/><polyline points="14 4 14 8 8 8"/></svg>
            {{ __('Save Commission') }}
        </button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calcSalePrice = document.getElementById('calc-sale-price');
    var calcListingPct = document.getElementById('calc-listing-pct');
    var calcBuyerPct = document.getElementById('calc-buyer-pct');
    var calcBrokerageSplit = document.getElementById('calc-brokerage-split');
    var calcGross = document.getElementById('calc-gross-commission');
    var calcListingSide = document.getElementById('calc-listing-side');
    var calcBuyerSide = document.getElementById('calc-buyer-side');
    var calcBrokerageCut = document.getElementById('calc-brokerage-cut');
    var calcNetAgent = document.getElementById('calc-net-agent');
    var calcSaveBtn = document.getElementById('calc-save-btn');

    var jsLocale = '{{ Fmt::jsLocale() }}';
    var currencyCode = '{{ Fmt::currencyCode() }}';

    function formatCurrency(value) {
        try {
            return new Intl.NumberFormat(jsLocale, { style: 'currency', currency: currencyCode, minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
        } catch (e) {
            return '$' + value.toFixed(2);
        }
    }

    function recalcCommission() {
        var salePrice = parseFloat(calcSalePrice.value) || 0;
        var listingPct = parseFloat(calcListingPct.value) || 0;
        var buyerPct = parseFloat(calcBuyerPct.value) || 0;
        var brokerageSplitPct = parseFloat(calcBrokerageSplit.value) || 0;

        var grossCommission = salePrice * (listingPct + buyerPct) / 100;
        var listingSide = salePrice * listingPct / 100;
        var buyerSide = salePrice * buyerPct / 100;
        var brokerageCut = grossCommission * brokerageSplitPct / 100;
        var netToAgent = grossCommission - brokerageCut;

        calcGross.textContent = formatCurrency(grossCommission);
        calcListingSide.textContent = formatCurrency(listingSide);
        calcBuyerSide.textContent = formatCurrency(buyerSide);
        calcBrokerageCut.textContent = formatCurrency(brokerageCut);
        calcNetAgent.textContent = formatCurrency(netToAgent);
    }

    // Listen to all input changes
    calcSalePrice.addEventListener('input', recalcCommission);
    calcListingPct.addEventListener('input', recalcCommission);
    calcBuyerPct.addEventListener('input', recalcCommission);
    calcBrokerageSplit.addEventListener('input', recalcCommission);

    // Calculate on page load
    recalcCommission();

    // Save button
    calcSaveBtn.addEventListener('click', function() {
        var salePrice = parseFloat(calcSalePrice.value) || 0;
        var listingPct = parseFloat(calcListingPct.value) || 0;
        var buyerPct = parseFloat(calcBuyerPct.value) || 0;
        var brokerageSplitPct = parseFloat(calcBrokerageSplit.value) || 0;
        var grossCommission = salePrice * (listingPct + buyerPct) / 100;

        calcSaveBtn.disabled = true;
        calcSaveBtn.textContent = '{{ __("Saving...") }}';

        fetch('{{ url("/pipeline/" . $deal->id) }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                contract_price: salePrice,
                listing_commission_pct: listingPct,
                buyer_commission_pct: buyerPct,
                total_commission: grossCommission.toFixed(2),
                brokerage_split_pct: brokerageSplitPct
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                calcSaveBtn.textContent = '{{ __("Saved!") }}';
                calcSaveBtn.classList.remove('btn-primary');
                calcSaveBtn.classList.add('btn-success');
                setTimeout(function() {
                    calcSaveBtn.textContent = '{{ __("Save Commission") }}';
                    calcSaveBtn.classList.remove('btn-success');
                    calcSaveBtn.classList.add('btn-primary');
                    calcSaveBtn.disabled = false;
                }, 2000);
            } else {
                calcSaveBtn.textContent = '{{ __("Failed") }}';
                calcSaveBtn.disabled = false;
                setTimeout(function() {
                    calcSaveBtn.textContent = '{{ __("Save Commission") }}';
                }, 2000);
            }
        })
        .catch(function() {
            calcSaveBtn.textContent = '{{ __("Failed") }}';
            calcSaveBtn.disabled = false;
            setTimeout(function() {
                calcSaveBtn.textContent = '{{ __("Save Commission") }}';
            }, 2000);
        });
    });
});
</script>
@endpush
