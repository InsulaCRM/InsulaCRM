@extends('layouts.app')

@section('title', __('Edit Workflow') . ' - ' . $workflow->name)
@section('page-title', $workflow->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('workflows.index') }}">{{ __('Workflows') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
@endsection

@push('styles')
<style>
.wf-step-list { position: relative; padding-left: 24px; }
.wf-step-card { position: relative; margin-bottom: 0; cursor: grab; }
.wf-step-card.dragging { opacity: 0.5; }
.wf-step-card.drag-over { border-top: 3px solid var(--tblr-primary); }
.wf-connector { width: 2px; height: 24px; background: var(--tblr-border-color); margin-left: 20px; }
.wf-step-number {
    position: absolute; left: -24px; top: 14px;
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700; color: #fff;
}
.wf-step-number.action { background: var(--tblr-primary); }
.wf-step-number.condition { background: var(--tblr-warning); }
.wf-step-number.delay { background: var(--tblr-info); }
.wf-type-icon { width: 20px; height: 20px; vertical-align: middle; }
.wf-condition-branches { display: flex; gap: 1rem; margin-top: 0.5rem; }
.wf-condition-branches .badge { font-size: 0.7rem; }
</style>
@endpush

@section('content')
{{-- Workflow Details Card --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Workflow Details') }}</h3>
        <div class="card-actions">
            <a href="{{ route('workflows.logs', $workflow) }}" class="btn btn-outline-secondary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 12h8"/><path d="M4 18h8"/><path d="M4 6h16"/><path d="M16 12l4 4l-4 4"/></svg>
                {{ __('Run Logs') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('workflows.update', $workflow) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Name') }}</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $workflow->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Trigger') }}</label>
                    <select name="trigger_type" id="trigger-type" class="form-select @error('trigger_type') is-invalid @enderror" required>
                        @foreach($triggerTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('trigger_type', $workflow->trigger_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('trigger_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }}</label>
                    <div class="pt-1">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="workflow-active-toggle" {{ $workflow->is_active ? 'checked' : '' }}>
                            <span class="form-check-label" id="workflow-status-label">{{ $workflow->is_active ? __('Active') : __('Inactive') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $workflow->description) }}</textarea>
                </div>
                <div class="col-md-4">
                    {{-- Trigger Config: Status --}}
                    <div id="config-status" class="trigger-config" style="display:none;">
                        <label class="form-label">{{ __('Target Status') }}</label>
                        <select name="trigger_config[status]" class="form-select">
                            <option value="">{{ __('Any') }}</option>
                            @foreach($leadStatuses as $slug => $label)
                                <option value="{{ $slug }}" {{ ($workflow->trigger_config['status'] ?? '') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Trigger Config: Stage --}}
                    <div id="config-stage" class="trigger-config" style="display:none;">
                        <label class="form-label">{{ __('Target Stage') }}</label>
                        <select name="trigger_config[stage]" class="form-select">
                            <option value="">{{ __('Any') }}</option>
                            @foreach($dealStages as $slug => $label)
                                <option value="{{ $slug }}" {{ ($workflow->trigger_config['stage'] ?? '') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Trigger Config: Score --}}
                    <div id="config-threshold" class="trigger-config" style="display:none;">
                        <label class="form-label">{{ __('Score Threshold') }}</label>
                        <input type="number" name="trigger_config[threshold]" class="form-control" min="1" max="100"
                            value="{{ $workflow->trigger_config['threshold'] ?? 70 }}">
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">{{ __('Save Details') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Steps Builder Card --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            {{ __('Steps') }}
            <span class="badge bg-secondary-lt ms-2" id="step-count">{{ $workflow->steps->count() }}</span>
        </h3>
        <div class="card-actions">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add-step-modal">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Step') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        @if($workflow->steps->isEmpty())
        <div class="text-center text-secondary py-4" id="empty-steps">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l0 18"/><path d="M18 9l-6 -6l-6 6"/></svg>
            <p class="mb-2">{{ __('No steps added yet.') }}</p>
            <p class="text-muted">{{ __('Add actions, conditions, and delays to build your automation flow.') }}</p>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add-step-modal">{{ __('Add First Step') }}</button>
        </div>
        @endif

        <div class="wf-step-list" id="step-list">
            @foreach($workflow->steps as $index => $step)
            <div class="wf-step-card card mb-0" draggable="true" data-step-id="{{ $step->id }}" data-position="{{ $step->position }}">
                <div class="card-body py-2 px-3">
                    <span class="wf-step-number {{ $step->type }}">{{ $step->position }}</span>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="me-2">
                                @if($step->type === 'action')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="wf-type-icon text-primary" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"/></svg>
                                @elseif($step->type === 'condition')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="wf-type-icon text-warning" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M7 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M7 8l0 8"/><path d="M9 18h6a2 2 0 0 0 2 -2v-5"/><path d="M14 14l3 -3l3 3"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="wf-type-icon text-info" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                                @endif
                            </span>
                            <div>
                                <span class="badge bg-{{ $step->type === 'action' ? 'primary' : ($step->type === 'condition' ? 'warning' : 'info') }}-lt me-2">{{ __(ucfirst($step->type)) }}</span>
                                <span class="text-secondary">{{ $step->summary }}</span>
                                @if($step->type === 'condition')
                                <div class="wf-condition-branches">
                                    <span class="badge bg-green-lt">{{ __('Yes') }}: {{ $step->next_step_id ? __('Step :n', ['n' => optional($step->nextStep)->position ?? '?']) : __('Continue') }}</span>
                                    <span class="badge bg-red-lt">{{ __('No') }}: {{ $step->alt_step_id ? __('Step :n', ['n' => optional($step->altStep)->position ?? '?']) : __('Skip') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-ghost-secondary btn-icon btn-sm edit-step-btn" data-step='@json($step)' title="{{ __('Edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"/><line x1="13.5" y1="6.5" x2="17.5" y2="10.5"/></svg>
                            </button>
                            <button type="button" class="btn btn-ghost-danger btn-icon btn-sm delete-step-btn" data-step-id="{{ $step->id }}" title="{{ __('Delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @if(!$loop->last)
            <div class="wf-connector"></div>
            @endif
            @endforeach
        </div>
    </div>
</div>

{{-- Merge Fields Reference --}}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Merge Field Reference') }}</h3>
        <div class="card-actions">
            <a class="btn btn-ghost-secondary btn-sm" data-bs-toggle="collapse" href="#merge-fields-ref">{{ __('Toggle') }}</a>
        </div>
    </div>
    <div class="collapse" id="merge-fields-ref">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h4>{{ __('Lead Fields') }}</h4>
                    <code class="d-block mb-1">@{{first_name}}</code>
                    <code class="d-block mb-1">@{{last_name}}</code>
                    <code class="d-block mb-1">@{{full_name}}</code>
                    <code class="d-block mb-1">@{{email}}</code>
                    <code class="d-block mb-1">@{{phone}}</code>
                    <code class="d-block mb-1">@{{status}}</code>
                    <code class="d-block mb-1">@{{lead_source}}</code>
                    <code class="d-block mb-1">@{{motivation_score}}</code>
                </div>
                <div class="col-md-4">
                    <h4>{{ __('Property Fields') }}</h4>
                    <code class="d-block mb-1">@{{property.address}}</code>
                    <code class="d-block mb-1">@{{property.city}}</code>
                    <code class="d-block mb-1">@{{property.state}}</code>
                    <code class="d-block mb-1">@{{property.zip_code}}</code>
                    <code class="d-block mb-1">@{{property.property_type}}</code>
                    <code class="d-block mb-1">@{{property.estimated_value}}</code>
                    @if(($businessMode ?? 'wholesale') === 'realestate')
                    <code class="d-block mb-1">@{{property.list_price}}</code>
                    <code class="d-block mb-1">@{{property.listing_status}}</code>
                    @endif
                </div>
                <div class="col-md-4">
                    <h4>{{ $modeTerms['deal_label'] ?? __('Deal') }} {{ __('Fields') }}</h4>
                    <code class="d-block mb-1">@{{title}}</code>
                    <code class="d-block mb-1">@{{stage}}</code>
                    <code class="d-block mb-1">@{{contract_price}}</code>
                    @if(($businessMode ?? 'wholesale') === 'realestate')
                    <code class="d-block mb-1">@{{total_commission}}</code>
                    <code class="d-block mb-1">@{{mls_number}}</code>
                    @else
                    <code class="d-block mb-1">@{{assignment_fee}}</code>
                    @endif
                    <code class="d-block mb-1">@{{lead.first_name}}</code>
                    <code class="d-block mb-1">@{{lead.full_name}}</code>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Step Modal --}}
<div class="modal modal-blur fade" id="add-step-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="step-modal-title">{{ __('Add Step') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-step-id" value="">

                <div class="mb-3">
                    <label class="form-label required">{{ __('Step Type') }}</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="step_type" id="type-action" value="action" checked>
                        <label class="btn btn-outline-primary" for="type-action">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"/></svg>
                            {{ __('Action') }}
                        </label>
                        <input type="radio" class="btn-check" name="step_type" id="type-condition" value="condition">
                        <label class="btn btn-outline-warning" for="type-condition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M7 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M7 8l0 8"/><path d="M9 18h6a2 2 0 0 0 2 -2v-5"/><path d="M14 14l3 -3l3 3"/></svg>
                            {{ __('Condition') }}
                        </label>
                        <input type="radio" class="btn-check" name="step_type" id="type-delay" value="delay">
                        <label class="btn btn-outline-info" for="type-delay">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                            {{ __('Delay') }}
                        </label>
                    </div>
                </div>

                {{-- Action Config --}}
                <div id="action-config" class="step-type-config">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('Action') }}</label>
                        <select id="action-type-select" class="form-select">
                            @foreach($actionTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dynamic action config fields --}}
                    <div id="action-fields">
                        {{-- send_email --}}
                        <div class="action-field-group" data-action="send_email">
                            <div class="mb-3">
                                <label class="form-label">{{ __('To (email)') }}</label>
                                <input type="text" class="form-control" data-config="to" placeholder="{{ __('Leave blank to use model email') }}">
                                <small class="text-muted">{{ __('Supports merge fields: {{email}}') }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Subject') }}</label>
                                <input type="text" class="form-control" data-config="subject" placeholder="{{ __('e.g., Follow-up on your property at {{property.address}}') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Body') }}</label>
                                <textarea class="form-control" data-config="body" rows="4" placeholder="{{ __('Email body with merge fields...') }}"></textarea>
                            </div>
                        </div>

                        {{-- send_sms --}}
                        <div class="action-field-group" data-action="send_sms" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">{{ __('To (phone)') }}</label>
                                <input type="text" class="form-control" data-config="to" placeholder="{{ __('Leave blank to use model phone') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Message') }}</label>
                                <textarea class="form-control" data-config="message" rows="3" placeholder="{{ __('SMS message with merge fields...') }}"></textarea>
                            </div>
                        </div>

                        {{-- create_task --}}
                        <div class="action-field-group" data-action="create_task" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Task Title') }}</label>
                                <input type="text" class="form-control" data-config="title" placeholder="{{ __('e.g., Follow up with {{first_name}}') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Description') }}</label>
                                <textarea class="form-control" data-config="description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Due in (days)') }}</label>
                                <input type="number" class="form-control" data-config="due_in_days" value="1" min="1" style="max-width: 150px;">
                            </div>
                        </div>

                        {{-- update_field --}}
                        <div class="action-field-group" data-action="update_field" style="display:none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">{{ __('Field Name') }}</label>
                                    <input type="text" class="form-control" data-config="field" placeholder="{{ __('e.g., status, temperature') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">{{ __('New Value') }}</label>
                                    <input type="text" class="form-control" data-config="value" placeholder="{{ __('e.g., contacted, hot') }}">
                                </div>
                            </div>
                        </div>

                        {{-- assign_agent --}}
                        <div class="action-field-group" data-action="assign_agent" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Agent') }}</label>
                                <select class="form-select" data-config="agent_id">
                                    <option value="">{{ __('Select agent...') }}</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- add_tag --}}
                        <div class="action-field-group" data-action="add_tag" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Tag Name') }}</label>
                                <input type="text" class="form-control" data-config="tag_name" placeholder="{{ __('e.g., hot-lead, follow-up') }}">
                                <small class="text-muted">{{ __('Tag will be created if it does not exist.') }}</small>
                            </div>
                        </div>

                        {{-- notify_user --}}
                        <div class="action-field-group" data-action="notify_user" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">{{ __('User') }}</label>
                                <select class="form-select" data-config="user_id">
                                    <option value="">{{ __('Assigned agent (default)') }}</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Notification Message') }}</label>
                                <textarea class="form-control" data-config="message" rows="2" placeholder="{{ __('e.g., New hot lead: {{first_name}} {{last_name}}') }}"></textarea>
                            </div>
                        </div>

                        {{-- webhook --}}
                        <div class="action-field-group" data-action="webhook" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('Webhook URL') }}</label>
                                <input type="url" class="form-control" data-config="url" placeholder="https://example.com/webhook">
                            </div>
                        </div>

                        {{-- ai_qualify_lead --}}
                        <div class="action-field-group" data-action="ai_qualify_lead" style="display:none;">
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="alert-title">{{ __('AI Lead Qualification') }}</h4>
                                        <div class="text-secondary">{{ __('This action uses AI to automatically analyze the lead and set its temperature (hot/warm/cold) based on available data, property info, and distress signals. No additional configuration needed. Requires AI to be enabled in Settings.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Condition Config --}}
                <div id="condition-config" class="step-type-config" style="display:none;">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">{{ __('Field') }}</label>
                            <input type="text" id="condition-field" class="form-control" placeholder="{{ __('e.g., status, temperature, motivation_score') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">{{ __('Operator') }}</label>
                            <select id="condition-operator" class="form-select">
                                @foreach($conditionOperators as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Value') }}</label>
                            <input type="text" id="condition-value" class="form-control" placeholder="{{ __('e.g., hot, new') }}">
                        </div>
                    </div>
                    @if($workflow->steps->count() > 0)
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('If Yes, go to step') }}</label>
                            <select id="condition-next-step" class="form-select">
                                <option value="">{{ __('Continue to next') }}</option>
                                @foreach($workflow->steps as $s)
                                    <option value="{{ $s->id }}">{{ __('Step :n', ['n' => $s->position]) }}: {{ \Illuminate\Support\Str::limit($s->summary, 30) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('If No, go to step') }}</label>
                            <select id="condition-alt-step" class="form-select">
                                <option value="">{{ __('Skip / End') }}</option>
                                @foreach($workflow->steps as $s)
                                    <option value="{{ $s->id }}">{{ __('Step :n', ['n' => $s->position]) }}: {{ \Illuminate\Support\Str::limit($s->summary, 30) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Delay Config --}}
                <div id="delay-config" class="step-type-config" style="display:none;">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">{{ __('Wait for') }}</label>
                            <input type="number" id="delay-amount" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">{{ __('Unit') }}</label>
                            <select id="delay-unit" class="form-select">
                                <option value="minutes">{{ __('Minutes') }}</option>
                                <option value="hours" selected>{{ __('Hours') }}</option>
                                <option value="days">{{ __('Days') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <span class="text-muted" id="delay-preview">= 60 {{ __('minutes') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="save-step-btn">{{ __('Add Step') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var workflowId = {{ $workflow->id }};
    var baseUrl = '{{ url("/workflows") }}';

    // === Trigger Config Toggle ===
    var triggerSelect = document.getElementById('trigger-type');
    function showTriggerConfig() {
        document.querySelectorAll('.trigger-config').forEach(function(el) { el.style.display = 'none'; });
        var val = triggerSelect.value;
        if (val === 'lead.status_changed') document.getElementById('config-status').style.display = 'block';
        else if (val === 'deal.stage_changed') document.getElementById('config-stage').style.display = 'block';
        else if (val === 'lead.score_above') document.getElementById('config-threshold').style.display = 'block';
    }
    triggerSelect.addEventListener('change', showTriggerConfig);
    showTriggerConfig();

    // === Active Toggle ===
    document.getElementById('workflow-active-toggle').addEventListener('change', function() {
        var label = document.getElementById('workflow-status-label');
        var toggle = this;
        fetch(baseUrl + '/' + workflowId + '/toggle', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.success) {
                label.textContent = res.is_active ? '{{ __("Active") }}' : '{{ __("Inactive") }}';
            } else {
                toggle.checked = !toggle.checked;
            }
        }).catch(function() { toggle.checked = !toggle.checked; });
    });

    // === Step Type Toggle in Modal ===
    document.querySelectorAll('input[name="step_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.step-type-config').forEach(function(el) { el.style.display = 'none'; });
            if (this.value === 'action') document.getElementById('action-config').style.display = 'block';
            else if (this.value === 'condition') document.getElementById('condition-config').style.display = 'block';
            else if (this.value === 'delay') document.getElementById('delay-config').style.display = 'block';
        });
    });

    // === Action Type Toggle ===
    var actionSelect = document.getElementById('action-type-select');
    actionSelect.addEventListener('change', function() {
        document.querySelectorAll('.action-field-group').forEach(function(el) { el.style.display = 'none'; });
        var group = document.querySelector('.action-field-group[data-action="' + this.value + '"]');
        if (group) group.style.display = 'block';
    });
    // Show first action group by default
    document.querySelector('.action-field-group[data-action="send_email"]').style.display = 'block';

    // === Delay Preview ===
    function updateDelayPreview() {
        var amount = parseInt(document.getElementById('delay-amount').value) || 1;
        var unit = document.getElementById('delay-unit').value;
        var minutes = amount;
        if (unit === 'hours') minutes = amount * 60;
        else if (unit === 'days') minutes = amount * 1440;
        document.getElementById('delay-preview').textContent = '= ' + minutes + ' {{ __("minutes") }}';
    }
    document.getElementById('delay-amount').addEventListener('input', updateDelayPreview);
    document.getElementById('delay-unit').addEventListener('change', updateDelayPreview);
    updateDelayPreview();

    // === Save Step ===
    document.getElementById('save-step-btn').addEventListener('click', function() {
        var btn = this;
        var editId = document.getElementById('edit-step-id').value;
        var stepType = document.querySelector('input[name="step_type"]:checked').value;
        var payload = { type: stepType };

        if (stepType === 'action') {
            payload.action_type = actionSelect.value;
            var config = {};
            var group = document.querySelector('.action-field-group[data-action="' + actionSelect.value + '"]');
            if (group) {
                group.querySelectorAll('[data-config]').forEach(function(el) {
                    var key = el.dataset.config;
                    var val = el.value.trim();
                    if (val) config[key] = val;
                });
            }
            payload.config = config;
        } else if (stepType === 'condition') {
            payload.condition_field = document.getElementById('condition-field').value;
            payload.condition_operator = document.getElementById('condition-operator').value;
            payload.condition_value = document.getElementById('condition-value').value;
            var nextEl = document.getElementById('condition-next-step');
            var altEl = document.getElementById('condition-alt-step');
            if (nextEl && nextEl.value) payload.next_step_id = parseInt(nextEl.value);
            if (altEl && altEl.value) payload.alt_step_id = parseInt(altEl.value);
        } else if (stepType === 'delay') {
            var amount = parseInt(document.getElementById('delay-amount').value) || 1;
            var unit = document.getElementById('delay-unit').value;
            if (unit === 'hours') payload.delay_minutes = amount * 60;
            else if (unit === 'days') payload.delay_minutes = amount * 1440;
            else payload.delay_minutes = amount;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Saving...") }}';

        var url = editId
            ? baseUrl + '/steps/' + editId
            : baseUrl + '/' + workflowId + '/steps';
        var method = editId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.success) {
                window.location.reload();
            } else {
                var msg = res.message || res.errors ? Object.values(res.errors || {}).flat().join(', ') : '{{ __("Failed to save step.") }}';
                alert(msg);
            }
        }).catch(function(e) {
            alert('{{ __("Request failed. Please try again.") }}');
        }).finally(function() {
            btn.disabled = false;
            btn.textContent = editId ? '{{ __("Update Step") }}' : '{{ __("Add Step") }}';
        });
    });

    // === Edit Step ===
    document.querySelectorAll('.edit-step-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var step = JSON.parse(this.dataset.step);
            document.getElementById('edit-step-id').value = step.id;
            document.getElementById('step-modal-title').textContent = '{{ __("Edit Step") }}';
            document.getElementById('save-step-btn').textContent = '{{ __("Update Step") }}';

            // Set step type
            var typeRadio = document.getElementById('type-' + step.type);
            if (typeRadio) { typeRadio.checked = true; typeRadio.dispatchEvent(new Event('change')); }

            if (step.type === 'action') {
                actionSelect.value = step.action_type || 'send_email';
                actionSelect.dispatchEvent(new Event('change'));
                // Fill config fields
                var config = step.config || {};
                var group = document.querySelector('.action-field-group[data-action="' + step.action_type + '"]');
                if (group) {
                    group.querySelectorAll('[data-config]').forEach(function(el) {
                        el.value = config[el.dataset.config] || '';
                    });
                }
            } else if (step.type === 'condition') {
                document.getElementById('condition-field').value = step.condition_field || '';
                document.getElementById('condition-operator').value = step.condition_operator || 'equals';
                document.getElementById('condition-value').value = step.condition_value || '';
                var nextEl = document.getElementById('condition-next-step');
                var altEl = document.getElementById('condition-alt-step');
                if (nextEl) nextEl.value = step.next_step_id || '';
                if (altEl) altEl.value = step.alt_step_id || '';
            } else if (step.type === 'delay') {
                var mins = step.delay_minutes || 60;
                if (mins >= 1440 && mins % 1440 === 0) {
                    document.getElementById('delay-amount').value = mins / 1440;
                    document.getElementById('delay-unit').value = 'days';
                } else if (mins >= 60 && mins % 60 === 0) {
                    document.getElementById('delay-amount').value = mins / 60;
                    document.getElementById('delay-unit').value = 'hours';
                } else {
                    document.getElementById('delay-amount').value = mins;
                    document.getElementById('delay-unit').value = 'minutes';
                }
                updateDelayPreview();
            }

            var modal = new bootstrap.Modal(document.getElementById('add-step-modal'));
            modal.show();
        });
    });

    // Reset modal when opening for "Add"
    document.querySelector('[data-bs-target="#add-step-modal"]') && document.querySelectorAll('[data-bs-target="#add-step-modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit-step-id').value = '';
            document.getElementById('step-modal-title').textContent = '{{ __("Add Step") }}';
            document.getElementById('save-step-btn').textContent = '{{ __("Add Step") }}';
            // Reset form fields
            document.getElementById('type-action').checked = true;
            document.getElementById('type-action').dispatchEvent(new Event('change'));
            actionSelect.value = 'send_email';
            actionSelect.dispatchEvent(new Event('change'));
            document.querySelectorAll('#add-step-modal input[type="text"], #add-step-modal textarea').forEach(function(el) { el.value = ''; });
            document.getElementById('delay-amount').value = 1;
            document.getElementById('delay-unit').value = 'hours';
            updateDelayPreview();
        });
    });

    // === Delete Step ===
    document.querySelectorAll('.delete-step-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('{{ __("Delete this step?") }}')) return;
            var stepId = this.dataset.stepId;
            fetch(baseUrl + '/steps/' + stepId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) window.location.reload();
            });
        });
    });

    // === Drag & Drop Reorder ===
    var stepList = document.getElementById('step-list');
    var dragItem = null;

    stepList.addEventListener('dragstart', function(e) {
        var card = e.target.closest('.wf-step-card');
        if (!card) return;
        dragItem = card;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.stepId);
    });

    stepList.addEventListener('dragend', function(e) {
        var card = e.target.closest('.wf-step-card');
        if (card) card.classList.remove('dragging');
        document.querySelectorAll('.wf-step-card').forEach(function(c) { c.classList.remove('drag-over'); });
        dragItem = null;
    });

    stepList.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var card = e.target.closest('.wf-step-card');
        if (card && card !== dragItem) {
            document.querySelectorAll('.wf-step-card').forEach(function(c) { c.classList.remove('drag-over'); });
            card.classList.add('drag-over');
        }
    });

    stepList.addEventListener('dragleave', function(e) {
        var card = e.target.closest('.wf-step-card');
        if (card) card.classList.remove('drag-over');
    });

    stepList.addEventListener('drop', function(e) {
        e.preventDefault();
        var targetCard = e.target.closest('.wf-step-card');
        if (!targetCard || !dragItem || targetCard === dragItem) return;

        // Reorder in DOM
        var cards = Array.from(stepList.querySelectorAll('.wf-step-card'));
        var dragIndex = cards.indexOf(dragItem);
        var targetIndex = cards.indexOf(targetCard);

        if (dragIndex < targetIndex) {
            targetCard.parentNode.insertBefore(dragItem, targetCard.nextSibling);
        } else {
            targetCard.parentNode.insertBefore(dragItem, targetCard);
        }

        // Build new order array
        var newOrder = [];
        stepList.querySelectorAll('.wf-step-card').forEach(function(card) {
            newOrder.push(parseInt(card.dataset.stepId));
        });

        // Update step numbers visually
        stepList.querySelectorAll('.wf-step-card').forEach(function(card, i) {
            var numEl = card.querySelector('.wf-step-number');
            if (numEl) numEl.textContent = i + 1;
        });

        // Rebuild connectors
        var connectors = stepList.querySelectorAll('.wf-connector');
        connectors.forEach(function(c) { c.remove(); });
        var allCards = stepList.querySelectorAll('.wf-step-card');
        allCards.forEach(function(card, i) {
            if (i < allCards.length - 1) {
                var connector = document.createElement('div');
                connector.className = 'wf-connector';
                card.after(connector);
            }
        });

        // Send reorder to server
        fetch(baseUrl + '/' + workflowId + '/reorder', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: newOrder })
        });
    });
})();
</script>
@endpush
@endsection
