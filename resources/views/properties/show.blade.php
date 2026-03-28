@extends('layouts.app')

@section('title', $property->address)
@section('page-title', $property->full_address)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('properties.index') }}">{{ __('Properties') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $property->address }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Property Details') }}</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Address') }}</div>
                        <div class="datagrid-content">{{ $property->full_address }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Type') }}</div>
                        <div class="datagrid-content">{{ __(ucwords(str_replace('_', ' ', $property->property_type))) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Bedrooms') }}</div>
                        <div class="datagrid-content">{{ $property->bedrooms ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Bathrooms') }}</div>
                        <div class="datagrid-content">{{ $property->bathrooms ?? '-' }}</div>
                    </div>
                    @if($property->square_footage)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Square Footage') }}</div>
                        <div class="datagrid-content">{{ Fmt::area($property->square_footage) }}</div>
                    </div>
                    @endif
                    @if($property->lot_size)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Lot Size') }}</div>
                        <div class="datagrid-content">{{ number_format($property->lot_size, 2) }} {{ __('acres') }}</div>
                    </div>
                    @endif
                    @if($property->year_built)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Year Built') }}</div>
                        <div class="datagrid-content">{{ $property->year_built }}</div>
                    </div>
                    @endif
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Contact') : __('Lead') }}</div>
                        <div class="datagrid-content">
                            <a href="{{ route('leads.show', $property->lead_id) }}">{{ $property->lead->full_name }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Financial Summary') }}</h3>
            </div>
            <div class="card-body">
                @if($businessMode === 'wholesale')
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Estimated Value:') }}</span>
                    <strong>{{ Fmt::currency($property->estimated_value) }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Repair Estimate:') }}</span>
                    <strong>{{ Fmt::currency($property->repair_estimate) }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('After Repair Value:') }}</span>
                    <strong>{{ Fmt::currency($property->after_repair_value) }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Asking Price:') }}</span>
                    <strong>{{ Fmt::currency($property->asking_price) }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Our Offer:') }}</span>
                    <strong>{{ Fmt::currency($property->our_offer) }}</strong>
                </div>
                <hr>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Assignment Fee:') }}</span>
                    @if($property->assignment_fee !== null)
                        <strong class="{{ $property->assignment_fee >= 0 ? 'text-green' : 'text-red' }}">
                            {{ Fmt::currency($property->assignment_fee) }}
                        </strong>
                    @else
                        <strong>-</strong>
                    @endif
                </div>
                @else
                <div class="mb-2">
                    <span class="text-secondary">{{ __('List Price:') }}</span>
                    <strong>{{ Fmt::currency($property->list_price) }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Listing Status:') }}</span>
                    @if($property->listing_status)
                        @php $listingStatusColors = ['active' => 'bg-green-lt', 'pending' => 'bg-yellow-lt', 'sold' => 'bg-purple-lt', 'withdrawn' => 'bg-red-lt', 'expired' => 'bg-secondary-lt']; @endphp
                        <span class="badge {{ $listingStatusColors[$property->listing_status] ?? 'bg-blue-lt' }}">{{ __(ucfirst($property->listing_status)) }}</span>
                    @else
                        <strong>-</strong>
                    @endif
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Listed At:') }}</span>
                    <strong>{{ $property->listed_at ? $property->listed_at->format('M d, Y') : '-' }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Sold At:') }}</span>
                    <strong>{{ $property->sold_at ? $property->sold_at->format('M d, Y') : '-' }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Sold Price:') }}</span>
                    <strong>{{ $property->sold_price ? Fmt::currency($property->sold_price) : '-' }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">{{ __('Estimated Value:') }}</span>
                    <strong>{{ Fmt::currency($property->estimated_value) }}</strong>
                </div>
                @endif
                @if(auth()->user()->tenant->ai_enabled)
                <hr>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-offer-strategy-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __($businessMode === 'realestate' ? 'AI Pricing Strategy' : 'AI Offer Strategy') }}
                    </button>
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-property-desc-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('AI Property Description') }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ARV / CMA Worksheet --}}
@if($businessMode === 'wholesale')
@include('properties._arv_worksheet', ['property' => $property])
@else
@include('properties._cma_worksheet', ['property' => $property])
@endif

@if(auth()->user()->tenant->ai_enabled)
<div class="modal modal-blur fade" id="prop-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="prop-ai-title">{{ __('AI Assistant') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="prop-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="prop-ai-result" style="display:none;">
                    <div style="line-height:1.6;" id="prop-ai-text"></div>
                </div>
                <div id="prop-ai-error" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="prop-ai-save-notes" style="display:none;">{{ __('Save to Notes') }}</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="prop-ai-copy" style="display:none;">{{ __('Copy to Clipboard') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
@if(auth()->user()->tenant->ai_enabled)
<script>
document.addEventListener('DOMContentLoaded', function() {
    var propId = {{ $property->id }};
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var propAiModalEl = document.getElementById('prop-ai-modal');
    var modal = new bootstrap.Modal(propAiModalEl);
    propAiModalEl.addEventListener('hide.bs.modal', function() {
        if (propAiModalEl.contains(document.activeElement)) document.activeElement.blur();
    });
    var lastText = '';
    var lastPropAction = '';

    function propAiRequest(url, data, title) {
        document.getElementById('prop-ai-title').textContent = title;
        document.getElementById('prop-ai-loading').style.display = 'block';
        document.getElementById('prop-ai-result').style.display = 'none';
        document.getElementById('prop-ai-error').style.display = 'none';
        document.getElementById('prop-ai-copy').style.display = 'none';
        document.getElementById('prop-ai-save-notes').style.display = 'none';
        modal.show();
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json()).then(res => {
            document.getElementById('prop-ai-loading').style.display = 'none';
            if (res.error) {
                document.getElementById('prop-ai-error').style.display = 'block';
                document.getElementById('prop-ai-error').textContent = res.error;
                return;
            }
            lastText = res.strategy || res.description || '';
            document.getElementById('prop-ai-result').style.display = 'block';
            document.getElementById('prop-ai-text').innerHTML = window.renderAiMarkdown(lastText);
            document.getElementById('prop-ai-copy').style.display = 'inline-block';
            document.getElementById('prop-ai-save-notes').style.display = 'inline-block';
        }).catch(() => {
            document.getElementById('prop-ai-loading').style.display = 'none';
            document.getElementById('prop-ai-error').style.display = 'block';
            document.getElementById('prop-ai-error').textContent = '{{ __('Request failed.') }}';
        });
    }

    document.getElementById('ai-offer-strategy-btn').addEventListener('click', function() {
        lastPropAction = 'strategy';
        propAiRequest('{{ url("/ai/offer-strategy") }}', { property_id: propId }, '{{ __('AI Offer Strategy') }}');
    });
    document.getElementById('ai-property-desc-btn').addEventListener('click', function() {
        lastPropAction = 'description';
        propAiRequest('{{ url("/ai/property-description") }}', { property_id: propId }, '{{ __('AI Property Description') }}');
    });
    document.getElementById('prop-ai-copy').addEventListener('click', function() {
        navigator.clipboard.writeText(lastText).then(() => {
            this.textContent = '{{ __('Copied!') }}';
            setTimeout(() => { this.textContent = '{{ __('Copy to Clipboard') }}'; }, 2000);
        });
    });
    document.getElementById('prop-ai-save-notes').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.textContent = '{{ __('Saving...') }}';
        fetch('{{ url("/ai/apply-property-field") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ property_id: propId, field: 'notes', value: lastText, append: true })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.success) {
                btn.textContent = '{{ __('Saved!') }}';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                btn.textContent = '{{ __('Failed') }}';
                btn.disabled = false;
            }
        }).catch(function() {
            btn.textContent = '{{ __('Failed') }}';
            btn.disabled = false;
        });
    });
});
</script>
@endif
@endpush
@endsection
