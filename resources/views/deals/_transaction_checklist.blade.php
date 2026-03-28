@php
    $checklistItems = $deal->checklistItems ?? collect();
    $completedCount = $checklistItems->whereIn('status', ['completed', 'waived'])->count();
    $totalCount = $checklistItems->count();
    $progressPct = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
@endphp

<div class="card mb-3" id="checklist-card">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-list-check me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3.5 5.5l1.5 1.5l2.5 -2.5"/><path d="M3.5 11.5l1.5 1.5l2.5 -2.5"/><path d="M3.5 17.5l1.5 1.5l2.5 -2.5"/><line x1="11" y1="6" x2="20" y2="6"/><line x1="11" y1="12" x2="20" y2="12"/><line x1="11" y1="18" x2="20" y2="18"/></svg>
            {{ __('Transaction Checklist') }}
        </h3>
        @if($totalCount > 0)
        <div class="card-actions">
            <span class="badge bg-{{ $completedCount === $totalCount ? 'green' : 'blue' }}-lt" id="checklist-progress-badge">
                {{ $completedCount }}/{{ $totalCount }} {{ __('complete') }}
            </span>
        </div>
        @endif
    </div>
    <div class="card-body">
        @if($totalCount === 0)
            <div class="text-center py-3">
                <p class="text-secondary mb-3">{{ __('No transaction checklist yet.') }}</p>
                <form method="POST" action="{{ route('deals.storeChecklist', $deal) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('Create Checklist') }}
                    </button>
                </form>
            </div>
        @else
            <!-- Progress Bar -->
            <div class="progress mb-3" id="checklist-progress-bar">
                <div class="progress-bar bg-green" style="width: {{ $progressPct }}%" role="progressbar" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                    {{ $progressPct }}%
                </div>
            </div>

            <!-- Checklist Items -->
            <div id="checklist-items">
                @foreach($checklistItems as $item)
                <div class="checklist-row border rounded p-2 mb-2" data-item-id="{{ $item->id }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center flex-grow-1">
                            <!-- Status Icon -->
                            @php
                                $statusColors = [
                                    'pending' => 'bg-blue',
                                    'in_progress' => 'bg-yellow',
                                    'completed' => 'bg-green',
                                    'waived' => 'bg-secondary',
                                    'failed' => 'bg-red',
                                ];
                                $statusColor = $statusColors[$item->status] ?? 'bg-secondary';
                            @endphp
                            <span class="status-dot status-dot-animated d-block {{ $statusColor }} me-2" title="{{ __(App\Models\TransactionChecklist::STATUSES[$item->status] ?? $item->status) }}"></span>

                            <!-- Label -->
                            <span class="fw-medium {{ $item->status === 'completed' ? 'text-decoration-line-through text-secondary' : '' }}" id="checklist-label-{{ $item->id }}">
                                {{ __($item->label) }}
                            </span>

                            <!-- Deadline -->
                            @if($item->deadline)
                                @php
                                    $daysUntil = now()->startOfDay()->diffInDays($item->deadline, false);
                                    $deadlineClass = '';
                                    if ($item->is_overdue) {
                                        $deadlineClass = 'text-danger fw-bold';
                                    } elseif ($daysUntil <= 3 && $daysUntil >= 0 && !in_array($item->status, ['completed', 'waived'])) {
                                        $deadlineClass = 'text-warning';
                                    }
                                @endphp
                                <span class="badge bg-{{ $item->is_overdue ? 'red' : ($daysUntil <= 3 && $daysUntil >= 0 && !in_array($item->status, ['completed', 'waived']) ? 'yellow' : 'secondary') }}-lt ms-2 {{ $deadlineClass }}">
                                    {{ $item->deadline->format('M d') }}
                                    @if($item->is_overdue)
                                        ({{ __('overdue') }})
                                    @endif
                                </span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <!-- Status Dropdown -->
                            <select class="form-select form-select-sm checklist-status-select" data-item-id="{{ $item->id }}" style="width: auto; min-width: 120px;">
                                @foreach(\App\Models\TransactionChecklist::STATUSES as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" {{ $item->status === $statusKey ? 'selected' : '' }}>{{ __($statusLabel) }}</option>
                                @endforeach
                            </select>

                            <!-- Deadline Input -->
                            <input type="date" class="form-control form-control-sm checklist-deadline-input" data-item-id="{{ $item->id }}" value="{{ $item->deadline ? $item->deadline->format('Y-m-d') : '' }}" style="width: 140px;">

                            <!-- Notes Toggle -->
                            <button type="button" class="btn btn-sm btn-ghost-secondary checklist-notes-toggle" data-item-id="{{ $item->id }}" title="{{ __('Notes') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="9" y1="9" x2="10" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                            </button>

                            <!-- Delete Button -->
                            <button type="button" class="btn btn-sm btn-ghost-danger checklist-delete-btn" data-item-id="{{ $item->id }}" title="{{ __('Remove') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Notes -->
                    <div class="checklist-notes mt-2" id="checklist-notes-{{ $item->id }}" style="display: none;">
                        <textarea class="form-control form-control-sm checklist-notes-input" data-item-id="{{ $item->id }}" rows="2" placeholder="{{ __('Add notes...') }}">{{ $item->notes }}</textarea>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1 checklist-notes-save" data-item-id="{{ $item->id }}">{{ __('Save Notes') }}</button>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Add Item Row -->
            <div class="border rounded p-2 mt-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <input type="text" class="form-control form-control-sm" id="checklist-new-label" placeholder="{{ __('New checklist item...') }}" maxlength="255">
                    <input type="date" class="form-control form-control-sm" id="checklist-new-deadline" style="width: 150px;">
                    <button type="button" class="btn btn-sm btn-primary" id="checklist-add-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('Add') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

@if($totalCount > 0)
@push('scripts')
<script>
(function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };

    // Status change
    document.querySelectorAll('.checklist-status-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var itemId = this.getAttribute('data-item-id');
            fetch('{{ url("/checklist") }}/' + itemId, {
                method: 'PATCH',
                headers: headers,
                body: JSON.stringify({ status: this.value })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
            });
        });
    });

    // Deadline change
    document.querySelectorAll('.checklist-deadline-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var itemId = this.getAttribute('data-item-id');
            fetch('{{ url("/checklist") }}/' + itemId, {
                method: 'PATCH',
                headers: headers,
                body: JSON.stringify({ deadline: this.value || null })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
            });
        });
    });

    // Notes toggle
    document.querySelectorAll('.checklist-notes-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var itemId = this.getAttribute('data-item-id');
            var notesEl = document.getElementById('checklist-notes-' + itemId);
            notesEl.style.display = notesEl.style.display === 'none' ? 'block' : 'none';
        });
    });

    // Save notes
    document.querySelectorAll('.checklist-notes-save').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var itemId = this.getAttribute('data-item-id');
            var notes = document.querySelector('.checklist-notes-input[data-item-id="' + itemId + '"]').value;
            var saveBtn = this;
            saveBtn.disabled = true;
            saveBtn.textContent = '{{ __('Saving...') }}';
            fetch('{{ url("/checklist") }}/' + itemId, {
                method: 'PATCH',
                headers: headers,
                body: JSON.stringify({ notes: notes })
            }).then(function(r) { return r.json(); }).then(function(data) {
                saveBtn.disabled = false;
                saveBtn.textContent = data.success ? '{{ __('Saved!') }}' : '{{ __('Save Notes') }}';
                if (data.success) {
                    setTimeout(function() { saveBtn.textContent = '{{ __('Save Notes') }}'; }, 2000);
                }
            }).catch(function() {
                saveBtn.disabled = false;
                saveBtn.textContent = '{{ __('Save Notes') }}';
            });
        });
    });

    // Delete item
    document.querySelectorAll('.checklist-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('{{ __('Remove this checklist item?') }}')) return;
            var itemId = this.getAttribute('data-item-id');
            fetch('{{ url("/checklist") }}/' + itemId, {
                method: 'DELETE',
                headers: headers
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
            });
        });
    });

    // Add item
    var addBtn = document.getElementById('checklist-add-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var label = document.getElementById('checklist-new-label').value.trim();
            if (!label) return;
            var deadline = document.getElementById('checklist-new-deadline').value || null;
            addBtn.disabled = true;
            fetch('{{ url("/pipeline/" . $deal->id . "/checklist/add") }}', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({ label: label, deadline: deadline })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
                addBtn.disabled = false;
            }).catch(function() {
                addBtn.disabled = false;
            });
        });
    }
})();
</script>
@endpush
@endif
