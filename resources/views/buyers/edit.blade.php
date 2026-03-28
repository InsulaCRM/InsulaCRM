@extends('layouts.app')

@section('title', ($businessMode ?? 'wholesale') === 'realestate' ? __('Edit Client') : __('Edit Buyer'))
@section('page-title', ($businessMode ?? 'wholesale') === 'realestate' ? __('Edit Client') : __('Edit Buyer'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('buyers.index') }}">{{ $modeTerms['buyer_label'] ?? __('Buyers') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('buyers.show', $buyer) }}">{{ $buyer->full_name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Edit Client:') : __('Edit Buyer:') }} {{ $buyer->full_name }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('buyers.update', $buyer) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('First Name') }}</label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $buyer->first_name) }}" required>
                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Last Name') }}</label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $buyer->last_name) }}" required>
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Company') }}</label>
                    <input type="text" name="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company', $buyer->company) }}">
                    @error('company') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $buyer->phone) }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $buyer->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Budget') : __('Max Purchase Price') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="max_purchase_price" class="form-control @error('max_purchase_price') is-invalid @enderror" value="{{ old('max_purchase_price', $buyer->max_purchase_price) }}" step="0.01" min="0">
                        @error('max_purchase_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Preferred Property Types') }}</label>
                <div class="row">
                    @php $selectedTypes = old('preferred_property_types', $buyer->preferred_property_types ?? []); @endphp
                    @foreach(\App\Services\CustomFieldService::getOptions('property_type') as $val => $label)
                    <div class="col-md-3">
                        <label class="form-check">
                            <input type="checkbox" name="preferred_property_types[]" value="{{ $val }}" class="form-check-input" {{ in_array($val, $selectedTypes) ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('preferred_property_types') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Preferred States') }}</label>
                    <textarea name="preferred_states" class="form-control @error('preferred_states') is-invalid @enderror" rows="3" placeholder="{{ __('Comma-separated state abbreviations (e.g., TX, FL, CA)') }}">{{ old('preferred_states', is_array($buyer->preferred_states) ? implode(', ', $buyer->preferred_states) : $buyer->preferred_states) }}</textarea>
                    @error('preferred_states') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Preferred Zip Codes') }}</label>
                    <textarea name="preferred_zip_codes" class="form-control @error('preferred_zip_codes') is-invalid @enderror" rows="3" placeholder="{{ __('Comma-separated zip codes (e.g., 75001, 33101, 90210)') }}">{{ old('preferred_zip_codes', is_array($buyer->preferred_zip_codes) ? implode(', ', $buyer->preferred_zip_codes) : $buyer->preferred_zip_codes) }}</textarea>
                    @error('preferred_zip_codes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            @if(($businessMode ?? 'wholesale') !== 'realestate')
            <div class="mb-3">
                <label class="form-label">{{ __('Asset Classes') }}</label>
                <div class="row">
                    @php $selectedClasses = old('asset_classes', $buyer->asset_classes ?? []); @endphp
                    @foreach(['sfr' => __('SFR'), 'multi_family' => __('Multi Family'), 'commercial' => __('Commercial'), 'land' => __('Land')] as $val => $label)
                    <div class="col-md-3">
                        <label class="form-check">
                            <input type="checkbox" name="asset_classes[]" value="{{ $val }}" class="form-check-input" {{ in_array($val, $selectedClasses) ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('asset_classes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            @endif
            <div class="mb-3">
                <label class="form-label">
                    {{ __('Notes') }}
                    @if(auth()->user()->tenant->ai_enabled)
                    <button type="button" class="btn btn-outline-purple btn-sm ms-2" id="ai-buyer-notes-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('AI Generate') }}
                    </button>
                    @endif
                </label>
                <textarea name="notes" id="buyer-notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes', $buyer->notes) }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('buyers.show', $buyer) }}" class="btn btn-outline-secondary me-2">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Update Client') : __('Update Buyer') }}</button>
            </div>
        </form>
    </div>
</div>
@if(auth()->user()->tenant->ai_enabled)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var aiBtn = document.getElementById('ai-buyer-notes-btn');
    if (aiBtn) {
        aiBtn.addEventListener('click', function() {
            var btn = this;
            var origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> {{ __("Generating...") }}';

            var data = {
                first_name: document.querySelector('input[name="first_name"]').value,
                last_name: document.querySelector('input[name="last_name"]').value,
                company: document.querySelector('input[name="company"]').value,
                max_purchase_price: document.querySelector('input[name="max_purchase_price"]').value,
                preferred_property_types: Array.from(document.querySelectorAll('input[name="preferred_property_types[]"]:checked')).map(function(cb) { return cb.value; }),
                preferred_states: document.querySelector('[name="preferred_states"]').value,
                preferred_zip_codes: document.querySelector('[name="preferred_zip_codes"]').value,
                asset_classes: Array.from(document.querySelectorAll('input[name="asset_classes[]"]:checked')).map(function(cb) { return cb.value; }),
            };

            fetch('{{ url("/ai/generate-buyer-notes") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(data)
            }).then(function(r) { return r.json(); }).then(function(res) {
                btn.disabled = false;
                btn.innerHTML = origText;
                if (res.error) { alert(res.error); return; }
                var textarea = document.getElementById('buyer-notes');
                textarea.value = res.notes || '';
                textarea.rows = Math.max(4, (res.notes || '').split('\n').length + 1);
            }).catch(function() {
                btn.disabled = false;
                btn.innerHTML = origText;
                alert('{{ __("AI request failed.") }}');
            });
        });
    }
});
</script>
@endpush
@endif
@endsection
