@extends('layouts.app')

@section('title', __('Create Sequence'))
@section('page-title', __('Create Drip Sequence'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('sequences.index') }}">{{ __('Sequences') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Create Sequence') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Sequence Information') }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('sequences.store') }}" method="POST" id="sequence-form">
            @csrf
            <div class="mb-3">
                <label class="form-label required">{{ __('Sequence Name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h3 class="mb-2">{{ __('Steps') }}</h3>
            <p class="text-secondary mb-3">{{ __('Each step runs after the specified delay from the previous step. Set delay to 0 for the first touch. Use different action types (SMS, Email, etc.) to vary your outreach cadence.') }}</p>
            <div id="steps-container">
                @if(old('steps'))
                    @foreach(old('steps') as $i => $step)
                    <div class="card mb-3 step-row">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0">Step <span class="step-number">{{ $i + 1 }}</span></h4>
                                <button type="button" class="btn btn-ghost-danger btn-sm remove-step">{{ __('Remove') }}</button>
                            </div>
                            <input type="hidden" name="steps[{{ $i }}][order]" value="{{ $i + 1 }}" class="step-order">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label required">{{ __('Delay (Days)') }}</label>
                                    <input type="number" name="steps[{{ $i }}][delay_days]" class="form-control @error('steps.'.$i.'.delay_days') is-invalid @enderror" value="{{ $step['delay_days'] ?? '' }}" min="0" required>
                                    @error('steps.'.$i.'.delay_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">{{ __('Action Type') }}</label>
                                    <select name="steps[{{ $i }}][action_type]" class="form-select @error('steps.'.$i.'.action_type') is-invalid @enderror" required>
                                        <option value="">{{ __('Select...') }}</option>
                                        @foreach(['sms' => __('SMS'), 'email' => __('Email'), 'voicemail' => __('Voicemail'), 'task' => __('Task'), 'direct_mail' => __('Direct Mail')] as $val => $label)
                                            <option value="{{ $val }}" {{ ($step['action_type'] ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('steps.'.$i.'.action_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">{{ __('Message Template') }}</label>
                                    @if(auth()->user()->tenant->ai_enabled)
                                    <button type="button" class="btn btn-outline-purple btn-sm ai-generate-step-btn" data-step-index="{{ $i }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                        {{ __('AI Generate') }}
                                    </button>
                                    @endif
                                </div>
                                <textarea name="steps[{{ $i }}][message_template]" class="form-control step-template @error('steps.'.$i.'.message_template') is-invalid @enderror" rows="3">{{ $step['message_template'] ?? '' }}</textarea>
                                @error('steps.'.$i.'.message_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="card mb-3 step-row">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0">Step <span class="step-number">1</span></h4>
                                <button type="button" class="btn btn-ghost-danger btn-sm remove-step">{{ __('Remove') }}</button>
                            </div>
                            <input type="hidden" name="steps[0][order]" value="1" class="step-order">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label required">{{ __('Delay (Days)') }}</label>
                                    <input type="number" name="steps[0][delay_days]" class="form-control" value="0" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">{{ __('Action Type') }}</label>
                                    <select name="steps[0][action_type]" class="form-select" required>
                                        <option value="">{{ __('Select...') }}</option>
                                        <option value="sms">{{ __('SMS') }}</option>
                                        <option value="email">{{ __('Email') }}</option>
                                        <option value="voicemail">{{ __('Voicemail') }}</option>
                                        <option value="task">{{ __('Task') }}</option>
                                        <option value="direct_mail">{{ __('Direct Mail') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">{{ __('Message Template') }}</label>
                                    @if(auth()->user()->tenant->ai_enabled)
                                    <button type="button" class="btn btn-outline-purple btn-sm ai-generate-step-btn" data-step-index="0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                        {{ __('AI Generate') }}
                                    </button>
                                    @endif
                                </div>
                                <textarea name="steps[0][message_template]" class="form-control step-template" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if(auth()->user()->tenant->ai_enabled)
            <button type="button" class="btn btn-outline-purple mb-3 me-2" id="ai-generate-all-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                {{ __('AI Generate All Steps') }}
            </button>
            @endif
            <button type="button" class="btn btn-outline-secondary mb-3" id="add-step-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Step') }}
            </button>

            <div class="card-footer text-end">
                <a href="{{ route('sequences.index') }}" class="btn btn-outline-secondary me-2">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Create Sequence') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showMsg(msg, type) {
    if (window.showToast) { window.showToast(msg, type || 'danger'); }
    else { console.error(msg); }
}

document.getElementById('add-step-btn').addEventListener('click', function() {
    var container = document.getElementById('steps-container');
    var stepCount = container.querySelectorAll('.step-row').length;
    var index = stepCount;

    var html = '<div class="card mb-3 step-row">' +
        '<div class="card-body">' +
        '<div class="d-flex justify-content-between align-items-center mb-3">' +
        '<h4 class="card-title mb-0">Step <span class="step-number">' + (index + 1) + '</span></h4>' +
        '<button type="button" class="btn btn-ghost-danger btn-sm remove-step">Remove</button>' +
        '</div>' +
        '<input type="hidden" name="steps[' + index + '][order]" value="' + (index + 1) + '" class="step-order">' +
        '<div class="row mb-3">' +
        '<div class="col-md-4">' +
        '<label class="form-label required">Delay (Days)</label>' +
        '<input type="number" name="steps[' + index + '][delay_days]" class="form-control" value="0" min="0" required>' +
        '</div>' +
        '<div class="col-md-4">' +
        '<label class="form-label required">Action Type</label>' +
        '<select name="steps[' + index + '][action_type]" class="form-select" required>' +
        '<option value="">Select...</option>' +
        '<option value="sms">SMS</option>' +
        '<option value="email">Email</option>' +
        '<option value="voicemail">Voicemail</option>' +
        '<option value="task">Task</option>' +
        '<option value="direct_mail">Direct Mail</option>' +
        '</select>' +
        '</div>' +
        '</div>' +
        '<div class="mb-0">' +
        '<div class="d-flex justify-content-between align-items-center mb-1">' +
        '<label class="form-label mb-0">Message Template</label>' +
        @if(auth()->user()->tenant->ai_enabled)
        '<button type="button" class="btn btn-outline-purple btn-sm ai-generate-step-btn" data-step-index="' + index + '">' +
        '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg> AI Generate</button>' +
        @endif
        '</div>' +
        '<textarea name="steps[' + index + '][message_template]" class="form-control step-template" rows="3"></textarea>' +
        '</div>' +
        '</div>' +
        '</div>';

    container.insertAdjacentHTML('beforeend', html);
});

document.getElementById('steps-container').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-step')) {
        var rows = this.querySelectorAll('.step-row');
        if (rows.length > 1) {
            e.target.closest('.step-row').remove();
            this.querySelectorAll('.step-row').forEach(function(row, i) {
                row.querySelector('.step-number').textContent = i + 1;
                row.querySelector('.step-order').value = i + 1;
                var aiBtn = row.querySelector('.ai-generate-step-btn');
                if (aiBtn) aiBtn.dataset.stepIndex = i;
            });
        }
    }
});

@if(auth()->user()->tenant->ai_enabled)
// AI Generate All Steps handler
document.getElementById('ai-generate-all-btn').addEventListener('click', function() {
    var btn = this;
    var sequenceName = document.querySelector('input[name="name"]').value || 'Untitled Sequence';
    var rows = document.querySelectorAll('.step-row');
    var steps = [];
    rows.forEach(function(row) {
        var actionSelect = row.querySelector('select[name*="action_type"]');
        var delayInput = row.querySelector('input[name*="delay_days"]');
        steps.push({
            action_type: actionSelect ? actionSelect.value : 'sms',
            delay_days: delayInput ? parseInt(delayInput.value) || 0 : 0
        });
    });

    if (steps.some(function(s) { return !s.action_type; })) {
        showMsg('Please select an action type for all steps first.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating All...';

    fetch('{{ url("/ai/generate-all-sequence-steps") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ sequence_name: sequenceName, steps: steps })
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (res.error) { showMsg(res.error); return; }
        if (res.templates && Array.isArray(res.templates)) {
            rows.forEach(function(row, i) {
                if (res.templates[i]) {
                    var textarea = row.querySelector('.step-template');
                    if (textarea) {
                        textarea.value = res.templates[i];
                        textarea.rows = Math.min(8, res.templates[i].split('\n').length + 1);
                    }
                }
            });
        }
    }).catch(function() {
        showMsg('AI request failed. Please try again.');
    }).finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg> AI Generate All Steps';
    });
});

// AI Generate handler for sequence steps
document.getElementById('steps-container').addEventListener('click', function(e) {
    var btn = e.target.closest('.ai-generate-step-btn');
    if (!btn) return;

    var stepRow = btn.closest('.step-row');
    var textarea = stepRow.querySelector('.step-template');
    var actionSelect = stepRow.querySelector('select[name*="action_type"]');
    var delayInput = stepRow.querySelector('input[name*="delay_days"]');
    var sequenceName = document.querySelector('input[name="name"]').value || 'Untitled Sequence';
    var allRows = document.querySelectorAll('.step-row');
    var stepNumber = Array.prototype.indexOf.call(allRows, stepRow) + 1;
    var totalSteps = allRows.length;
    var actionType = actionSelect ? actionSelect.value : 'sms';
    var delayDays = delayInput ? parseInt(delayInput.value) || 0 : 0;

    if (!actionType) {
        showMsg('Please select an action type first.');
        return;
    }

    var previousStep = null;
    if (stepNumber > 1) {
        var prevRow = allRows[stepNumber - 2];
        var prevTemplate = prevRow.querySelector('.step-template');
        var prevAction = prevRow.querySelector('select[name*="action_type"]');
        if (prevTemplate && prevTemplate.value) {
            previousStep = (prevAction ? prevAction.value : '') + ': ' + prevTemplate.value.substring(0, 100);
        }
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    fetch('{{ url("/ai/draft-sequence-step") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            sequence_name: sequenceName,
            step_number: stepNumber,
            total_steps: totalSteps,
            action_type: actionType,
            delay_days: delayDays,
            previous_step: previousStep
        })
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (res.error) {
            showMsg(res.error);
        } else if (res.message) {
            textarea.value = res.message;
            textarea.rows = Math.min(8, res.message.split('\n').length + 1);
        }
    }).catch(function() {
        showMsg('AI request failed. Please try again.');
    }).finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg> AI Generate';
    });
});
@endif
</script>
@endpush
@endsection
