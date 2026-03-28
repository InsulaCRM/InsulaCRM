@extends('layouts.app')

@section('title', $modeTerms['buyer_label'] ?? __('Cash Buyers'))
@section('page-title', $modeTerms['buyer_label'] ?? __('Cash Buyers'))

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ $businessMode === 'realestate' ? __('Import Clients from CSV') : __('Import Buyers from CSV') }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('buyers.import') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-auto flex-grow-1">
                <label class="form-label">{{ __('CSV File') }}</label>
                <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                <small class="form-hint">{{ $businessMode === 'realestate' ? __('Columns: first_name, last_name, company, phone, email, budget') : __('Columns: first_name, last_name, company, phone, email, max_purchase_price') }}</small>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                    {{ __('Import CSV') }}
                </button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $modeTerms['buyer_label'] ?? __('Cash Buyers') }}</h3>
        <div class="card-actions">
            <a href="{{ route('buyers.export', request()->query()) }}" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                {{ __('Export CSV') }}
            </a>
            <a href="{{ route('buyers.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Add Client') : __('Add Buyer') }}
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
        <form method="POST" action="{{ route('buyers.bulkAction') }}" id="bulk-form">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span id="selected-count" class="fw-bold text-muted">0</span>
                <span class="text-muted">{{ __('selected') }}</span>
                <div class="ms-3 d-flex gap-2">
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ $businessMode === 'realestate' ? __('Delete selected clients?') : __('Delete selected buyers?') }}')">{{ __('Delete') }}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('buyers.index') }}" class="row g-2">
            <div class="col-md-4">
                <label for="buyer-search" class="visually-hidden">{{ __('Search name, company, email') }}</label>
                <input type="text" id="buyer-search" name="search" class="form-control" placeholder="{{ __('Search name, company, email...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">{{ __('Search') }}</button>
            </div>
            @if(request('search'))
            <div class="col-md-1">
                <a href="{{ route('buyers.index') }}" class="btn btn-outline-secondary w-100">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
    <x-saved-views-bar entity-type="buyers" />
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th class="w-1"><input type="checkbox" id="select-all" class="form-check-input" aria-label="{{ $businessMode === 'realestate' ? __('Select all clients') : __('Select all buyers') }}"></th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Company') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ $businessMode === 'realestate' ? __('Budget') : __('Max Purchase') }}</th>
                    <th>{{ __('Reliability Score') }}</th>
                    <th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Closed') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($buyers as $buyer)
                <tr>
                    <td><input type="checkbox" class="form-check-input buyer-checkbox" value="{{ $buyer->id }}" aria-label="{{ __('Select') }} {{ $buyer->full_name }}"></td>
                    <td>
                        <a href="{{ route('buyers.show', $buyer) }}">{{ $buyer->full_name }}</a>
                    </td>
                    <td class="text-secondary">{{ $buyer->company ?? '-' }}</td>
                    <td class="text-secondary">@if($buyer->phone)<a href="tel:{{ $buyer->phone }}" class="text-reset text-decoration-none">{{ $buyer->phone }}</a>@else - @endif</td>
                    <td class="text-secondary">@if($buyer->email)<a href="mailto:{{ $buyer->email }}" class="text-reset text-decoration-none">{{ $buyer->email }}</a>@else - @endif</td>
                    <td>{{ Fmt::currency($buyer->max_purchase_price) }}</td>
                    <td>
                        @php
                            $score = $buyer->buyer_score ?? 0;
                            if ($score > 80) {
                                $barColor = 'bg-green';
                            } elseif ($score > 50) {
                                $barColor = 'bg-yellow';
                            } else {
                                $barColor = 'bg-red';
                            }
                        @endphp
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-fill" style="height: 6px;">
                                <div class="progress-bar {{ $barColor }}" style="width: {{ $score }}%"></div>
                            </div>
                            <span class="small text-secondary" style="min-width: 30px;">{{ $score }}%</span>
                        </div>
                    </td>
                    <td class="text-secondary">{{ $buyer->total_deals_closed ?? 0 }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Actions for') }} {{ $buyer->full_name }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('buyers.show', $buyer) }}">{{ __('View') }}</a>
                                <a class="dropdown-item" href="{{ route('buyers.edit', $buyer) }}">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('buyers.destroy', $buyer) }}" onsubmit="return confirm('{{ $businessMode === 'realestate' ? __('Delete this client?') : __('Delete this buyer?') }}')">
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
                    <td colspan="9" class="text-center text-secondary py-4">
                        @if(request('search'))
                            <p class="mb-2">{{ $businessMode === 'realestate' ? __('No clients match your filters.') : __('No buyers match your filters.') }}</p>
                            <a href="{{ route('buyers.index') }}" class="btn btn-outline-secondary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 5h-9.5l-4.21 5.22a2 2 0 0 0 0 2.56l4.22 5.22h9.5a1 1 0 0 0 1-1v-11a1 1 0 0 0-1-1z"/><path d="M18 9l-4 4m0-4l4 4"/></svg>
                                {{ __('Clear Filters') }}
                            </a>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-secondary mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
                            <p class="mb-2">{{ $businessMode === 'realestate' ? __('No clients yet. Start building your client list!') : __('No buyers yet. Start building your buyer list!') }}</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="{{ route('buyers.create') }}" class="btn btn-primary btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    {{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Add Client') : __('Add Buyer') }}
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelector('input[name=file]').click()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                                    {{ __('Import CSV') }}
                                </button>
                            </div>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $buyers->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $buyers->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $buyers->total() }}</span> {{ __('entries') }}</p>
        <div class="ms-auto">
            {{ $buyers->withQueryString()->links() }}
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const bulkBar = document.getElementById('bulk-actions-bar');
    const bulkForm = document.getElementById('bulk-form');
    const countSpan = document.getElementById('selected-count');

    function getCheckboxes() {
        return document.querySelectorAll('.buyer-checkbox');
    }

    function updateBulkBar() {
        const checked = document.querySelectorAll('.buyer-checkbox:checked');
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
        if (e.target.classList.contains('buyer-checkbox')) {
            updateBulkBar();
            if (!e.target.checked && selectAll) selectAll.checked = false;
        }
    });
});

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
