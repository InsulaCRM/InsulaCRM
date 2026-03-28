@extends('layouts.app')

@section('title', __('Edit Campaign'))
@section('page-title', __('Edit Campaign') . ': ' . $campaign->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('campaigns.index') }}">{{ __('Campaigns') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
@endsection

@section('content')
<form action="{{ route('campaigns.update', $campaign) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('Campaign Details') }}</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Campaign Name') }}</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $campaign->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('Type') }}</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">{{ __('-- Select Type --') }}</option>
                        @foreach(\App\Models\Campaign::typeLabels() as $val => $label)
                            <option value="{{ $val }}" {{ old('type', $campaign->type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('Status') }}</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(\App\Models\Campaign::statusLabels() as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $campaign->status) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Budget') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="budget" class="form-control @error('budget') is-invalid @enderror" value="{{ old('budget', $campaign->budget) }}" step="0.01" min="0" placeholder="0.00">
                    </div>
                    @error('budget') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Actual Spend') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="actual_spend" class="form-control @error('actual_spend') is-invalid @enderror" value="{{ old('actual_spend', $campaign->actual_spend) }}" step="0.01" min="0" placeholder="0.00">
                    </div>
                    @error('actual_spend') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Target Lead Count') }}</label>
                    <input type="number" name="target_count" class="form-control @error('target_count') is-invalid @enderror" value="{{ old('target_count', $campaign->target_count) }}" min="0">
                    @error('target_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}">
                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}">
                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('Notes') }}</h3>
            @if(auth()->user()->tenant->ai_enabled)
            <div class="card-actions">
                <button type="button" class="btn btn-outline-purple btn-sm" id="ai-campaign-notes-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                    {{ __('AI Generate Plan') }}
                </button>
            </div>
            @endif
        </div>
        <div class="card-body">
            <label for="campaign-notes" class="visually-hidden">{{ __('Notes') }}</label>
            <textarea name="notes" id="campaign-notes" class="form-control @error('notes') is-invalid @enderror" rows="4" placeholder="{{ __('Campaign description, goals, target audience, etc.') }}">{{ old('notes', $campaign->notes) }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Update Campaign') }}</button>
    </div>
</form>
@if(auth()->user()->tenant->ai_enabled)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var aiBtn = document.getElementById('ai-campaign-notes-btn');
    if (aiBtn) {
        aiBtn.addEventListener('click', function() {
            var btn = this;
            var origText = btn.innerHTML;
            var nameField = document.querySelector('input[name="name"]');
            var typeField = document.querySelector('select[name="type"]');
            var budgetField = document.querySelector('input[name="budget"]');

            if (!nameField.value || !typeField.value) {
                alert('{{ __("Please fill in Campaign Name and Type first.") }}');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> {{ __("Generating...") }}';

            fetch('{{ url("/ai/generate-campaign-notes") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: nameField.value,
                    type: typeField.value,
                    budget: budgetField.value || null,
                })
            }).then(function(r) { return r.json(); }).then(function(res) {
                btn.disabled = false;
                btn.innerHTML = origText;
                if (res.error) { alert(res.error); return; }
                var textarea = document.getElementById('campaign-notes');
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
