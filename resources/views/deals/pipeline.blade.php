@extends('layouts.app')

@section('title', ($businessMode ?? 'wholesale') === 'realestate' ? __('Transactions') : __('Pipeline'))
@section('page-title', ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Pipeline') : __('Deal Pipeline'))

@push('styles')
<style>
    /* ── Filters ──────────────────────────────────────── */
    .pipeline-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
        align-items: center;
    }
    .pipeline-filters .form-control,
    .pipeline-filters .form-select {
        max-width: 220px;
    }

    /* ── Stage rows (accordion) ───────────────────────── */
    .stage-row {
        border: 1px solid #e6e7e9;
        border-radius: 8px;
        margin-bottom: 8px;
        background: #fff;
        transition: background 0.15s, box-shadow 0.15s;
    }
    .stage-row.drag-over {
        background: #e8f0fe;
        box-shadow: inset 0 0 0 2px #206bc4;
    }
    .stage-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        cursor: pointer;
        user-select: none;
        gap: 12px;
    }
    .stage-header:hover {
        background: #f8f9fa;
        border-radius: 8px;
    }
    .stage-chevron {
        transition: transform 0.2s;
        color: #adb5bd;
        flex-shrink: 0;
    }
    .stage-row.expanded .stage-chevron {
        transform: rotate(90deg);
    }
    .stage-name {
        font-weight: 600;
        font-size: 0.925rem;
        flex-shrink: 0;
    }
    .stage-stats {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-left: auto;
        font-size: 0.8rem;
        color: #667085;
        flex-shrink: 0;
    }
    .stage-stats .badge {
        font-size: 0.75rem;
    }

    /* ── Expanded card grid ────────────────────────────── */
    .stage-cards {
        display: none;
        padding: 4px 16px 16px;
    }
    .stage-row.expanded .stage-cards {
        display: block;
    }
    .stage-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 10px;
    }
    .stage-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 20px;
        color: #adb5bd;
        font-size: 0.85rem;
    }

    /* ── Deal cards ────────────────────────────────────── */
    .deal-card {
        background: #f8f9fa;
        border: 1px solid #e6e7e9;
        border-radius: 6px;
        padding: 12px;
        position: relative;
        transition: box-shadow 0.2s, opacity 0.2s;
    }
    .deal-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        background: #fff;
    }
    .deal-card.dragging {
        opacity: 0.4;
        transform: rotate(1deg);
    }
    .deal-card-drag {
        cursor: grab;
        color: #adb5bd;
        position: absolute;
        top: 10px;
        right: 8px;
        padding: 2px;
        line-height: 1;
    }
    .deal-card-drag:hover { color: #667085; }
    .deal-card-drag:active { cursor: grabbing; }
    .deal-card .move-dropdown { position: absolute; bottom: 4px; right: 4px; }
    .deal-card .move-dropdown .btn { padding: 2px 4px; line-height: 1; }
    .deal-card-body {
        cursor: pointer;
    }
    .deal-card-title {
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 4px;
        padding-right: 20px;
    }
    .deal-card-meta {
        font-size: 0.75rem;
        color: #667085;
        line-height: 1.5;
    }

    /* ── Slide-over ────────────────────────────────────── */
    .slide-over {
        position: fixed;
        top: 0;
        right: -450px;
        width: 450px;
        height: 100%;
        background: #fff;
        box-shadow: -4px 0 20px rgba(0,0,0,0.15);
        z-index: 1050;
        transition: right 0.3s;
        overflow-y: auto;
        padding: 24px;
    }
    .slide-over.open { right: 0; }
    .slide-over-backdrop {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.3);
        z-index: 1040;
        display: none;
    }
    .slide-over-backdrop.open { display: block; }
    .slide-over .badge { color: #fff; }

    /* ── Toast ─────────────────────────────────────────── */
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1060;
    }
    .pipeline-toast {
        padding: 12px 20px;
        border-radius: 6px;
        color: #fff;
        font-size: 0.875rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        opacity: 0;
        transition: opacity 0.3s;
        margin-top: 8px;
    }
    .pipeline-toast.show { opacity: 1; }
    .pipeline-toast.success { background: #2fb344; }
    .pipeline-toast.error { background: #d63939; }

    /* ── Quick Edit ────────────────────────────────────── */
    .deal-card .quick-edit-btn {
        position: absolute;
        top: 4px;
        right: 28px;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 2;
    }
    .deal-card:hover .quick-edit-btn {
        opacity: 1;
    }
    .quick-edit-form .form-control-sm {
        font-size: 0.8rem;
        padding: 0.2rem 0.4rem;
    }
    .quick-edit-form .form-label-sm {
        font-size: 0.75rem;
        color: #667085;
    }

    /* ── Stage SLA warnings ──────────────────────────── */
    .deal-card.deal-age-warning {
        border-left: 4px solid #f59f00;
        background: #fffdf5;
    }
    .deal-card.deal-age-critical {
        border-left: 4px solid #d63939;
        background: #fff0f0;
    }
    [data-bs-theme="dark"] .deal-card.deal-age-warning {
        background: #2a2410;
    }
    [data-bs-theme="dark"] .deal-card.deal-age-critical {
        background: #2c1a1a;
    }
    .deal-age-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 4px;
    }
    .deal-age-badge.warning {
        background: #fff3cd;
        color: #856404;
    }
    .deal-age-badge.warning::before {
        content: '\26A0';
        font-size: 0.65rem;
    }
    .deal-age-badge.critical {
        background: #f8d7da;
        color: #842029;
    }
    .deal-age-badge.critical::before {
        content: '\1F534';
        font-size: 0.55rem;
    }
    [data-bs-theme="dark"] .deal-age-badge.warning {
        background: #332701;
        color: #f59f00;
    }
    [data-bs-theme="dark"] .deal-age-badge.critical {
        background: #2c0b0e;
        color: #e35d6a;
    }
</style>
@endpush

@section('content')
{{-- Filters --}}
<div class="pipeline-filters">
    <div class="input-icon" style="max-width: 220px;">
        <span class="input-icon-addon">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
        </span>
        <label for="pipeline-search" class="visually-hidden">{{ __('Search deals') }}</label>
        <input type="text" id="pipeline-search" class="form-control" placeholder="{{ __('Search deals...') }}" value="{{ request('search') }}">
    </div>
    @if(auth()->user()->isAdmin() && $agents->count() > 1)
    <label for="pipeline-agent-filter" class="visually-hidden">{{ __('Filter by agent') }}</label>
    <select id="pipeline-agent-filter" class="form-select">
        <option value="">{{ __('All Agents') }}</option>
        @foreach($agents as $agent)
            <option value="{{ $agent->id }}" {{ request('agent') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
        @endforeach
    </select>
    @endif
    <a href="{{ route('deals.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm ms-auto">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
        {{ __('Export CSV') }}
    </a>
    <div class="text-secondary" style="font-size: 0.8rem;">
        @php $totalDeals = collect($deals)->flatten()->count(); @endphp
        {{ $totalDeals }} {{ Str::plural('deal', $totalDeals) }} {{ __('in pipeline') }}
    </div>
</div>

<x-saved-views-bar entity-type="deals" />

{{-- Accordion Pipeline --}}
<div id="pipeline">
    @foreach($stages as $stageKey => $stageLabel)
    @php
        $stageDeals = $deals[$stageKey] ?? collect();
        $stageTotal = $stageDeals->sum('contract_price');
        $stageFees = $stageDeals->sum($businessMode === 'realestate' ? 'total_commission' : 'assignment_fee');
    @endphp
    <div class="stage-row" data-stage="{{ $stageKey }}">
        <div class="stage-header">
            <svg class="stage-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18"/></svg>
            <span class="stage-name">{{ $stageLabel }}</span>
            <div class="stage-stats">
                @if($stageTotal > 0)
                    <span>{{ Fmt::currency($stageTotal, 0) }}</span>
                @endif
                @if($stageFees > 0)
                    <span class="text-success">{{ Fmt::currency($stageFees, 0) }} {{ $modeTerms['fee_label'] }}</span>
                @endif
                <span class="badge bg-secondary-lt">{{ $stageDeals->count() }}</span>
                @php
                    $staleDealCount = $stageDeals->filter(function($d) {
                        return $d->stage_changed_at && (int) now()->diffInDays($d->stage_changed_at, true) > 5;
                    })->count();
                @endphp
                @if($staleDealCount > 0)
                <span class="badge bg-{{ $staleDealCount > 3 ? 'red' : 'yellow' }}-lt" title="{{ __(':count deal(s) need attention', ['count' => $staleDealCount]) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 001.84-2.75l-7.1-12.25a2 2 0 00-3.5 0l-7.1 12.25a2 2 0 001.75 2.75"/></svg>
                    {{ $staleDealCount }}
                </span>
                @endif
            </div>
        </div>
        <div class="stage-cards">
            <div class="stage-cards-grid" data-stage="{{ $stageKey }}">
                @forelse($stageDeals as $deal)
                @php $daysInStage = $deal->stage_changed_at ? (int) now()->diffInDays($deal->stage_changed_at, true) : 0; @endphp
                <div class="deal-card {{ $daysInStage > 10 ? 'deal-age-critical' : ($daysInStage > 5 ? 'deal-age-warning' : '') }}" data-deal-id="{{ $deal->id }}">
                    <button class="btn btn-ghost-secondary btn-icon btn-sm quick-edit-btn" data-deal-id="{{ $deal->id }}" title="{{ __('Quick Edit') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97l-8.415 8.385v3h3l8.385-8.415z"/><path d="M16 5l3 3"/></svg>
                    </button>
                    <div class="deal-card-drag" draggable="true" title="{{ __('Drag to move stage') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                    </div>
                    <div class="deal-card-body" data-deal-id="{{ $deal->id }}">
                        <div class="deal-card-title">{{ $deal->lead->full_name ?? $deal->title }}</div>
                        <div class="deal-card-meta">
                            @if($deal->lead && $deal->lead->property)
                                {{ $deal->lead->property->address ?? '' }}<br>
                            @endif
                            @if($deal->contract_price)
                                {{ __('Contract:') }} <span data-field="contract_price" data-raw-value="{{ $deal->contract_price }}">{{ Fmt::currency($deal->contract_price, 0) }}</span>
                            @endif
                            @if($businessMode === 'wholesale' && $deal->assignment_fee)
                                &middot; {{ __('Fee:') }} <span data-field="assignment_fee" data-raw-value="{{ $deal->assignment_fee }}">{{ Fmt::currency($deal->assignment_fee, 0) }}</span>
                            @elseif($businessMode === 'realestate' && $deal->total_commission)
                                &middot; {{ __('Comm:') }} <span data-field="total_commission" data-raw-value="{{ $deal->total_commission }}">{{ Fmt::currency($deal->total_commission, 0) }}</span>
                            @endif
                            @if($deal->contract_price || ($businessMode === 'wholesale' ? $deal->assignment_fee : $deal->total_commission))<br>@endif
                            @if($deal->stage === 'under_contract' && $deal->due_diligence_end_date)
                                <span class="badge {{ $deal->is_due_diligence_urgent ? 'bg-red-lt' : 'bg-cyan-lt' }} mt-1" style="color:#fff;">
                                    {{ __('DD:') }} {{ $deal->due_diligence_days_remaining }}{{ __('d left') }}
                                </span><br>
                            @endif
                            <span>{{ $deal->agent->name ?? '' }}</span>
                            &middot; <span class="deal-age-badge {{ $daysInStage > 10 ? 'critical' : ($daysInStage > 5 ? 'warning' : '') }}">{{ $daysInStage }}{{ __('d in stage') }}</span>
                        </div>
                    </div>
                    <div class="dropdown move-dropdown">
                        <button class="btn btn-sm btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Move to') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12h14"/><path d="M15 16l4 -4"/><path d="M15 8l4 4"/></svg>
                            <span class="visually-hidden">{{ __('Move to') }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach($stages as $moveStageKey => $moveStageLabel)
                                @if($moveStageKey !== $stageKey)
                                    <a href="#" class="dropdown-item move-deal-btn" data-deal-id="{{ $deal->id }}" data-stage="{{ $moveStageKey }}">{{ $moveStageLabel }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @empty
                <div class="stage-empty">{{ __('No deals in this stage') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Slide-over Panel -->
<div class="slide-over-backdrop" id="dealBackdrop"></div>
<div class="slide-over" id="dealPanel">
    <div class="d-flex justify-content-between mb-3">
        <h3 id="panel-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Details') : __('Deal Details') }}</h3>
        <button class="btn btn-ghost-secondary btn-sm" id="panel-close" aria-label="{{ __('Close panel') }}">&times;</button>
    </div>
    <div id="panel-content">
        <p class="text-secondary">{{ __('Loading...') }}</p>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

@push('scripts')
<script>
(function() {
    const baseUrl = '{{ url("/pipeline") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let dragDealId = null;
    let dragSourceStage = null;
    let dragSourceRow = null;

    // ── Toast ────────────────────────────────────────────
    function showToast(message, type) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'pipeline-toast ' + type;
        toast.textContent = message;
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ── Accordion toggle ─────────────────────────────────
    document.querySelectorAll('.stage-header').forEach(header => {
        header.addEventListener('click', e => {
            // Don't toggle if dropping
            if (e.target.closest('.deal-card-drag')) return;
            const row = header.closest('.stage-row');
            const wasExpanded = row.classList.contains('expanded');

            // Collapse all
            document.querySelectorAll('.stage-row.expanded').forEach(r => r.classList.remove('expanded'));

            // Toggle clicked
            if (!wasExpanded) {
                row.classList.add('expanded');
                // Scroll into view if needed
                row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

    // Auto-expand first stage that has deals
    const firstWithDeals = document.querySelector('.stage-row .stage-cards-grid .deal-card');
    if (firstWithDeals) {
        firstWithDeals.closest('.stage-row').classList.add('expanded');
    }

    // ── Drag & Drop ──────────────────────────────────────
    document.querySelectorAll('.deal-card-drag').forEach(handle => {
        handle.addEventListener('dragstart', e => {
            const card = handle.closest('.deal-card');
            const grid = card.closest('.stage-cards-grid');
            dragDealId = card.dataset.dealId;
            dragSourceStage = grid.dataset.stage;
            dragSourceRow = card.closest('.stage-row');
            e.dataTransfer.setData('text/plain', dragDealId);
            e.dataTransfer.effectAllowed = 'move';
            requestAnimationFrame(() => card.classList.add('dragging'));
        });

        handle.addEventListener('dragend', () => {
            const card = handle.closest('.deal-card');
            card.classList.remove('dragging');
            document.querySelectorAll('.stage-row.drag-over').forEach(r => r.classList.remove('drag-over'));
            dragDealId = null;
            dragSourceStage = null;
            dragSourceRow = null;
        });
    });

    // Each stage row is a drop target
    document.querySelectorAll('.stage-row').forEach(row => {
        const stage = row.dataset.stage;

        row.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (stage !== dragSourceStage && !row.classList.contains('drag-over')) {
                row.classList.add('drag-over');
            }
        });

        row.addEventListener('dragleave', e => {
            if (!row.contains(e.relatedTarget)) {
                row.classList.remove('drag-over');
            }
        });

        row.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
            row.classList.remove('drag-over');

            const dealId = e.dataTransfer.getData('text/plain');
            if (!dealId || stage === dragSourceStage) return;

            // Optimistic UI: move card
            const card = document.querySelector(`.deal-card[data-deal-id="${dealId}"]`);
            if (!card) return;

            const sourceGrid = document.querySelector(`.stage-cards-grid[data-stage="${dragSourceStage}"]`);
            const targetGrid = row.querySelector('.stage-cards-grid');

            // Remove empty message from target if present
            const emptyMsg = targetGrid.querySelector('.stage-empty');
            if (emptyMsg) emptyMsg.remove();

            targetGrid.appendChild(card);
            card.classList.remove('dragging');

            // Add empty message to source if now empty
            if (!sourceGrid.querySelector('.deal-card')) {
                sourceGrid.innerHTML = '<div class="stage-empty">{{ __('No deals in this stage') }}</div>';
            }

            updateStageStats();

            // AJAX
            fetch(baseUrl + '/' + dealId + '/stage', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ stage: stage })
            }).then(r => {
                if (!r.ok) throw new Error('Failed');
                return r.json();
            }).then(data => {
                if (data.success) {
                    showToast('{{ ($businessMode ?? "wholesale") === "realestate" ? __("Transaction moved to") : __("Deal moved to") }} ' + row.querySelector('.stage-name').textContent, 'success');
                } else {
                    throw new Error('Failed');
                }
            }).catch(() => {
                // Revert
                showToast('{{ __('Failed to move deal. Please try again.') }}', 'error');
                targetGrid.querySelector('.stage-empty')?.remove();
                sourceGrid.querySelector('.stage-empty')?.remove();
                sourceGrid.appendChild(card);
                if (!targetGrid.querySelector('.deal-card')) {
                    targetGrid.innerHTML = '<div class="stage-empty">{{ __('No deals in this stage') }}</div>';
                }
                updateStageStats();
            });
        });
    });

    // ── Update badges/stats after move ───────────────────
    function updateStageStats() {
        document.querySelectorAll('.stage-row').forEach(row => {
            const count = row.querySelectorAll('.deal-card').length;
            const badge = row.querySelector('.stage-stats .badge');
            if (badge) badge.textContent = count;
        });
        const totalCount = document.querySelectorAll('.deal-card').length;
        const totalEl = document.querySelector('.pipeline-filters .ms-auto');
        if (totalEl) totalEl.textContent = totalCount + ' ' + (totalCount !== 1 ? '{{ __('deals') }}' : '{{ __('deal') }}') + ' {{ __('in pipeline') }}';
    }

    // ── Card click → slide-over ──────────────────────────
    document.addEventListener('click', e => {
        const body = e.target.closest('.deal-card-body');
        if (body) {
            e.stopPropagation();
            openDealPanel(body.dataset.dealId);
        }
    });

    document.getElementById('dealBackdrop').addEventListener('click', closeDealPanel);
    document.getElementById('panel-close').addEventListener('click', closeDealPanel);

    function openDealPanel(dealId) {
        document.getElementById('dealBackdrop').classList.add('open');
        document.getElementById('dealPanel').classList.add('open');
        document.getElementById('panel-content').innerHTML = '<p class="text-secondary">{{ __('Loading...') }}</p>';

        fetch(baseUrl + '/' + dealId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json()).then(deal => {
            document.getElementById('panel-title').innerHTML = deal.title + ' <a href="{{ url("/pipeline") }}/' + deal.id + '" class="btn btn-outline-primary btn-sm ms-2" title="{{ __('View Full Deal') }}"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6"/><path d="M11 13l9 -9"/><path d="M15 4h5v5"/></svg> {{ __('Open') }}</a>';
            let html = `
                <form id="deal-edit-form" data-deal-id="${deal.id}">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Stage') }}</label>
                        <div><span class="badge bg-primary">${deal.stage.replace(/_/g, ' ')}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Lead') }}</label>
                        <div>${deal.lead ? deal.lead.first_name + ' ' + deal.lead.last_name : '-'}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Property') }}</label>
                        <div>${deal.lead && deal.lead.property ? deal.lead.property.address : '-'}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Agent') }}</label>
                        <div>${deal.agent ? deal.agent.name : '-'}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Contract Price ($)') }}</label>
                        <input type="number" name="contract_price" class="form-control" step="0.01" value="${deal.contract_price || ''}">
                    </div>
                    @if($businessMode === 'wholesale')
                    <div class="mb-3">
                        <label class="form-label">{{ __('Assignment Fee ($)') }}</label>
                        <input type="number" name="assignment_fee" class="form-control" step="0.01" value="${deal.assignment_fee || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Earnest Money ($)') }}</label>
                        <input type="number" name="earnest_money" class="form-control" step="0.01" value="${deal.earnest_money || ''}">
                    </div>
                    @else
                    <div class="mb-3">
                        <label class="form-label">{{ __('Listing Commission (%)') }}</label>
                        <input type="number" name="listing_commission_pct" class="form-control" step="0.01" min="0" max="100" value="${deal.listing_commission_pct || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Buyer Commission (%)') }}</label>
                        <input type="number" name="buyer_commission_pct" class="form-control" step="0.01" min="0" max="100" value="${deal.buyer_commission_pct || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Total Commission ($)') }}</label>
                        <input type="number" name="total_commission" class="form-control" step="0.01" value="${deal.total_commission || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('MLS #') }}</label>
                        <input type="text" name="mls_number" class="form-control" maxlength="30" value="${deal.mls_number || ''}">
                    </div>
                    @endif
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">{{ __('Contract Date') }}</label>
                            <input type="date" name="contract_date" class="form-control" value="${deal.contract_date ? deal.contract_date.split('T')[0] : ''}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Closing Date') }}</label>
                            <input type="date" name="closing_date" class="form-control" value="${deal.closing_date ? deal.closing_date.split('T')[0] : ''}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Inspection Period (days)') }}</label>
                        <input type="number" name="inspection_period_days" class="form-control" min="0" value="${deal.inspection_period_days || ''}">
                    </div>
                    ${deal.due_diligence_end_date ? `<div class="alert ${deal.is_due_diligence_urgent ? 'alert-danger' : 'alert-info'} mb-3"><strong>{{ __('Due Diligence:') }}</strong> {{ __('Ends') }} ${new Date(deal.due_diligence_end_date).toLocaleDateString()} (${deal.due_diligence_days_remaining ?? '?'} {{ __('days left') }})</div>` : ''}
                    <div class="mb-3">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">${deal.notes || ''}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill" id="panel-save-btn">{{ __('Save Changes') }}</button>
                        @if(auth()->user()->tenant->ai_enabled)
                        <button type="button" class="btn btn-outline-purple" onclick="aiAnalyzeDeal(${deal.id})" title="{{ __('AI Deal Analysis') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                            {{ __('AI Analyze') }}
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="aiStageAdvice(${deal.id})" title="{{ __('AI Stage Advisor') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                            {{ __('Stage Advisor') }}
                        </button>
                        @endif
                    </div>
                </form>
                <hr>
                <h4>{{ __('Documents') }}</h4>
                <form action="{{ url('/pipeline') }}/${deal.id}/documents" method="POST" enctype="multipart/form-data" class="mb-3">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <div class="input-group">
                        <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Upload') }}</button>
                    </div>
                    <small class="text-secondary">{{ __('PDF, JPG, PNG. Max 10MB.') }}</small>
                </form>
            `;

            if (deal.documents && deal.documents.length) {
                html += '<div class="list-group list-group-flush">';
                deal.documents.forEach(doc => {
                    html += `<a href="{{ url('/pipeline/documents') }}/${doc.id}/download" class="list-group-item list-group-item-action">${doc.original_name} <small class="text-secondary">(${(doc.size / 1024).toFixed(1)} KB)</small></a>`;
                });
                html += '</div>';
            } else {
                html += '<p class="text-secondary">{{ __('No documents uploaded.') }}</p>';
            }

            if (deal.buyer_matches && deal.buyer_matches.length) {
                html += '<hr><h4>{{ __('Matched') }} {{ $modeTerms['buyer_label'] ?? __('Buyers') }}</h4><div class="list-group list-group-flush">';
                deal.buyer_matches.forEach(m => {
                    const scoreClass = m.match_score >= 70 ? 'bg-green-lt' : (m.match_score >= 40 ? 'bg-yellow-lt' : 'bg-red-lt');
                    const sparkSvg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>';
                    const aiBtn = @json(auth()->user()->tenant->ai_enabled ?? false) ? `<button class="btn btn-outline-purple btn-sm ms-1" onclick="aiDraftBuyerMsg(${deal.id}, ${m.buyer ? m.buyer.id : 0})" title="{{ __('AI Draft Message') }}">${sparkSvg}</button><button class="btn btn-outline-info btn-sm ms-1" onclick="aiExplainMatch(${deal.id}, ${m.buyer ? m.buyer.id : 0})" title="{{ __('Explain Match') }}">${sparkSvg}</button>` : '';
                    html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <div><strong>${m.buyer ? m.buyer.company_name : '-'}</strong><br><small class="text-secondary">${m.buyer ? m.buyer.contact_name : ''}</small></div>
                        <div class="d-flex align-items-center">${aiBtn}<span class="badge ${scoreClass} ms-1">${m.match_score}%</span></div>
                    </div>`;
                });
                html += '</div>';
            }

            document.getElementById('panel-content').innerHTML = html;

            document.getElementById('deal-edit-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('panel-save-btn');
                btn.disabled = true;
                btn.textContent = '{{ __('Saving...') }}';

                const formData = new FormData(this);
                const data = {};
                formData.forEach((val, key) => data[key] = val);

                fetch(baseUrl + '/' + this.dataset.dealId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                }).then(r => {
                    if (!r.ok) throw new Error('Failed');
                    return r.json();
                }).then(result => {
                    if (result.success) {
                        closeDealPanel();
                        showToast('{{ ($businessMode ?? "wholesale") === "realestate" ? __("Transaction updated successfully") : __("Deal updated successfully") }}', 'success');
                        setTimeout(() => location.reload(), 500);
                    }
                }).catch(() => {
                    btn.disabled = false;
                    btn.textContent = '{{ __('Save Changes') }}';
                    showToast('{{ __('Failed to save changes') }}', 'error');
                });
            });
        }).catch(() => {
            document.getElementById('panel-content').innerHTML = '<p class="text-danger">{{ __('Failed to load deal details.') }}</p>';
        });
    }

    function closeDealPanel() {
        document.getElementById('dealBackdrop').classList.remove('open');
        document.getElementById('dealPanel').classList.remove('open');
    }

    // ── Search & filter ──────────────────────────────────
    const searchInput = document.getElementById('pipeline-search');
    const agentFilter = document.getElementById('pipeline-agent-filter');

    function applyFilters() {
        const params = new URLSearchParams();
        const search = searchInput ? searchInput.value.trim() : '';
        const agent = agentFilter ? agentFilter.value : '';
        if (search) params.set('search', search);
        if (agent) params.set('agent', agent);
        const qs = params.toString();
        window.location.href = '{{ url("/pipeline") }}' + (qs ? '?' + qs : '');
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); applyFilters(); }
        });
    }
    if (agentFilter) {
        agentFilter.addEventListener('change', applyFilters);
    }

    // ── Move-to dropdown buttons ────────────────────────
    document.querySelectorAll('.move-deal-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var dealId = this.dataset.dealId;
            var stage = this.dataset.stage;
            fetch(baseUrl + '/' + dealId + '/stage', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ stage: stage })
            }).then(function(r) {
                if (r.ok) location.reload();
                else showToast('{{ __("Failed to move deal. Please try again.") }}', 'error');
            });
        });
    });

    // ── Quick Edit on deal cards ─────────────────────────
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.quick-edit-btn');
        if (!btn) return;
        e.stopPropagation();
        e.preventDefault();

        var card = btn.closest('.deal-card');
        var dealId = btn.dataset.dealId;

        // Get current values from card content
        var priceEl = card.querySelector('[data-field="contract_price"]');
        var feeField = '{{ $businessMode === "realestate" ? "total_commission" : "assignment_fee" }}';
        var feeEl = card.querySelector('[data-field="' + feeField + '"]');
        var currentPrice = priceEl ? priceEl.dataset.rawValue || '' : '';
        var currentFee = feeEl ? feeEl.dataset.rawValue || '' : '';

        // Store original content
        var cardBody = card.querySelector('.deal-card-body');
        if (!cardBody) return;
        var originalHtml = cardBody.innerHTML;

        // Hide the quick-edit button while editing
        btn.style.display = 'none';

        var feeLabel = '{{ $businessMode === "realestate" ? __("Total Commission") : __("Assignment Fee") }}';
        var feeName = feeField;

        cardBody.innerHTML =
            '<div class="quick-edit-form" onclick="event.stopPropagation()">' +
            '<div class="mb-2"><label class="form-label form-label-sm mb-0">{{ __("Contract Price") }}</label>' +
            '<input type="number" class="form-control form-control-sm" name="contract_price" value="' + currentPrice + '" step="0.01"></div>' +
            '<div class="mb-2"><label class="form-label form-label-sm mb-0">' + feeLabel + '</label>' +
            '<input type="number" class="form-control form-control-sm" name="' + feeName + '" value="' + currentFee + '" step="0.01"></div>' +
            '<div class="d-flex gap-1">' +
            '<button type="button" class="btn btn-sm btn-primary quick-edit-save">{{ __("Save") }}</button>' +
            '<button type="button" class="btn btn-sm btn-ghost-secondary quick-edit-cancel">{{ __("Cancel") }}</button>' +
            '</div></div>';

        // Focus first input
        var firstInput = cardBody.querySelector('input[name="contract_price"]');
        if (firstInput) firstInput.focus();

        // Cancel - restore original
        cardBody.querySelector('.quick-edit-cancel').addEventListener('click', function(ev) {
            ev.stopPropagation();
            cardBody.innerHTML = originalHtml;
            btn.style.display = '';
        });

        // Save
        cardBody.querySelector('.quick-edit-save').addEventListener('click', function(ev) {
            ev.stopPropagation();
            var form = cardBody.querySelector('.quick-edit-form');
            var contractPrice = form.querySelector('[name="contract_price"]').value;
            var feeInput = form.querySelector('[name="' + feeName + '"]');
            var feeValue = feeInput ? feeInput.value : '';
            var saveBtn = form.querySelector('.quick-edit-save');
            saveBtn.disabled = true;
            saveBtn.textContent = '{{ __("Saving...") }}';

            var payload = { contract_price: contractPrice || null };
            payload[feeName] = feeValue || null;

            fetch(baseUrl + '/' + dealId, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload)
            }).then(function(r) {
                if (!r.ok) throw new Error('Failed');
                return r.json();
            }).then(function(data) {
                if (data.success || data.deal) {
                    // Restore original HTML first
                    cardBody.innerHTML = originalHtml;
                    btn.style.display = '';

                    // Update contract price display
                    var newPriceEl = card.querySelector('[data-field="contract_price"]');
                    if (newPriceEl && contractPrice) {
                        newPriceEl.dataset.rawValue = contractPrice;
                        newPriceEl.textContent = '$' + Number(contractPrice).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0});
                    }

                    // Update fee display (assignment_fee or total_commission based on mode)
                    var newFeeEl = card.querySelector('[data-field="' + feeName + '"]');
                    if (newFeeEl && feeValue) {
                        newFeeEl.dataset.rawValue = feeValue;
                        newFeeEl.textContent = '$' + Number(feeValue).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0});
                    }

                    showToast('{{ __("Deal updated successfully") }}', 'success');
                } else {
                    throw new Error('Failed');
                }
            }).catch(function() {
                cardBody.innerHTML = originalHtml;
                btn.style.display = '';
                showToast('{{ __("Failed to save changes") }}', 'error');
            });
        });

        // Allow Enter key to save and Escape to cancel
        cardBody.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                ev.stopPropagation();
                var saveBtn = cardBody.querySelector('.quick-edit-save');
                if (saveBtn && !saveBtn.disabled) saveBtn.click();
            } else if (ev.key === 'Escape') {
                ev.stopPropagation();
                var cancelBtn = cardBody.querySelector('.quick-edit-cancel');
                if (cancelBtn) cancelBtn.click();
            }
        });
    });

    // ── Keyboard ─────────────────────────────────────────
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeDealPanel();
            var aiM = document.getElementById('pipeline-ai-modal');
            if (aiM) bootstrap.Modal.getInstance(aiM)?.hide();
        }
    });
})();

@if(auth()->user()->tenant->ai_enabled)
// ── AI Functions for Pipeline ───────────────────────
var _pipelineCurrentDealId = null;
var _pipelineCurrentLeadId = null;

function aiAnalyzeDeal(dealId) {
    _pipelineCurrentDealId = dealId;
    showPipelineAiModal('{{ __('AI Deal Analysis') }}');
    pipelineAiRequest('{{ url("/ai/analyze-deal") }}', { deal_id: dealId });
}

function aiDraftBuyerMsg(dealId, buyerId) {
    _pipelineCurrentDealId = dealId;
    showPipelineAiModal('{{ __('AI Buyer Outreach Draft') }}');
    pipelineAiRequest('{{ url("/ai/draft-buyer-message") }}', { deal_id: dealId, buyer_id: buyerId });
}

function aiStageAdvice(dealId) {
    _pipelineCurrentDealId = dealId;
    showPipelineAiModal('{{ __('AI Stage Advisor') }}');
    pipelineAiRequest('{{ url("/ai/deal-stage-advice") }}', { deal_id: dealId });
}

function aiExplainMatch(dealId, buyerId) {
    _pipelineCurrentDealId = dealId;
    showPipelineAiModal('{{ __('AI Buyer Match Explanation') }}');
    pipelineAiRequest('{{ url("/ai/explain-buyer-match") }}', { deal_id: dealId, buyer_id: buyerId });
}

var _pipelineAiModalInstance = null;
function showPipelineAiModal(title) {
    var modal = document.getElementById('pipeline-ai-modal');
    if (!modal) return;
    if (!_pipelineAiModalInstance) {
        _pipelineAiModalInstance = new bootstrap.Modal(modal);
        modal.addEventListener('hide.bs.modal', function() {
            if (modal.contains(document.activeElement)) document.activeElement.blur();
        });
    }
    document.getElementById('p-ai-modal-title').textContent = title;
    document.getElementById('p-ai-loading').style.display = 'block';
    document.getElementById('p-ai-result').style.display = 'none';
    document.getElementById('p-ai-error').style.display = 'none';
    document.getElementById('p-ai-copy-btn').style.display = 'none';
    document.getElementById('p-ai-actions').style.display = 'none';
    document.getElementById('p-ai-actions-list').innerHTML = '';
    _pipelineAiModalInstance.show();
}

var _pStageLabels = @json(\App\Models\Deal::stageLabels());
var _pPriorityLabels = { low: '{{ __('Low') }}', medium: '{{ __('Medium') }}', high: '{{ __('High') }}' };
var _pPriorityColors = { low: 'secondary', medium: 'warning', high: 'danger' };

function pipelineAiRequest(url, data) {
    var csrfTkn = document.querySelector('meta[name="csrf-token"]').content;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTkn, 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        document.getElementById('p-ai-loading').style.display = 'none';
        if (res.error) {
            document.getElementById('p-ai-error').style.display = 'block';
            document.getElementById('p-ai-error').textContent = res.error;
            return;
        }
        var text = res.analysis || res.message || res.advice || res.explanation || res.strategy || res.description || '';
        document.getElementById('p-ai-result').style.display = 'block';
        document.getElementById('p-ai-text').innerHTML = window.renderAiMarkdown(text);
        document.getElementById('p-ai-copy-btn').style.display = 'inline-block';
        window._pipelineAiText = text;
        if (res.lead_id) _pipelineCurrentLeadId = res.lead_id;
        renderPipelineActions(res.actions || []);
    }).catch(() => {
        document.getElementById('p-ai-loading').style.display = 'none';
        document.getElementById('p-ai-error').style.display = 'block';
        document.getElementById('p-ai-error').textContent = '{{ __('Request failed. Please try again.') }}';
    });
}

function renderPipelineActions(actions) {
    var container = document.getElementById('p-ai-actions-list');
    var wrapper = document.getElementById('p-ai-actions');
    container.innerHTML = '';
    if (!actions.length) { wrapper.style.display = 'none'; return; }
    wrapper.style.display = 'block';

    actions.forEach(function(action) {
        var row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between border rounded p-2 mb-2';
        var left = document.createElement('div');
        left.className = 'd-flex align-items-center gap-2';
        var icon = '', detail = '';
        if (action.type === 'stage_change') {
            icon = '<span class="badge bg-blue-lt">{{ __('Stage') }}</span>';
            detail = '<span>' + action.label + ' &rarr; <strong>' + (_pStageLabels[action.stage] || action.stage) + '</strong></span>';
        } else if (action.type === 'create_task') {
            icon = '<span class="badge bg-yellow-lt">{{ __('Task') }}</span>';
            var pc = _pPriorityColors[action.priority] || 'secondary';
            detail = '<span>' + action.label + ' <span class="badge bg-' + pc + '-lt ms-1">' + (_pPriorityLabels[action.priority] || action.priority) + '</span> <span class="text-secondary small">(' + action.due_days + 'd)</span></span>';
        } else if (action.type === 'add_note') {
            icon = '<span class="badge bg-cyan-lt">{{ __('Note') }}</span>';
            detail = '<span>' + action.label + '</span>';
        }
        left.innerHTML = icon + detail;

        var btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-outline-success ms-2';
        btn.style.whiteSpace = 'nowrap';
        btn.textContent = '{{ __('Apply') }}';
        btn.addEventListener('click', function() { applyPipelineAction(action, this); });

        row.appendChild(left);
        row.appendChild(btn);
        container.appendChild(row);
    });
}

function applyPipelineAction(action, btn) {
    var csrfTkn = document.querySelector('meta[name="csrf-token"]').content;
    btn.disabled = true;
    btn.textContent = '{{ __('Applying...') }}';

    if (action.type === 'stage_change' && _pipelineCurrentDealId) {
        fetch('{{ url("/pipeline") }}/' + _pipelineCurrentDealId + '/stage', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTkn, 'Accept': 'application/json' },
            body: JSON.stringify({ stage: action.stage })
        }).then(r => r.json()).then(data => {
            if (data.success) { markPApplied(btn); setTimeout(() => location.reload(), 800); }
            else { markPFailed(btn); }
        }).catch(() => markPFailed(btn));

    } else if (action.type === 'create_task' && _pipelineCurrentLeadId) {
        var due = new Date(); due.setDate(due.getDate() + (action.due_days || 3));
        fetch('{{ url("/leads") }}/' + _pipelineCurrentLeadId + '/tasks', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTkn, 'Accept': 'application/json' },
            body: JSON.stringify({ title: action.title || action.label, due_date: due.toISOString().split('T')[0] })
        }).then(r => {
            if (r.redirected || !r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(data => {
            if (data.success) { markPApplied(btn, '{{ __('Task Created') }}'); }
            else { console.error('Task create response:', data); markPFailed(btn); }
        }).catch(e => { console.error('Task create error:', e); markPFailed(btn); });

    } else if (action.type === 'add_note' && _pipelineCurrentDealId) {
        fetch('{{ url("/pipeline") }}/' + _pipelineCurrentDealId + '/activities', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTkn, 'Accept': 'application/json' },
            body: JSON.stringify({ type: 'note', subject: '{{ __('AI Recommendation') }}', body: action.text || action.label })
        }).then(r => {
            if (r.redirected || !r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(data => {
            if (data.success || data.id) { markPApplied(btn, '{{ __('Note Saved') }}'); }
            else { console.error('Note save response:', data); markPFailed(btn); }
        }).catch(e => { console.error('Note save error:', e); markPFailed(btn); });
    } else {
        console.error('Cannot apply action - missing dealId:', _pipelineCurrentDealId, 'leadId:', _pipelineCurrentLeadId);
        markPFailed(btn);
    }
}

function markPApplied(btn, label) {
    btn.textContent = label || '{{ __('Applied') }}';
    btn.className = 'btn btn-sm btn-success ms-2 disabled';
    btn.disabled = true;
}
function markPFailed(btn) {
    btn.textContent = '{{ __('Failed') }}';
    btn.className = 'btn btn-sm btn-outline-danger ms-2';
    setTimeout(function() { btn.textContent = '{{ __('Apply') }}'; btn.className = 'btn btn-sm btn-outline-success ms-2'; btn.disabled = false; }, 2000);
}
@endif
</script>
@endpush

@if(auth()->user()->tenant->ai_enabled)
<!-- Pipeline AI Modal -->
<div class="modal modal-blur fade" id="pipeline-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="p-ai-modal-title">{{ __('AI Assistant') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="p-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="p-ai-result" style="display: none;">
                    <div style="line-height: 1.6;" id="p-ai-text"></div>
                </div>
                <div id="p-ai-error" class="alert alert-danger" style="display: none;"></div>
                <div id="p-ai-actions" class="mt-3" style="display:none;">
                    <h4 class="mb-2">{{ __('Recommended Actions') }}</h4>
                    <div id="p-ai-actions-list"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="p-ai-copy-btn" style="display: none;" onclick="navigator.clipboard.writeText(window._pipelineAiText||'').then(()=>{this.textContent='{{ __('Copied!') }}';setTimeout(()=>{this.textContent='{{ __('Copy to Clipboard') }}'},2000)})">{{ __('Copy to Clipboard') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
