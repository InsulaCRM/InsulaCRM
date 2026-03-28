@extends('layouts.app')

@section('title', __('Create Workflow'))
@section('page-title', __('Create Workflow'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('workflows.index') }}">{{ __('Workflows') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Create') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('New Workflow') }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('workflows.store') }}" method="POST" id="workflow-form">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Workflow Name') }}</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="{{ __('e.g., New Lead Follow-Up') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Trigger') }}</label>
                    <select name="trigger_type" id="trigger-type" class="form-select @error('trigger_type') is-invalid @enderror" required>
                        <option value="">{{ __('Select a trigger...') }}</option>
                        @foreach($triggerTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('trigger_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('trigger_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                    rows="2" placeholder="{{ __('Describe what this workflow does...') }}">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Trigger Config: Lead Status --}}
            <div id="config-status" class="trigger-config mb-3" style="display:none;">
                <label class="form-label">{{ __('When status changes to') }}</label>
                <select name="trigger_config[status]" class="form-select">
                    <option value="">{{ __('Any status') }}</option>
                    @foreach($leadStatuses as $slug => $label)
                        <option value="{{ $slug }}" {{ old('trigger_config.status') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Trigger Config: Deal Stage --}}
            <div id="config-stage" class="trigger-config mb-3" style="display:none;">
                <label class="form-label">{{ __('When stage changes to') }}</label>
                <select name="trigger_config[stage]" class="form-select">
                    <option value="">{{ __('Any stage') }}</option>
                    @foreach($dealStages as $slug => $label)
                        <option value="{{ $slug }}" {{ old('trigger_config.stage') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Trigger Config: Score Threshold --}}
            <div id="config-threshold" class="trigger-config mb-3" style="display:none;">
                <label class="form-label">{{ __('Score threshold') }}</label>
                <input type="number" name="trigger_config[threshold]" class="form-control" min="1" max="100"
                    value="{{ old('trigger_config.threshold', 70) }}" style="max-width: 200px;">
                <small class="text-muted">{{ __('Workflow triggers when motivation score reaches or exceeds this value.') }}</small>
            </div>

            <div class="card-footer text-end">
                <a href="{{ route('workflows.index') }}" class="btn btn-outline-secondary me-2">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Create & Add Steps') }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var triggerSelect = document.getElementById('trigger-type');

    function showTriggerConfig() {
        document.querySelectorAll('.trigger-config').forEach(function(el) { el.style.display = 'none'; });

        var val = triggerSelect.value;
        if (val === 'lead.status_changed') {
            document.getElementById('config-status').style.display = 'block';
        } else if (val === 'deal.stage_changed') {
            document.getElementById('config-stage').style.display = 'block';
        } else if (val === 'lead.score_above') {
            document.getElementById('config-threshold').style.display = 'block';
        }
    }

    triggerSelect.addEventListener('change', showTriggerConfig);
    showTriggerConfig();
})();
</script>
@endpush
@endsection
