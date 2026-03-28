@extends('layouts.app')

@section('title', __('Campaigns'))
@section('page-title', __('Campaigns'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Campaigns') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Marketing Campaigns') }}</h3>
        <div class="card-actions">
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Create Campaign') }}
            </a>
        </div>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('campaigns.index') }}" class="row g-2">
            <div class="col-md-3">
                <label for="filter-search" class="visually-hidden">{{ __('Search') }}</label>
                <input type="text" name="search" id="filter-search" class="form-control" placeholder="{{ __('Search campaigns...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="filter-status" class="visually-hidden">{{ __('Status') }}</label>
                <select name="status" id="filter-status" class="form-select">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(\App\Models\Campaign::statusLabels() as $val => $label)
                        <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-type" class="visually-hidden">{{ __('Type') }}</label>
                <select name="type" id="filter-type" class="form-select">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach(\App\Models\Campaign::typeLabels() as $val => $label)
                        <option value="{{ $val }}" {{ request('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
            </div>
            @if(request()->hasAny(['search', 'status', 'type']))
            <div class="col-auto">
                <a href="{{ route('campaigns.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Budget') }}</th>
                    <th>{{ __('Spend') }}</th>
                    <th>{{ __('Leads') }}</th>
                    <th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s</th>
                    <th>{{ __('Revenue') }}</th>
                    <th>{{ __('ROI') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                @php
                    $cLeads = $leadCounts[$campaign->id] ?? 0;
                    $cDeals = $dealCounts[$campaign->id] ?? 0;
                    $cRevenue = $revenues[$campaign->id] ?? 0;
                    $cSpend = (float) $campaign->actual_spend;
                    $cRoi = ($cSpend > 0) ? round(($cRevenue - $cSpend) / $cSpend * 100, 2) : null;

                    $statusColors = [
                        'draft' => 'bg-secondary',
                        'active' => 'bg-green',
                        'paused' => 'bg-yellow',
                        'completed' => 'bg-blue',
                    ];

                    $typeColors = [
                        'direct_mail' => 'bg-purple-lt',
                        'ppc' => 'bg-orange-lt',
                        'cold_call' => 'bg-cyan-lt',
                        'bandit_sign' => 'bg-lime-lt',
                        'seo' => 'bg-blue-lt',
                        'social' => 'bg-pink-lt',
                        'email' => 'bg-azure-lt',
                        'ringless_voicemail' => 'bg-teal-lt',
                        'other' => 'bg-secondary-lt',
                    ];
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="text-reset fw-medium">{{ $campaign->name }}</a>
                        @if($campaign->start_date || $campaign->end_date)
                            <div class="text-secondary small">
                                @if($campaign->start_date && $campaign->end_date)
                                    {{ $campaign->start_date->format('M d') }} - {{ $campaign->end_date->format('M d, Y') }}
                                @elseif($campaign->start_date)
                                    {{ __('From') }} {{ $campaign->start_date->format('M d, Y') }}
                                @endif
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $typeColors[$campaign->type] ?? 'bg-secondary-lt' }}">
                            {{ \App\Models\Campaign::typeLabel($campaign->type) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $statusColors[$campaign->status] ?? 'bg-secondary' }}">
                            {{ \App\Models\Campaign::statusLabel($campaign->status) }}
                        </span>
                    </td>
                    <td class="text-secondary">{{ $campaign->budget ? \Fmt::currency($campaign->budget) : '-' }}</td>
                    <td class="text-secondary">{{ $cSpend > 0 ? \Fmt::currency($cSpend) : '-' }}</td>
                    <td>
                        <span class="fw-medium">{{ number_format($cLeads) }}</span>
                        @if($campaign->target_count > 0)
                            <span class="text-secondary small">/ {{ number_format($campaign->target_count) }}</span>
                        @endif
                    </td>
                    <td class="text-secondary">{{ number_format($cDeals) }}</td>
                    <td>
                        @if($cRevenue > 0)
                            <span class="text-green fw-medium">{{ \Fmt::currency($cRevenue) }}</span>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        @if($cRoi !== null)
                            <span class="badge {{ $cRoi >= 0 ? 'bg-green-lt' : 'bg-red-lt' }}">{{ $cRoi }}%</span>
                        @else
                            <span class="text-secondary">{{ __('N/A') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Actions for') }} {{ $campaign->name }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('campaigns.show', $campaign) }}">{{ __('View') }}</a>
                                <a class="dropdown-item" href="{{ route('campaigns.edit', $campaign) }}">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('{{ __('Delete this campaign? Leads will be unlinked but not deleted.') }}')">
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
                    <td colspan="10" class="text-center py-4">
                        @if(request()->hasAny(['search', 'status', 'type']))
                            <div class="text-secondary mb-2">{{ __('No campaigns match your current filters.') }}</div>
                            <a href="{{ route('campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                                {{ __('Clear Filters') }}
                            </a>
                        @else
                            <div class="mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-secondary mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19l18 0"/><path d="M5 6l0 13"/><path d="M19 6l0 13"/><path d="M8 6l0 8"/><path d="M12 6l0 5"/><path d="M16 6l0 10"/></svg>
                            </div>
                            <div class="text-secondary mb-2">{{ __('No campaigns yet. Start tracking your marketing efforts!') }}</div>
                            <a href="{{ route('campaigns.create') }}" class="btn btn-sm btn-primary">{{ __('Create Campaign') }}</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $campaigns->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $campaigns->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $campaigns->total() }}</span> {{ __('entries') }}</p>
        <div class="ms-auto">
            {{ $campaigns->withQueryString()->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
