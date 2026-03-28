@extends('layouts.app')

@section('title', __('Workflows'))
@section('page-title', __('Workflow Automations'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Workflows') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Workflows') }}</h3>
        <div class="card-actions">
            @if(($businessMode ?? 'wholesale') === 'realestate')
            <div class="dropdown d-inline-block">
                <button class="btn btn-outline-primary" data-bs-toggle="dropdown">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 1a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-14a1 1 0 0 1 -1 -1z"/><path d="M4 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><line x1="14" y1="12" x2="20" y2="12"/><line x1="14" y1="16" x2="20" y2="16"/><line x1="14" y1="20" x2="20" y2="20"/></svg>
                    {{ __('Use Template') }}
                </button>
                <div class="dropdown-menu" id="workflow-templates-dropdown">
                    <span class="dropdown-item text-muted">{{ __('Loading...') }}</span>
                </div>
            </div>
            @endif
            <a href="{{ route('workflows.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Create Workflow') }}
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Trigger') }}</th>
                    <th>{{ __('Steps') }}</th>
                    <th>{{ __('Runs') }}</th>
                    <th>{{ __('Last Run') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($workflows as $workflow)
                <tr>
                    <td>
                        <a href="{{ route('workflows.edit', $workflow) }}" class="fw-bold">{{ $workflow->name }}</a>
                        @if($workflow->description)
                            <div class="text-secondary small">{{ \Illuminate\Support\Str::limit($workflow->description, 60) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-azure-lt">{{ $workflow->trigger_label }}</span>
                    </td>
                    <td class="text-secondary">{{ $workflow->steps_count }}</td>
                    <td class="text-secondary">{{ number_format($workflow->run_count ?? 0) }}</td>
                    <td class="text-secondary">
                        @if($workflow->last_run_at)
                            {{ $workflow->last_run_at->diffForHumans() }}
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input workflow-toggle" type="checkbox"
                                data-id="{{ $workflow->id }}"
                                {{ $workflow->is_active ? 'checked' : '' }}>
                        </label>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Actions') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('workflows.edit', $workflow) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"/><line x1="13.5" y1="6.5" x2="17.5" y2="10.5"/></svg>
                                    {{ __('Edit') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('workflows.logs', $workflow) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 12h8"/><path d="M4 18h8"/><path d="M4 6h16"/><path d="M16 12l4 4l-4 4"/></svg>
                                    {{ __('Run Logs') }}
                                </a>
                                <form method="POST" action="{{ route('workflows.destroy', $workflow) }}" onsubmit="return confirm('{{ __('Delete this workflow and all its steps?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-secondary py-4">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M7 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M7 8l0 8"/><path d="M9 18h6a2 2 0 0 0 2 -2v-5"/><path d="M14 14l3 -3l3 3"/></svg>
                        </div>
                        <p class="mb-2">{{ __('No workflows yet.') }}</p>
                        <p class="text-muted mb-3">{{ __('Automate your lead management with custom workflows.') }}</p>
                        <a href="{{ route('workflows.create') }}" class="btn btn-outline-primary btn-sm">{{ __('Create your first workflow') }}</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.workflow-toggle').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        var id = this.dataset.id;
        var checkbox = this;
        fetch('{{ url("/workflows") }}/' + id + '/toggle', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (!res.success) {
                checkbox.checked = !checkbox.checked;
            }
        }).catch(function() {
            checkbox.checked = !checkbox.checked;
        });
    });
});
</script>
@if(($businessMode ?? 'wholesale') === 'realestate')
<script>
fetch('{{ url("/workflows/templates") }}', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
.then(function(r) { return r.json(); })
.then(function(templates) {
    var dd = document.getElementById('workflow-templates-dropdown');
    dd.innerHTML = '';
    Object.keys(templates).forEach(function(key) {
        var a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.textContent = templates[key].name;
        a.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('{{ url("/workflows/create-from-template") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ template_key: key })
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.redirect) window.location.href = res.redirect;
            });
        });
        dd.appendChild(a);
    });
});
</script>
@endif
@endpush
@endsection
