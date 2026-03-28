@extends('layouts.app')

@section('title', __('Lead Board'))
@section('page-title', __('Lead Kanban Board'))

@push('styles')
<style>
    .kanban-wrapper { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 16px; min-height: 70vh; }
    .kanban-column { min-width: 260px; max-width: 280px; flex-shrink: 0; background: #f4f6fa; border-radius: 8px; display: flex; flex-direction: column; }
    .kanban-column-header { padding: 10px 12px; font-weight: 600; font-size: 13px; border-bottom: 2px solid #e6e7e9; display: flex; justify-content: space-between; align-items: center; }
    .kanban-column-body { flex: 1; padding: 8px; overflow-y: auto; min-height: 80px; }
    .kanban-column.drag-over { background: #e8f0fe; box-shadow: inset 0 0 0 2px #206bc4; }
    .kanban-card { background: #fff; border: 1px solid #e6e7e9; border-radius: 6px; padding: 10px; margin-bottom: 8px; cursor: grab; transition: opacity 0.15s, transform 0.15s; }
    .kanban-card.dragging { opacity: 0.4; transform: rotate(1deg); }
    .kanban-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .kanban-card .lead-name { font-weight: 600; font-size: 13px; }
    .kanban-card .lead-meta { font-size: 11px; color: #6c757d; }
    .kanban-card { position: relative; }
    .kanban-card .move-dropdown { position: absolute; bottom: 4px; right: 4px; }
    .kanban-card .move-dropdown .btn { padding: 2px 4px; line-height: 1; }
</style>
@endpush

@section('content')
<div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    @if(auth()->user()->isAdmin())
    <label for="agent-filter" class="visually-hidden">{{ __('Filter by agent') }}</label>
    <select class="form-select form-select-sm" style="max-width:200px;" id="agent-filter">
        <option value="">{{ __('All Agents') }}</option>
        @foreach($agents as $agent)
        <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
        @endforeach
    </select>
    @endif
    <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Table View') }}</a>
</div>

<div class="kanban-wrapper">
    @foreach($statuses as $slug => $label)
    @php $statusLeads = $leads->get($slug, collect()); @endphp
    <div class="kanban-column" data-status="{{ $slug }}" aria-label="{{ __('Column:') }} {{ $label }}">
        <div class="kanban-column-header">
            <span>{{ $label }}</span>
            <span class="badge bg-secondary-lt kanban-count">{{ $statusLeads->count() }}</span>
        </div>
        <div class="kanban-column-body">
            @foreach($statusLeads as $lead)
            <div class="kanban-card" draggable="true" data-lead-id="{{ $lead->id }}" aria-roledescription="draggable item">
                <div class="lead-name">
                    <a href="{{ route('leads.show', $lead) }}" class="text-reset">{{ $lead->full_name }}</a>
                </div>
                <div class="lead-meta">
                    @if($lead->phone) <a href="tel:{{ $lead->phone }}" class="text-reset text-decoration-none">{{ $lead->phone }}</a> @endif
                    @if($lead->agent) &middot; {{ $lead->agent->name }} @endif
                </div>
                @if($lead->tags->count())
                <div class="mt-1">
                    @foreach($lead->tags as $tag)
                    <span class="badge bg-{{ $tag->color }}-lt" style="font-size:10px;">{{ $tag->name }}</span>
                    @endforeach
                </div>
                @endif
                @if($lead->temperature)
                <span class="badge bg-{{ $lead->temperature === 'hot' ? 'red' : ($lead->temperature === 'warm' ? 'orange' : 'blue') }}-lt mt-1" style="font-size:10px;">@if($lead->temperature === 'hot')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z"/></svg>@elseif($lead->temperature === 'warm')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8-9v1m8 8h1m-9 8v1m-6.4-15.4l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>@elseif($lead->temperature === 'cold')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4l2 1l2-1"/><path d="M12 2v6.5l3 1.72"/><path d="M17.928 6.268l.134 2.232l1.866 1.232"/><path d="M20.66 7l-5.629 3.25l.01 3.458"/><path d="M19.928 14.268l-1.866 1.232l-.134 2.232"/><path d="M20.66 17l-5.629-3.25l-2.99 1.738"/><path d="M14 20l-2-1l-2 1"/><path d="M12 22v-6.5l-3-1.72"/><path d="M6.072 17.732l-.134-2.232l-1.866-1.232"/><path d="M3.34 17l5.629-3.25l-.01-3.458"/><path d="M4.072 9.732l1.866-1.232l.134-2.232"/><path d="M3.34 7l5.629 3.25l2.99-1.738"/></svg>@endif {{ __(ucfirst($lead->temperature)) }}</span>
                @endif
                <div class="dropdown move-dropdown">
                    <button class="btn btn-sm btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Move to') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12h14"/><path d="M15 16l4 -4"/><path d="M15 8l4 4"/></svg>
                        <span class="visually-hidden">{{ __('Move to') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach($statuses as $moveSlug => $moveLabel)
                            @if($moveSlug !== $slug)
                                <a href="#" class="dropdown-item move-lead-btn" data-lead-id="{{ $lead->id }}" data-status="{{ $moveSlug }}">{{ $moveLabel }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
            @if($statusLeads->isEmpty())
            <div class="text-center text-muted small py-3 empty-msg">{{ __('No leads') }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script>
(function() {
    let dragLeadId = null;
    let dragSourceCol = null;

    document.querySelectorAll('.kanban-card').forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            dragLeadId = this.dataset.leadId;
            dragSourceCol = this.closest('.kanban-column');
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            document.querySelectorAll('.kanban-column').forEach(c => c.classList.remove('drag-over'));
        });
    });

    document.querySelectorAll('.kanban-column').forEach(function(col) {
        col.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        col.addEventListener('dragleave', function(e) {
            if (!this.contains(e.relatedTarget)) this.classList.remove('drag-over');
        });
        col.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            if (!dragLeadId) return;

            const newStatus = this.dataset.status;
            const card = document.querySelector('.kanban-card[data-lead-id="' + dragLeadId + '"]');
            const body = this.querySelector('.kanban-column-body');
            const empty = body.querySelector('.empty-msg');
            if (empty) empty.remove();

            body.appendChild(card);

            // Update counts
            updateCounts();

            // Check if source column now empty
            const srcBody = dragSourceCol.querySelector('.kanban-column-body');
            if (!srcBody.querySelector('.kanban-card')) {
                srcBody.innerHTML = '<div class="text-center text-muted small py-3 empty-msg">{{ __("No leads") }}</div>';
            }

            // AJAX update
            fetch('{{ url("/leads") }}/' + dragLeadId + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus })
            }).then(r => {
                if (!r.ok) {
                    // Rollback
                    dragSourceCol.querySelector('.kanban-column-body').appendChild(card);
                    const em = body.querySelector('.empty-msg');
                    if (!body.querySelector('.kanban-card') && !em) {
                        body.innerHTML = '<div class="text-center text-muted small py-3 empty-msg">{{ __("No leads") }}</div>';
                    }
                    updateCounts();
                    showToast('{{ __("Failed to update lead status") }}', 'danger');
                }
            });

            dragLeadId = null;
            dragSourceCol = null;
        });
    });

    function updateCounts() {
        document.querySelectorAll('.kanban-column').forEach(function(col) {
            const count = col.querySelectorAll('.kanban-card').length;
            col.querySelector('.kanban-count').textContent = count;
        });
    }

    function showToast(msg, type) {
        const toast = document.createElement('div');
        toast.className = 'alert alert-' + (type || 'info') + ' position-fixed bottom-0 end-0 m-3';
        toast.style.zIndex = 9999;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Move-to dropdown buttons
    document.querySelectorAll('.move-lead-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var leadId = this.dataset.leadId;
            var status = this.dataset.status;
            fetch('{{ url("/leads") }}/' + leadId + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: status })
            }).then(function(r) {
                if (r.ok) location.reload();
                else showToast('{{ __("Failed to update lead status") }}', 'danger');
            });
        });
    });

    // Agent filter
    const agentFilter = document.getElementById('agent-filter');
    if (agentFilter) {
        agentFilter.addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value) url.searchParams.set('agent_id', this.value);
            else url.searchParams.delete('agent_id');
            window.location = url;
        });
    }
})();
</script>
@endpush
@endsection
