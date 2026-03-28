{{-- Buyer POF & Verification + Score Card --}}
{{-- Include: @include('buyers._verification_card', ['buyer' => $buyer]) --}}

@php
    $buyerScore = $buyer->buyer_score ?? 0;
    $transactions = $buyer->transactions ?? collect();

    if ($buyerScore >= 61) {
        $scoreColor = '#2fb344';
        $scoreBg = 'bg-green-lt';
        $scoreLabel = __('Excellent');
    } elseif ($buyerScore >= 31) {
        $scoreColor = '#f59f00';
        $scoreBg = 'bg-yellow-lt';
        $scoreLabel = __('Moderate');
    } else {
        $scoreColor = '#d63939';
        $scoreBg = 'bg-red-lt';
        $scoreLabel = __('Low');
    }
@endphp

<div class="col-md-6">
{{-- Buyer Score Card --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"/></svg>
            {{ __('Buyer Score') }}
        </h3>
        <div class="card-actions">
            @if(auth()->user()->tenant->ai_enabled)
            <button type="button" class="btn btn-outline-purple btn-sm" id="btn-buyer-risk-ai">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 6a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6z"/></svg>
                {{ __('AI Risk Assessment') }}
            </button>
            @endif
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-recalculate-score">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                {{ __('Recalculate') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                {{-- Circular Score Display --}}
                <div style="position:relative; width:100px; height:100px;">
                    <svg viewBox="0 0 36 36" style="width:100px; height:100px; transform:rotate(-90deg);">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e9ecef" stroke-width="3"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="{{ $scoreColor }}" stroke-width="3" stroke-dasharray="{{ $buyerScore }}, 100" stroke-linecap="round"/>
                    </svg>
                    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
                        <span class="fw-bold fs-2" id="score-display">{{ $buyerScore }}</span>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-2">
                    <span class="badge {{ $scoreBg }}">{{ $scoreLabel }}</span>
                </div>
                {{-- Score Breakdown --}}
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">{{ __('POF Verified') }}</span>
                        <span>{{ $buyer->pof_verified ? '+20' : '0' }} / 20</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">{{ __('Purchase History') }}</span>
                        <span>
                            @php
                                $tp = $buyer->total_purchases ?? 0;
                                $phScore = $tp >= 6 ? 30 : ($tp >= 3 ? 20 : ($tp >= 1 ? 10 : 0));
                            @endphp
                            {{ $phScore }} / 30
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">{{ __('Close Speed') }}</span>
                        <span>
                            @php
                                $acd = $buyer->avg_close_days;
                                $csScore = 0;
                                if ($acd !== null && $tp > 0) {
                                    $csScore = $acd < 14 ? 20 : ($acd < 30 ? 15 : ($acd < 60 ? 10 : 5));
                                }
                            @endphp
                            {{ $csScore }} / 20
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">{{ __('Recency') }}</span>
                        <span>
                            @php
                                $recScore = 0;
                                if ($buyer->last_purchase_at) {
                                    $daysSince = now()->diffInDays($buyer->last_purchase_at);
                                    $recScore = $daysSince < 30 ? 15 : ($daysSince < 90 ? 10 : ($daysSince < 180 ? 5 : 0));
                                }
                            @endphp
                            {{ $recScore }} / 15
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">{{ __('Profile & Prefs') }}</span>
                        <span>
                            @php
                                $profScore = ($buyer->phone && $buyer->email && $buyer->company) ? 5 : 0;
                                $prefScore = (($buyer->preferred_property_types && count($buyer->preferred_property_types) > 0) && ($buyer->preferred_zip_codes && count($buyer->preferred_zip_codes) > 0)) ? 10 : 0;
                            @endphp
                            {{ $profScore + $prefScore }} / 15
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
<div class="col-md-6">
{{-- POF Verification Card --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 14l2 2l4 -4"/></svg>
            {{ __('Proof of Funds') }}
        </h3>
    </div>
    <div class="card-body">
        @if($buyer->pof_verified)
            <div class="d-flex align-items-center mb-3">
                <span class="badge bg-green-lt me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    {{ __('Verified') }}
                </span>
                @if($buyer->pof_verified_at)
                    <span class="text-secondary small">{{ $buyer->pof_verified_at->format('M d, Y') }}</span>
                @endif
            </div>
            @if($buyer->pof_amount)
            <div class="mb-2">
                <span class="text-secondary">{{ __('Amount:') }}</span>
                <strong>{{ Fmt::currency($buyer->pof_amount) }}</strong>
            </div>
            @endif
            @if($buyer->pof_document_path)
            <div class="mb-3">
                <a href="{{ route('buyers.downloadPof', $buyer) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                    {{ __('Download POF') }}
                </a>
            </div>
            @endif
            <form action="{{ url('/buyers/' . $buyer->id . '/remove-pof') }}" method="POST" id="remove-pof-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Remove POF verification?') }}')">
                    {{ __('Remove POF') }}
                </button>
            </form>
        @else
            <div class="mb-3">
                <span class="badge bg-red-lt">{{ __('Not Verified') }}</span>
            </div>
            <form action="{{ url('/buyers/' . $buyer->id . '/upload-pof') }}" method="POST" enctype="multipart/form-data" id="pof-upload-form">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('POF Document') }}</label>
                    <input type="file" name="pof_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                    <div class="form-text">{{ __('PDF or image, max 10MB') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('POF Amount') }}</label>
                    <input type="number" name="pof_amount" class="form-control" step="0.01" min="0" placeholder="{{ __('Available funds') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Upload & Verify') }}</button>
            </form>
        @endif
    </div>
</div>

</div>
<div class="col-12">
{{-- Transaction History Card --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 7v5l3 3"/></svg>
            {{ __('Transaction History') }}
        </h3>
    </div>
    @if($transactions->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr>
                    <th>{{ __('Address') }}</th>
                    <th>{{ __('Price') }}</th>
                    <th>{{ __('Close Date') }}</th>
                    <th>{{ __('Days') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $txn)
                <tr>
                    <td>{{ $txn->property_address }}</td>
                    <td>{{ Fmt::currency($txn->purchase_price) }}</td>
                    <td>{{ $txn->close_date->format('M d, Y') }}</td>
                    <td>{{ $txn->days_to_close ?? '-' }}</td>
                    <td>
                        <form action="{{ url('/buyer-transactions/' . $txn->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger btn-sm" onclick="return confirm('{{ __('Delete this transaction?') }}')" title="{{ __('Delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body">
        <p class="text-secondary text-center">{{ __('No transactions recorded.') }}</p>
    </div>
    @endif

    {{-- Add Transaction Form --}}
    <div class="card-footer">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#add-transaction-form">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Add Transaction') }}
        </button>
        <div class="collapse mt-3" id="add-transaction-form">
            <form action="{{ url('/buyers/' . $buyer->id . '/transactions') }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="property_address" class="form-control form-control-sm" placeholder="{{ __('Property Address') }}" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="purchase_price" class="form-control form-control-sm" placeholder="{{ __('Purchase Price') }}" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="close_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="days_to_close" class="form-control form-control-sm" placeholder="{{ __('Days to Close') }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('Notes (optional)') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Add') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

{{-- AI Risk Assessment Modal --}}
@if(auth()->user()->tenant->ai_enabled)
<div class="modal modal-blur fade" id="buyer-risk-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('AI Risk Assessment') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="buyer-risk-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="buyer-risk-ai-result" style="display: none;">
                    <div style="line-height: 1.6;" id="buyer-risk-ai-text"></div>
                </div>
                <div id="buyer-risk-ai-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="buyer-risk-ai-save-btn" style="display: none;">{{ __('Save to Notes') }}</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="buyer-risk-ai-copy-btn" style="display: none;">{{ __('Copy to Clipboard') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    // AI Risk Assessment
    var buyerRiskBtn = document.getElementById('btn-buyer-risk-ai');
    if (buyerRiskBtn) {
        var buyerRiskModalEl = document.getElementById('buyer-risk-ai-modal');
        var buyerRiskModal = new bootstrap.Modal(buyerRiskModalEl);
        var lastBuyerRiskText = '';

        buyerRiskModalEl.addEventListener('hide.bs.modal', function() {
            if (buyerRiskModalEl.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });

        buyerRiskBtn.addEventListener('click', function() {
            document.getElementById('buyer-risk-ai-loading').style.display = 'block';
            document.getElementById('buyer-risk-ai-result').style.display = 'none';
            document.getElementById('buyer-risk-ai-error').style.display = 'none';
            document.getElementById('buyer-risk-ai-copy-btn').style.display = 'none';
            document.getElementById('buyer-risk-ai-save-btn').style.display = 'none';
            lastBuyerRiskText = '';
            buyerRiskModal.show();

            fetch('{{ url("/ai/buyer-risk") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ buyer_id: {{ $buyer->id }} })
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.error) {
                    document.getElementById('buyer-risk-ai-loading').style.display = 'none';
                    document.getElementById('buyer-risk-ai-error').style.display = 'block';
                    document.getElementById('buyer-risk-ai-error').textContent = res.error;
                    return;
                }
                lastBuyerRiskText = res.assessment || '';
                document.getElementById('buyer-risk-ai-loading').style.display = 'none';
                document.getElementById('buyer-risk-ai-result').style.display = 'block';
                document.getElementById('buyer-risk-ai-text').innerHTML = window.renderAiMarkdown(lastBuyerRiskText);
                document.getElementById('buyer-risk-ai-copy-btn').style.display = 'inline-block';
                document.getElementById('buyer-risk-ai-save-btn').style.display = 'inline-block';
            }).catch(function() {
                document.getElementById('buyer-risk-ai-loading').style.display = 'none';
                document.getElementById('buyer-risk-ai-error').style.display = 'block';
                document.getElementById('buyer-risk-ai-error').textContent = '{{ __("Request failed. Please try again.") }}';
            });
        });

        document.getElementById('buyer-risk-ai-copy-btn').addEventListener('click', function() {
            navigator.clipboard.writeText(lastBuyerRiskText).then(function() {
                var btn = document.getElementById('buyer-risk-ai-copy-btn');
                var orig = btn.textContent;
                btn.textContent = '{{ __("Copied!") }}';
                setTimeout(function() { btn.textContent = orig; }, 2000);
            });
        });

        document.getElementById('buyer-risk-ai-save-btn').addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.textContent = '{{ __("Saving...") }}';
            fetch('{{ url("/ai/apply-buyer-notes") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ buyer_id: {{ $buyer->id }}, notes: lastBuyerRiskText })
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) {
                    btn.textContent = '{{ __("Saved!") }}';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    btn.textContent = '{{ __("Failed") }}';
                    btn.disabled = false;
                }
            }).catch(function() {
                btn.textContent = '{{ __("Failed") }}';
                btn.disabled = false;
            });
        });
    }

    // Recalculate Score
    var recalcBtn = document.getElementById('btn-recalculate-score');
    if (recalcBtn) {
        var recalcOrigLabel = recalcBtn.innerHTML;
        recalcBtn.addEventListener('click', function() {
            var oldScore = parseInt(document.getElementById('score-display').textContent) || 0;
            recalcBtn.disabled = true;
            recalcBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Recalculating...") }}';
            fetch('{{ url("/buyers/" . $buyer->id . "/recalculate-score") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: '{}'
            }).then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function(res) {
                if (res.success) {
                    var newScore = res.buyer_score;
                    if (newScore === oldScore) {
                        recalcBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg> {{ __("Score is up to date") }}';
                        recalcBtn.classList.remove('btn-outline-primary');
                        recalcBtn.classList.add('btn-outline-success');
                        setTimeout(function() {
                            recalcBtn.innerHTML = recalcOrigLabel;
                            recalcBtn.classList.remove('btn-outline-success');
                            recalcBtn.classList.add('btn-outline-primary');
                            recalcBtn.disabled = false;
                        }, 2500);
                    } else {
                        recalcBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg> {{ __("Updated!") }} ' + oldScore + ' → ' + newScore;
                        recalcBtn.classList.remove('btn-outline-primary');
                        recalcBtn.classList.add('btn-outline-success');
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                } else {
                    recalcBtn.innerHTML = '{{ __("Failed — try again") }}';
                    recalcBtn.classList.remove('btn-outline-primary');
                    recalcBtn.classList.add('btn-outline-danger');
                    setTimeout(function() {
                        recalcBtn.innerHTML = recalcOrigLabel;
                        recalcBtn.classList.remove('btn-outline-danger');
                        recalcBtn.classList.add('btn-outline-primary');
                        recalcBtn.disabled = false;
                    }, 2500);
                }
            }).catch(function(err) {
                console.error('Recalculate error:', err);
                recalcBtn.innerHTML = '{{ __("Failed — try again") }}';
                recalcBtn.classList.remove('btn-outline-primary');
                recalcBtn.classList.add('btn-outline-danger');
                setTimeout(function() {
                    recalcBtn.innerHTML = recalcOrigLabel;
                    recalcBtn.classList.remove('btn-outline-danger');
                    recalcBtn.classList.add('btn-outline-primary');
                    recalcBtn.disabled = false;
                }, 2500);
            });
        });
    }
});
</script>
@endpush
