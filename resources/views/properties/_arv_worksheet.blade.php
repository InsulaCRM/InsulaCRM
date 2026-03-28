{{-- Comps / ARV Worksheet Card --}}
{{-- Include: @include('properties._arv_worksheet', ['property' => $property]) --}}

@php
    $comps = \App\Models\ComparableSale::where('property_id', $property->id)->orderBy('sale_date', 'desc')->get();
    $adjustedPrices = $comps->pluck('adjusted_price')->filter();
    $compCount = $adjustedPrices->count();
    $avgArv = $compCount > 0 ? $adjustedPrices->avg() : 0;
    $medianArv = 0;
    if ($compCount > 0) {
        $sorted = $adjustedPrices->sort()->values();
        if ($compCount % 2 === 0) {
            $medianArv = ($sorted[$compCount / 2 - 1] + $sorted[$compCount / 2]) / 2;
        } else {
            $medianArv = $sorted[intdiv($compCount, 2)];
        }
    }
    $repairEstimate = (float) ($property->repair_estimate ?? 0);
    $mao70 = $compCount > 0 ? ($avgArv * 0.70) - $repairEstimate : 0;
    $mao75 = $compCount > 0 ? ($avgArv * 0.75) - $repairEstimate : 0;
    $askingPrice = (float) ($property->asking_price ?? 0);
    $isGoodDeal = $askingPrice > 0 && $mao70 > 0 && $askingPrice < $mao70;
@endphp

{{-- ARV Summary Card --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M10 13l-1 2l1 2"/><path d="M14 13l1 2l-1 2"/></svg>
            {{ __('ARV Worksheet') }}
        </h3>
        <div class="card-actions">
            <span class="badge bg-blue-lt">{{ $compCount }} {{ trans_choice('comp|comps', $compCount) }}</span>
            @if(auth()->user()->tenant->ai_enabled)
            <button type="button" class="btn btn-outline-purple btn-sm" id="arv-ai-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                {{ __('AI ARV Analysis') }}
            </button>
            @endif
        </div>
    </div>
    <div class="card-body" id="arv-summary-section">
        @if($compCount > 0)
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary small">{{ __('Estimated ARV') }}</div>
                        <div class="fw-bold fs-3">{{ Fmt::currency($avgArv, 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary small">{{ __('Median ARV') }}</div>
                        <div class="fw-bold fs-3">{{ Fmt::currency($medianArv, 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary small">{{ __('MAO (70% Rule)') }}</div>
                        <div class="fw-bold fs-3 {{ $mao70 > 0 ? 'text-green' : 'text-red' }}">{{ Fmt::currency($mao70, 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary small">{{ __('MAO (75% Rule)') }}</div>
                        <div class="fw-bold fs-3">{{ Fmt::currency($mao75, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <span class="text-secondary">{{ __('Repair Estimate:') }}</span>
                <strong>{{ Fmt::currency($repairEstimate) }}</strong>
            </div>
            <div class="col-md-6">
                @if($askingPrice > 0)
                    <span class="text-secondary">{{ __('Asking Price:') }}</span>
                    <strong>{{ Fmt::currency($askingPrice) }}</strong>
                    @if($isGoodDeal)
                        <span class="badge bg-green-lt ms-2">{{ __('Good Deal') }}</span>
                    @elseif($askingPrice > $mao75)
                        <span class="badge bg-red-lt ms-2">{{ __('Above MAO') }}</span>
                    @endif
                @endif
            </div>
        </div>

        {{-- ARV Chart --}}
        <div id="arv-chart" style="height:250px;" class="mb-3" role="img" aria-label="{{ __('Bar chart showing comparable sale adjusted prices with average ARV line') }}"></div>
        @else
        <div class="text-center text-secondary py-3">
            <p>{{ __('No comparable sales added yet. Add comps below to calculate ARV.') }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Comparable Sales Table --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Comparable Sales') }}</h3>
    </div>
    @if($comps->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr>
                    <th>{{ __('Address') }}</th>
                    <th>{{ __('Sale Price') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Sqft') }}</th>
                    <th>{{ __('Bed/Bath') }}</th>
                    <th>{{ __('Distance') }}</th>
                    <th>{{ __('Condition') }}</th>
                    <th>{{ __('Adjustments') }}</th>
                    <th>{{ __('Adj. Price') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($comps as $comp)
                <tr>
                    <td>{{ $comp->address }}</td>
                    <td>{{ Fmt::currency($comp->sale_price) }}</td>
                    <td>{{ $comp->sale_date->format('M d, Y') }}</td>
                    <td>{{ $comp->sqft ? number_format($comp->sqft) : '-' }}</td>
                    <td>{{ $comp->beds ?? '-' }}/{{ $comp->baths ?? '-' }}</td>
                    <td>{{ $comp->distance_miles ? $comp->distance_miles . ' mi' : '-' }}</td>
                    <td>
                        @if($comp->condition)
                            <span class="badge bg-azure-lt">{{ __(ucfirst($comp->condition)) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($comp->adjustments && count($comp->adjustments) > 0)
                            @php $adjTotal = array_sum(array_values($comp->adjustments)); @endphp
                            <span class="badge {{ $adjTotal >= 0 ? 'bg-green-lt' : 'bg-red-lt' }}" data-bs-toggle="tooltip" data-bs-html="true" title="@foreach($comp->adjustments as $reason => $amount){{ $reason }}: {{ Fmt::currency($amount) }}<br>@endforeach">
                                {{ $adjTotal >= 0 ? '+' : '' }}{{ Fmt::currency($adjTotal, 0) }}
                            </span>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td class="fw-bold">{{ Fmt::currency($comp->adjusted_price) }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-ghost-primary btn-sm btn-edit-comp" data-comp-id="{{ $comp->id }}" data-comp='@json($comp)' title="{{ __('Edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"/><line x1="13.5" y1="6.5" x2="17.5" y2="10.5"/></svg>
                            </button>
                            <form action="{{ url('/comps/' . $comp->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost-danger btn-sm" onclick="return confirm('{{ __('Delete this comp?') }}')" title="{{ __('Delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body text-center text-secondary">
        {{ __('No comparable sales yet.') }}
    </div>
    @endif
</div>

{{-- Add Comparable Sale Form --}}
<div class="card mb-3" id="add-comp-card">
    <div class="card-header">
        <h3 class="card-title" id="comp-form-title">{{ __('Add Comparable Sale') }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ url('/properties/' . $property->id . '/comps') }}" method="POST" id="comp-form">
            @csrf
            <input type="hidden" name="_method" value="POST" id="comp-method">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Address') }}</label>
                    <input type="text" name="address" class="form-control" required id="comp-address">
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('Sale Price') }}</label>
                    <input type="number" name="sale_price" class="form-control" step="0.01" min="0" required id="comp-sale-price">
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('Sale Date') }}</label>
                    <input type="date" name="sale_date" class="form-control" required id="comp-sale-date">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label class="form-label">{{ __('Sqft') }}</label>
                    <input type="number" name="sqft" class="form-control" min="0" id="comp-sqft">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Beds') }}</label>
                    <input type="number" name="beds" class="form-control" min="0" id="comp-beds">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Baths') }}</label>
                    <input type="number" name="baths" class="form-control" min="0" step="0.5" id="comp-baths">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Year Built') }}</label>
                    <input type="number" name="year_built" class="form-control" min="1800" max="{{ date('Y') + 1 }}" id="comp-year-built">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Distance (mi)') }}</label>
                    <input type="number" name="distance_miles" class="form-control" step="0.01" min="0" id="comp-distance">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Condition') }}</label>
                    <select name="condition" class="form-select" id="comp-condition">
                        <option value="">-</option>
                        <option value="excellent">{{ __('Excellent') }}</option>
                        <option value="good">{{ __('Good') }}</option>
                        <option value="average">{{ __('Average') }}</option>
                        <option value="fair">{{ __('Fair') }}</option>
                        <option value="poor">{{ __('Poor') }}</option>
                    </select>
                </div>
            </div>

            {{-- Adjustments Section --}}
            <div class="mb-3">
                <label class="form-label">{{ __('Adjustments') }}</label>
                <div id="adjustments-container">
                    {{-- Dynamic rows added by JS --}}
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="add-adjustment-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Add Adjustment') }}
                </button>
            </div>

            {{-- Calculated Adjusted Price --}}
            <div class="mb-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary">{{ __('Adjusted Price:') }}</span>
                    <span class="fw-bold fs-3" id="calculated-adjusted-price">{{ Fmt::currency(0) }}</span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Notes') }}</label>
                <input type="text" name="notes" class="form-control" id="comp-notes" placeholder="{{ __('Optional notes about this comp') }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="comp-submit-btn">{{ __('Add Comparable') }}</button>
                <button type="button" class="btn btn-ghost-secondary" id="comp-cancel-btn" style="display:none;">{{ __('Cancel Edit') }}</button>
            </div>
        </form>
    </div>
</div>

@if(auth()->user()->tenant->ai_enabled)
{{-- AI ARV Analysis Modal --}}
<div class="modal modal-blur fade" id="arv-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('AI ARV Analysis') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="arv-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="arv-ai-result" style="display: none;">
                    <div style="line-height: 1.6;" id="arv-ai-text"></div>
                </div>
                <div id="arv-ai-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <div id="arv-ai-apply-section" style="display:none;" class="me-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">{{ __('ARV') }}</span>
                        <input type="number" class="form-control" id="arv-ai-apply-value" step="0.01" min="0" style="max-width:140px;">
                        <button type="button" class="btn btn-success" id="arv-ai-apply-btn">{{ __('Apply ARV') }}</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="arv-ai-copy-btn" style="display: none;">{{ __('Copy to Clipboard') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var propertyId = {{ $property->id }};
    var currencySymbol = '{{ Fmt::currencySymbol() }}';
    var jsLocale = '{{ Fmt::jsLocale() }}';

    // Initialize tooltips
    var tooltipList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipList.map(function(el) { return new bootstrap.Tooltip(el); });

    // ── Adjustments dynamic rows ──
    var adjContainer = document.getElementById('adjustments-container');
    var adjIdx = 0;

    function addAdjustmentRow(reason, amount) {
        reason = reason || '';
        amount = amount || '';
        var row = document.createElement('div');
        row.className = 'row g-2 mb-2 adjustment-row';
        row.innerHTML = '<div class="col-md-6"><input type="text" class="form-control form-control-sm adj-reason" placeholder="{{ __("Reason (e.g. Size Adjustment)") }}" value="' + reason.replace(/"/g, '&quot;') + '"></div>' +
            '<div class="col-md-4"><input type="number" class="form-control form-control-sm adj-amount" placeholder="{{ __("Amount") }}" step="0.01" value="' + amount + '"></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-ghost-danger btn-sm btn-remove-adj"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>';
        adjContainer.appendChild(row);
        row.querySelector('.btn-remove-adj').addEventListener('click', function() {
            row.remove();
            recalcAdjustedPrice();
        });
        row.querySelector('.adj-amount').addEventListener('input', recalcAdjustedPrice);
        recalcAdjustedPrice();
    }

    document.getElementById('add-adjustment-btn').addEventListener('click', function() {
        addAdjustmentRow('', '');
    });

    function recalcAdjustedPrice() {
        var salePrice = parseFloat(document.getElementById('comp-sale-price').value) || 0;
        var total = 0;
        document.querySelectorAll('.adj-amount').forEach(function(input) {
            total += parseFloat(input.value) || 0;
        });
        var adjusted = salePrice + total;
        document.getElementById('calculated-adjusted-price').textContent = currencySymbol + adjusted.toLocaleString(jsLocale, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    document.getElementById('comp-sale-price').addEventListener('input', recalcAdjustedPrice);

    // ── Collect adjustments into hidden fields before submit ──
    document.getElementById('comp-form').addEventListener('submit', function(e) {
        // Remove any old hidden adjustment fields
        this.querySelectorAll('input[name^="adjustments"]').forEach(function(el) { el.remove(); });

        document.querySelectorAll('.adjustment-row').forEach(function(row) {
            var reason = row.querySelector('.adj-reason').value.trim();
            var amount = row.querySelector('.adj-amount').value;
            if (reason && amount) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'adjustments[' + reason + ']';
                input.value = amount;
                e.target.appendChild(input);
            }
        });
    });

    // ── Edit Comp ──
    var originalAction = document.getElementById('comp-form').action;
    document.querySelectorAll('.btn-edit-comp').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var comp = JSON.parse(this.dataset.comp);
            var form = document.getElementById('comp-form');

            form.action = '{{ url("/comps") }}/' + comp.id;
            document.getElementById('comp-method').value = 'PUT';
            document.getElementById('comp-form-title').textContent = '{{ __("Edit Comparable Sale") }}';
            document.getElementById('comp-submit-btn').textContent = '{{ __("Update Comparable") }}';
            document.getElementById('comp-cancel-btn').style.display = 'inline-block';

            document.getElementById('comp-address').value = comp.address || '';
            document.getElementById('comp-sale-price').value = comp.sale_price || '';
            document.getElementById('comp-sale-date').value = comp.sale_date ? comp.sale_date.substring(0, 10) : '';
            document.getElementById('comp-sqft').value = comp.sqft || '';
            document.getElementById('comp-beds').value = comp.beds || '';
            document.getElementById('comp-baths').value = comp.baths || '';
            document.getElementById('comp-year-built').value = comp.year_built || '';
            document.getElementById('comp-distance').value = comp.distance_miles || '';
            document.getElementById('comp-condition').value = comp.condition || '';
            document.getElementById('comp-notes').value = comp.notes || '';

            // Clear and repopulate adjustments
            adjContainer.innerHTML = '';
            if (comp.adjustments && typeof comp.adjustments === 'object') {
                Object.keys(comp.adjustments).forEach(function(reason) {
                    addAdjustmentRow(reason, comp.adjustments[reason]);
                });
            }

            recalcAdjustedPrice();
            document.getElementById('add-comp-card').scrollIntoView({ behavior: 'smooth' });
        });
    });

    document.getElementById('comp-cancel-btn').addEventListener('click', function() {
        var form = document.getElementById('comp-form');
        form.action = originalAction;
        document.getElementById('comp-method').value = 'POST';
        document.getElementById('comp-form-title').textContent = '{{ __("Add Comparable Sale") }}';
        document.getElementById('comp-submit-btn').textContent = '{{ __("Add Comparable") }}';
        this.style.display = 'none';
        form.reset();
        adjContainer.innerHTML = '';
        recalcAdjustedPrice();
    });

    // ── AI ARV Analysis ──
    @if(auth()->user()->tenant->ai_enabled)
    (function() {
        var arvAiModalEl = document.getElementById('arv-ai-modal');
        var arvAiModal = new bootstrap.Modal(arvAiModalEl);
        var lastArvAiText = '';

        arvAiModalEl.addEventListener('hide.bs.modal', function() {
            if (arvAiModalEl.contains(document.activeElement)) document.activeElement.blur();
        });

        document.getElementById('arv-ai-btn').addEventListener('click', function() {
            document.getElementById('arv-ai-loading').style.display = 'block';
            document.getElementById('arv-ai-result').style.display = 'none';
            document.getElementById('arv-ai-error').style.display = 'none';
            document.getElementById('arv-ai-copy-btn').style.display = 'none';
            arvAiModal.show();

            fetch('{{ url("/ai/arv-analysis") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ property_id: propertyId })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.error) {
                    document.getElementById('arv-ai-loading').style.display = 'none';
                    document.getElementById('arv-ai-error').style.display = 'block';
                    document.getElementById('arv-ai-error').textContent = res.error;
                    return;
                }
                lastArvAiText = res.analysis || '';
                document.getElementById('arv-ai-loading').style.display = 'none';
                document.getElementById('arv-ai-result').style.display = 'block';
                document.getElementById('arv-ai-text').innerHTML = window.renderAiMarkdown(res.analysis);
                document.getElementById('arv-ai-copy-btn').style.display = 'inline-block';
                document.getElementById('arv-ai-apply-section').style.display = 'flex';
                document.getElementById('arv-ai-apply-value').value = {{ $avgArv > 0 ? round($avgArv) : 0 }};
            })
            .catch(function() {
                document.getElementById('arv-ai-loading').style.display = 'none';
                document.getElementById('arv-ai-error').style.display = 'block';
                document.getElementById('arv-ai-error').textContent = '{{ __("Request failed. Please try again.") }}';
            });
        });

        document.getElementById('arv-ai-copy-btn').addEventListener('click', function() {
            var btn = this;
            navigator.clipboard.writeText(lastArvAiText).then(function() {
                btn.textContent = '{{ __("Copied!") }}';
                setTimeout(function() { btn.textContent = '{{ __("Copy to Clipboard") }}'; }, 2000);
            });
        });

        document.getElementById('arv-ai-apply-btn').addEventListener('click', function() {
            var btn = this;
            var arvValue = document.getElementById('arv-ai-apply-value').value;
            if (!arvValue || parseFloat(arvValue) <= 0) { alert('{{ __("Enter a valid ARV value.") }}'); return; }
            btn.disabled = true;
            btn.textContent = '{{ __("Applying...") }}';
            fetch('{{ url("/ai/apply-property-field") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ property_id: propertyId, field: 'after_repair_value', value: arvValue })
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) {
                    btn.textContent = '{{ __("Applied!") }}';
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
    })();
    @endif

    // ── ARV Bar Chart ──
    @if($compCount > 0)
    var compData = @json($comps->map(fn($c) => ['address' => \Illuminate\Support\Str::limit($c->address, 20), 'adjusted_price' => (float) $c->adjusted_price])->values());
    var avgArvValue = {{ $avgArv }};

    if (compData.length > 0) {
        new ApexCharts(document.getElementById('arv-chart'), {
            chart: {
                type: 'bar',
                height: 250,
                fontFamily: 'inherit',
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 600 },
            },
            series: [{
                name: '{{ __("Adjusted Price") }}',
                data: compData.map(function(c) { return c.adjusted_price; })
            }],
            colors: ['#206bc4'],
            plotOptions: {
                bar: { borderRadius: 4, columnWidth: '60%' }
            },
            xaxis: {
                categories: compData.map(function(c) { return c.address; }),
                labels: { style: { colors: '#667382', fontSize: '11px' }, rotate: -30 }
            },
            yaxis: {
                labels: {
                    style: { colors: '#667382', fontSize: '12px' },
                    formatter: function(val) { return currencySymbol + val.toLocaleString(jsLocale, { maximumFractionDigits: 0 }); }
                }
            },
            grid: { strokeDashArray: 4, borderColor: '#e6e8eb' },
            dataLabels: { enabled: false },
            annotations: {
                yaxis: [{
                    y: avgArvValue,
                    borderColor: '#2fb344',
                    label: {
                        borderColor: '#2fb344',
                        style: { color: '#fff', background: '#2fb344', fontSize: '11px' },
                        text: '{{ __("Avg ARV") }}: ' + currencySymbol + avgArvValue.toLocaleString(jsLocale, { maximumFractionDigits: 0 })
                    }
                }]
            },
            tooltip: {
                y: { formatter: function(val) { return currencySymbol + val.toLocaleString(jsLocale); } }
            }
        }).render();
    }
    @endif
});
</script>
@endpush
