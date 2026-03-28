@extends('layouts.app')

@section('title', __('Leads'))
@section('page-title', __('Leads'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('All Leads') }}</h3>
        <div class="card-actions">
            <a href="{{ route('leads.export', request()->query()) }}" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                {{ __('Export CSV') }}
            </a>
            <a href="{{ route('leads.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Lead') }}
            </a>
            <div class="btn-group ms-2" role="group" aria-label="{{ __('Table density') }}">
                <button type="button" class="btn btn-outline-secondary btn-sm density-toggle" data-density="comfortable" title="{{ __('Comfortable') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm density-toggle" data-density="compact" title="{{ __('Compact') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="5" x2="20" y2="5"/><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="13" x2="20" y2="13"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body border-bottom py-2" id="bulk-actions-bar" style="display: none;">
        <form method="POST" action="{{ route('leads.bulkAction') }}" id="bulk-form">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span id="selected-count" class="fw-bold text-muted">0</span>
                <span class="text-muted">{{ __('selected') }}</span>
                <div class="ms-3 d-flex gap-2">
                    @if(!auth()->user()->isAgent())
                    <div class="d-flex align-items-center gap-1">
                        <select name="agent_id" class="form-select form-select-sm" style="width:auto;">
                            <option value="">{{ __('Assign to...') }}</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" name="action" value="assign" class="btn btn-sm btn-outline-primary">{{ __('Assign') }}</button>
                    </div>
                    @endif
                    <div class="d-flex align-items-center gap-1">
                        <select name="status" class="form-select form-select-sm" style="width:auto;">
                            <option value="">{{ __('Set status...') }}</option>
                            @foreach(\App\Services\CustomFieldService::getOptions('lead_status') as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" name="action" value="status" class="btn btn-sm btn-outline-warning">{{ __('Update') }}</button>
                    </div>
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Delete selected leads?') }}')">{{ __('Delete') }}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('leads.index') }}" class="row g-2">
            <div class="col-md-3">
                <label for="filter-search" class="visually-hidden">{{ __('Search') }}</label>
                <input type="text" name="search" id="filter-search" class="form-control" placeholder="{{ __('Search name, phone, email...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="filter-source" class="visually-hidden">{{ __('Lead Source') }}</label>
                <select name="source" id="filter-source" class="form-select">
                    <option value="">{{ __('All Sources') }}</option>
                    @foreach(\App\Services\CustomFieldService::getOptions('lead_source') as $val => $label)
                        <option value="{{ $val }}" {{ request('source') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-status" class="visually-hidden">{{ __('Status') }}</label>
                <select name="status" id="filter-status" class="form-select">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(\App\Services\CustomFieldService::getOptions('lead_status') as $val => $label)
                        <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-temperature" class="visually-hidden">{{ __('Temperature') }}</label>
                <select name="temperature" id="filter-temperature" class="form-select">
                    <option value="">{{ __('All Temperatures') }}</option>
                    @foreach(['hot' => __('Hot'), 'warm' => __('Warm'), 'cold' => __('Cold')] as $val => $label)
                        <option value="{{ $val }}" {{ request('temperature') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(!auth()->user()->isAgent())
            <div class="col-md-2">
                <label for="filter-agent" class="visually-hidden">{{ __('Agent') }}</label>
                <select name="agent_id" id="filter-agent" class="form-select">
                    <option value="">{{ __('All Agents') }}</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <div class="btn-group" role="group">
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <a href="{{ route('leads.index', ['stacked' => 1]) }}" class="btn btn-sm {{ request('stacked') ? 'btn-purple' : 'btn-outline-purple' }}">{{ __('Stacked') }}</a>
                    @endif
                    <a href="{{ route('leads.index', ['dnc' => 1]) }}" class="btn btn-sm {{ request('dnc') ? 'btn-danger' : 'btn-outline-danger' }}">{{ __('DNC') }}</a>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
            </div>
            @if(request()->hasAny(['search', 'source', 'status', 'temperature', 'agent_id', 'stacked', 'dnc']))
            <div class="col-md-1">
                <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary w-100">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap" id="saved-views-bar">
            <span class="text-secondary small">{{ __('Saved Views:') }}</span>
            <div id="saved-views-list" class="d-flex gap-1 flex-wrap"></div>
            @if(request()->hasAny(['search', 'source', 'status', 'temperature', 'agent_id', 'stacked', 'dnc']))
            <button type="button" class="btn btn-sm btn-outline-primary" id="save-view-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/><circle cx="12" cy="14" r="2"/><polyline points="14 4 14 8 8 8"/></svg>
                {{ __('Save View') }}
            </button>
            @endif
        </div>
    </div>
    @php
        $currentSort = request('sort', '');
        $currentDir = request('direction', 'asc');
        $sortArrow = function($col) use ($currentSort, $currentDir) {
            if ($currentSort !== $col) return '';
            return $currentDir === 'asc'
                ? '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm ms-1" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M6 15l6-6l6 6"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm ms-1" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M6 9l6 6l6-6"/></svg>';
        };
        $sortUrl = function($col) use ($currentSort, $currentDir) {
            $dir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $dir]);
        };
    @endphp
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th class="w-1"><input type="checkbox" id="select-all" class="form-check-input" aria-label="{{ __('Select all leads') }}"></th>
                    <th class="w-1"><a href="{{ $sortUrl('id') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('ID') }}{!! $sortArrow('id') !!}</a></th>
                    <th><a href="{{ $sortUrl('first_name') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('Name') }}{!! $sortArrow('first_name') !!}</a></th>
                    <th>{{ __('Phone') }}</th>
                    <th><a href="{{ $sortUrl('lead_source') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('Source') }}{!! $sortArrow('lead_source') !!}</a></th>
                    <th><a href="{{ $sortUrl('status') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('Status') }}{!! $sortArrow('status') !!}</a></th>
                    <th><a href="{{ $sortUrl('temperature') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('Temp') }}{!! $sortArrow('temperature') !!}</a></th>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <th><a href="{{ $sortUrl('motivation_score') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('Score') }}{!! $sortArrow('motivation_score') !!}</a></th>
                    @endif
                    <th>{{ __('Agent') }}</th>
                    <th><a href="{{ $sortUrl('created_at') }}" class="text-reset text-decoration-none d-inline-flex align-items-center">{{ __('Date Added') }}{!! $sortArrow('created_at') !!}</a></th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                <tr>
                    <td><input type="checkbox" class="form-check-input lead-checkbox" value="{{ $lead->id }}" aria-label="{{ __('Select') }} {{ $lead->full_name }}"></td>
                    <td class="text-secondary">{{ $lead->id }}</td>
                    <td>
                        <a href="{{ route('leads.show', $lead) }}">{{ $lead->full_name }}</a>
                        @if($lead->do_not_contact)
                            <span class="badge bg-red-lt ms-1">{{ __('DNC') }}</span>
                        @endif
                        @if(($businessMode ?? 'wholesale') === 'wholesale' && ($lead->lists_count ?? 0) >= 3)
                            <span class="badge bg-purple-lt ms-1">{{ __('Stacked') }}</span>
                        @endif
                    </td>
                    <td class="text-secondary">@if($lead->phone)<a href="tel:{{ $lead->phone }}" class="text-reset text-decoration-none">{{ $lead->phone }}</a>@else - @endif</td>
                    <td>
                        @php
                            $sourceColors = [
                                'referral' => 'bg-green-lt',
                                'open_house' => 'bg-teal-lt',
                                'sign_call' => 'bg-orange-lt',
                                'zillow' => 'bg-blue-lt',
                                'realtor_com' => 'bg-red-lt',
                                'sphere' => 'bg-cyan-lt',
                                'past_client' => 'bg-purple-lt',
                                'social_media' => 'bg-pink-lt',
                                'website' => 'bg-indigo-lt',
                                'driving_for_dollars' => 'bg-orange-lt',
                                'direct_mail' => 'bg-green-lt',
                                'cold_calling' => 'bg-cyan-lt',
                                'bandit_sign' => 'bg-yellow-lt',
                                'mls' => 'bg-red-lt',
                                'auction' => 'bg-purple-lt',
                                'other' => 'bg-secondary-lt',
                            ];
                        @endphp
                        <span class="badge {{ $sourceColors[$lead->lead_source] ?? 'bg-blue-lt' }}">{{ __(ucwords(str_replace('_', ' ', $lead->lead_source))) }}</span>
                    </td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-lead-id="{{ $lead->id }}" aria-label="{{ __('Status for') }} {{ $lead->full_name }}" style="width: auto; min-width: 120px;">
                            @foreach(\App\Services\CustomFieldService::getOptions('lead_status') as $val => $label)
                                <option value="{{ $val }}" {{ $lead->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        @php
                            $tempColors = ['hot' => 'bg-red-lt', 'warm' => 'bg-yellow-lt', 'cold' => 'bg-azure-lt'];
                        @endphp
                        <span class="badge {{ $tempColors[$lead->temperature] ?? 'bg-secondary-lt' }}">@if($lead->temperature === 'hot')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z"/></svg>@elseif($lead->temperature === 'warm')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8-9v1m8 8h1m-9 8v1m-6.4-15.4l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>@elseif($lead->temperature === 'cold')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4l2 1l2-1"/><path d="M12 2v6.5l3 1.72"/><path d="M17.928 6.268l.134 2.232l1.866 1.232"/><path d="M20.66 7l-5.629 3.25l.01 3.458"/><path d="M19.928 14.268l-1.866 1.232l-.134 2.232"/><path d="M20.66 17l-5.629-3.25l-2.99 1.738"/><path d="M14 20l-2-1l-2 1"/><path d="M12 22v-6.5l-3-1.72"/><path d="M6.072 17.732l-.134-2.232l-1.866-1.232"/><path d="M3.34 17l5.629-3.25l-.01-3.458"/><path d="M4.072 9.732l1.866-1.232l.134-2.232"/><path d="M3.34 7l5.629 3.25l2.99-1.738"/></svg>@endif {{ __(ucfirst($lead->temperature)) }}</span>
                    </td>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <td>
                        @php $ms = $lead->motivation_score ?? 0; @endphp
                        <span class="{{ $ms >= 70 ? 'badge bg-green-lt' : ($ms >= 40 ? 'badge bg-yellow-lt' : 'text-secondary') }}" title="{{ __('System') }}">@if($ms >= 70)<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg> @elseif($ms >= 40)<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12h14"/></svg> @endif{{ $ms }}</span>
                        @if($lead->ai_motivation_score !== null)
                            <span class="badge bg-purple-lt ms-1" title="{{ __('AI') }}">{{ $lead->ai_motivation_score }}</span>
                        @endif
                    </td>
                    @endif
                    <td class="text-secondary">{{ $lead->agent->name ?? '-' }}</td>
                    <td class="text-secondary">{{ $lead->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Actions for') }} {{ $lead->full_name }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('leads.show', $lead) }}">{{ __('View') }}</a>
                                <a class="dropdown-item" href="{{ route('leads.edit', $lead) }}">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('{{ __('Delete this lead?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-4">
                        @if(request()->hasAny(['search', 'source', 'status', 'temperature', 'agent_id', 'stacked', 'dnc']))
                            <div class="text-secondary mb-2">{{ __('No leads match your current filters.') }}</div>
                            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                                {{ __('Clear Filters') }}
                            </a>
                        @else
                            <div class="mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-secondary mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                            </div>
                            <div class="text-secondary mb-2">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('No leads yet. Add your first inquiry or client!') : __('No leads yet. Start building your pipeline!') }}</div>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary">{{ __('Add Lead') }}</a>
                                @if(($businessMode ?? 'wholesale') === 'wholesale')
                                <a href="{{ route('lists.create') }}" class="btn btn-sm btn-outline-secondary">{{ __('Import CSV') }}</a>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $leads->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $leads->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $leads->total() }}</span> {{ __('entries') }}</p>
        <div class="ms-auto">
            {{ $leads->withQueryString()->links() }}
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080;" id="toast-container"></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const bulkBar = document.getElementById('bulk-actions-bar');
    const bulkForm = document.getElementById('bulk-form');
    const countSpan = document.getElementById('selected-count');

    function getCheckboxes() {
        return document.querySelectorAll('.lead-checkbox');
    }

    function updateBulkBar() {
        const checked = document.querySelectorAll('.lead-checkbox:checked');
        countSpan.textContent = checked.length;
        bulkBar.style.display = checked.length > 0 ? '' : 'none';

        // Update hidden inputs
        bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            bulkForm.appendChild(input);
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            getCheckboxes().forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('lead-checkbox')) {
            updateBulkBar();
            if (!e.target.checked && selectAll) selectAll.checked = false;
        }
    });
});

function showToast(message, type) {
    var container = document.getElementById('toast-container');
    var id = 'toast-' + Date.now();
    var bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    container.insertAdjacentHTML('beforeend',
        '<div id="' + id + '" class="toast align-items-center text-white ' + bgClass + ' border-0" role="alert">' +
        '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
        '</div></div>'
    );
    var el = document.getElementById(id);
    var toast = new bootstrap.Toast(el, { delay: 3000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', function() { el.remove(); });
}

document.querySelectorAll('.status-select').forEach(function(select) {
    select.addEventListener('change', function() {
        var leadId = this.dataset.leadId;
        var status = this.value;
        var selectEl = this;
        fetch('{{ url("/leads") }}/' + leadId + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: status })
        }).then(function(r) {
            if (r.ok) {
                showToast('{{ __("Status updated successfully.") }}', 'success');
            } else {
                showToast('{{ __("Failed to update status.") }}', 'error');
            }
        }).catch(function() {
            showToast('{{ __("Network error. Please try again.") }}', 'error');
        });
    });
});

// Saved views (localStorage)
(function() {
    var STORAGE_KEY = 'insulacrm_saved_views_leads';
    var list = document.getElementById('saved-views-list');
    var saveBtn = document.getElementById('save-view-btn');
    var bar = document.getElementById('saved-views-bar');

    function getViews() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch(e) { return []; }
    }

    function saveViews(views) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(views));
    }

    function renderViews() {
        var views = getViews();
        if (!views.length && !saveBtn) {
            bar.style.display = 'none';
            return;
        }
        bar.style.display = '';
        list.innerHTML = '';
        views.forEach(function(view, i) {
            var pill = document.createElement('a');
            pill.href = view.url;
            pill.className = 'btn btn-sm btn-pill btn-outline-secondary';
            pill.innerHTML = view.name + ' <span class="ms-1 btn-close-icon" data-view-index="' + i + '" style="cursor:pointer;opacity:0.5;font-size:10px;">&times;</span>';
            pill.querySelector('.btn-close-icon').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var views = getViews();
                views.splice(i, 1);
                saveViews(views);
                renderViews();
            });
            list.appendChild(pill);
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            var name = prompt('{{ __("Name this view:") }}');
            if (!name || !name.trim()) return;
            var views = getViews();
            views.push({ name: name.trim(), url: window.location.href });
            saveViews(views);
            renderViews();
        });
    }

    renderViews();
})();

// Table density toggle
(function() {
    var STORAGE_KEY = 'insulacrm_table_density';
    var table = document.querySelector('.table.card-table');
    if (!table) return;

    var savedDensity = localStorage.getItem(STORAGE_KEY) || 'comfortable';

    function applyDensity(density) {
        if (density === 'compact') {
            table.classList.add('table-sm');
        } else {
            table.classList.remove('table-sm');
        }
        // Update button active states
        document.querySelectorAll('.density-toggle').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.density === density);
        });
        localStorage.setItem(STORAGE_KEY, density);
    }

    // Apply saved preference
    applyDensity(savedDensity);

    // Handle clicks
    document.querySelectorAll('.density-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            applyDensity(this.dataset.density);
        });
    });
})();
</script>
@endpush
@endsection
