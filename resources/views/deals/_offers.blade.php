@php
    $offers = $deal->offers ?? collect();
    $offerCount = $offers->count();
    $bestOfferPrice = $offers->max('offer_price');
@endphp

<div class="card mb-3" id="offers-card">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-cash me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            {{ __('Offers') }}
            @if($offerCount > 0)
            <span class="badge bg-blue-lt ms-2">{{ $offerCount }}</span>
            @endif
        </h3>
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-primary" id="offers-toggle-form-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Offer') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Add Offer Form (Collapsible) -->
        <div id="offers-form-wrapper" style="display: none;" class="mb-3">
            <form method="POST" action="{{ route('deals.storeOffer', $deal) }}">
                @csrf
                <div class="card bg-light border">
                    <div class="card-body">
                        <h4 class="mb-3">{{ __('New Offer') }}</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('Buyer Name') }}</label>
                                <input type="text" name="buyer_name" class="form-control form-control-sm" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Buyer Agent Name') }}</label>
                                <input type="text" name="buyer_agent_name" class="form-control form-control-sm" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Buyer Agent Phone') }}</label>
                                <input type="text" name="buyer_agent_phone" class="form-control form-control-sm" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Buyer Agent Email') }}</label>
                                <input type="email" name="buyer_agent_email" class="form-control form-control-sm" maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">{{ __('Offer Price') }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="offer_price" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Earnest Money') }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="earnest_money" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Financing Type') }}</label>
                                <select name="financing_type" class="form-select form-select-sm">
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach(\App\Models\DealOffer::FINANCING_TYPES as $ftKey => $ftLabel)
                                        <option value="{{ $ftKey }}">{{ __($ftLabel) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('Contingencies') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check form-check-inline">
                                        <input type="checkbox" name="contingencies[]" value="inspection" class="form-check-input">
                                        <span class="form-check-label">{{ __('Inspection') }}</span>
                                    </label>
                                    <label class="form-check form-check-inline">
                                        <input type="checkbox" name="contingencies[]" value="appraisal" class="form-check-input">
                                        <span class="form-check-label">{{ __('Appraisal') }}</span>
                                    </label>
                                    <label class="form-check form-check-inline">
                                        <input type="checkbox" name="contingencies[]" value="financing" class="form-check-input">
                                        <span class="form-check-label">{{ __('Financing') }}</span>
                                    </label>
                                    <label class="form-check form-check-inline">
                                        <input type="checkbox" name="contingencies[]" value="sale_of_home" class="form-check-input">
                                        <span class="form-check-label">{{ __('Sale of Home') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Expiration Date') }}</label>
                                <input type="datetime-local" name="expiration_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Submit Offer') }}</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="offers-cancel-form-btn">{{ __('Cancel') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if($offerCount === 0)
            <p class="text-secondary text-center py-2">{{ __('No offers received yet.') }}</p>
        @else
            @foreach($offers->sortByDesc('offer_price') as $offer)
            @php
                $statusColors = [
                    'pending' => 'bg-blue-lt',
                    'countered' => 'bg-yellow-lt',
                    'accepted' => 'bg-green-lt',
                    'rejected' => 'bg-red-lt',
                    'withdrawn' => 'bg-secondary-lt',
                    'expired' => 'bg-secondary-lt',
                ];
                $statusBadge = $statusColors[$offer->status] ?? 'bg-secondary-lt';
                $isBestOffer = (float) $offer->offer_price === (float) $bestOfferPrice && $offer->status === 'pending';
                $isExpired = $offer->is_expired;
            @endphp
            <div class="offer-row border rounded p-3 mb-2 {{ $offer->status === 'accepted' ? 'border-success' : '' }}" data-offer-id="{{ $offer->id }}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold">{{ $offer->buyer_name }}</span>
                            @if($isBestOffer)
                                <span class="badge bg-green-lt">{{ __('Best Offer') }}</span>
                            @endif
                            <span class="badge {{ $statusBadge }}">{{ __(App\Models\DealOffer::STATUSES[$offer->status] ?? ucfirst($offer->status)) }}</span>
                            @if($isExpired)
                                <span class="badge bg-red-lt">{{ __('Expired') }}</span>
                            @endif
                        </div>
                        @if($offer->buyer_agent_name)
                            <div class="text-secondary small mt-1">
                                {{ __('Agent:') }} {{ $offer->buyer_agent_name }}
                                @if($offer->buyer_agent_phone) &middot; {{ $offer->buyer_agent_phone }} @endif
                                @if($offer->buyer_agent_email) &middot; {{ $offer->buyer_agent_email }} @endif
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="fs-3 fw-bold text-primary">{{ Fmt::currency($offer->offer_price) }}</div>
                        @if($offer->counter_price)
                            <div class="text-secondary small">{{ __('Counter:') }} {{ Fmt::currency($offer->counter_price) }}</div>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-2">
                    @if($offer->financing_type)
                        <span class="badge bg-cyan-lt">{{ __(App\Models\DealOffer::FINANCING_TYPES[$offer->financing_type] ?? ucfirst($offer->financing_type)) }}</span>
                    @endif
                    @if($offer->earnest_money)
                        <span class="badge bg-secondary-lt">{{ __('EMD:') }} {{ Fmt::currency($offer->earnest_money) }}</span>
                    @endif
                    @if($offer->contingencies && is_array($offer->contingencies))
                        @foreach($offer->contingencies as $contingency)
                            <span class="badge bg-orange-lt">{{ __(ucwords(str_replace('_', ' ', $contingency))) }}</span>
                        @endforeach
                    @endif
                    @if($offer->expiration_date)
                        @php
                            $expirationPast = $offer->expiration_date->isPast();
                        @endphp
                        <span class="badge {{ $expirationPast ? 'bg-red-lt' : 'bg-yellow-lt' }}">
                            {{ __('Expires:') }} {{ $offer->expiration_date->format('M d, g:i A') }}
                            @if(!$expirationPast)
                                ({{ $offer->expiration_date->diffForHumans() }})
                            @endif
                        </span>
                    @endif
                </div>

                @if($offer->notes)
                    <div class="text-secondary small mb-2">{{ $offer->notes }}</div>
                @endif

                <!-- Action Buttons -->
                @if($offer->status === 'pending' || $offer->status === 'countered')
                <div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-success offer-action-btn" data-offer-id="{{ $offer->id }}" data-action="accepted">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                        {{ __('Accept') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger offer-action-btn" data-offer-id="{{ $offer->id }}" data-action="rejected">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        {{ __('Reject') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning offer-counter-toggle" data-offer-id="{{ $offer->id }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="7 8 3 12 7 16"/><polyline points="17 8 21 12 17 16"/><line x1="3" y1="12" x2="21" y2="12"/></svg>
                        {{ __('Counter') }}
                    </button>
                    <div class="offer-counter-input d-none align-items-center gap-2" id="offer-counter-{{ $offer->id }}">
                        <div class="input-group input-group-sm" style="width: 160px;">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control offer-counter-price" data-offer-id="{{ $offer->id }}" step="0.01" min="0" placeholder="{{ __('Counter price') }}">
                        </div>
                        <button type="button" class="btn btn-sm btn-warning offer-counter-submit" data-offer-id="{{ $offer->id }}">{{ __('Send') }}</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-ghost-danger ms-auto offer-delete-btn" data-offer-id="{{ $offer->id }}" title="{{ __('Delete') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                    </button>
                </div>
                @else
                <div class="d-flex gap-2 mt-2 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-ghost-danger ms-auto offer-delete-btn" data-offer-id="{{ $offer->id }}" title="{{ __('Delete') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                    </button>
                </div>
                @endif
            </div>
            @endforeach
        @endif
    </div>
</div>

@push('scripts')
<script>
(function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };

    // Toggle add offer form
    var toggleBtn = document.getElementById('offers-toggle-form-btn');
    var formWrapper = document.getElementById('offers-form-wrapper');
    var cancelBtn = document.getElementById('offers-cancel-form-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            formWrapper.style.display = formWrapper.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            formWrapper.style.display = 'none';
        });
    }

    // Accept / Reject buttons
    document.querySelectorAll('.offer-action-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var offerId = this.getAttribute('data-offer-id');
            var action = this.getAttribute('data-action');
            var actionBtn = this;
            actionBtn.disabled = true;

            fetch('{{ url("/offers") }}/' + offerId, {
                method: 'PATCH',
                headers: headers,
                body: JSON.stringify({ status: action })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
                else { actionBtn.disabled = false; }
            }).catch(function() { actionBtn.disabled = false; });
        });
    });

    // Counter toggle
    document.querySelectorAll('.offer-counter-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var offerId = this.getAttribute('data-offer-id');
            var counterEl = document.getElementById('offer-counter-' + offerId);
            counterEl.classList.toggle('d-none');
            counterEl.classList.toggle('d-flex');
        });
    });

    // Counter submit
    document.querySelectorAll('.offer-counter-submit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var offerId = this.getAttribute('data-offer-id');
            var priceInput = document.querySelector('.offer-counter-price[data-offer-id="' + offerId + '"]');
            var counterPrice = priceInput.value;
            if (!counterPrice || parseFloat(counterPrice) <= 0) return;

            btn.disabled = true;
            fetch('{{ url("/offers") }}/' + offerId, {
                method: 'PATCH',
                headers: headers,
                body: JSON.stringify({ status: 'countered', counter_price: counterPrice })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
                else { btn.disabled = false; }
            }).catch(function() { btn.disabled = false; });
        });
    });

    // Delete offer
    document.querySelectorAll('.offer-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('{{ __('Delete this offer?') }}')) return;
            var offerId = this.getAttribute('data-offer-id');
            fetch('{{ url("/offers") }}/' + offerId, {
                method: 'DELETE',
                headers: headers
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
            });
        });
    });
})();
</script>
@endpush
