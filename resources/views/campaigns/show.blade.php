@extends('layouts.app')

@section('title', $campaign->name)
@section('page-title', $campaign->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('campaigns.index') }}">{{ __('Campaigns') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $campaign->name }}</li>
@endsection

@php
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

@section('content')
{{-- Campaign Header --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h2 class="mb-0">{{ $campaign->name }}</h2>
                    <span class="badge {{ $typeColors[$campaign->type] ?? 'bg-secondary-lt' }}">
                        {{ \App\Models\Campaign::typeLabel($campaign->type) }}
                    </span>
                    <span class="badge {{ $statusColors[$campaign->status] ?? 'bg-secondary' }}">
                        {{ \App\Models\Campaign::statusLabel($campaign->status) }}
                    </span>
                </div>
                <div class="text-secondary">
                    @if($campaign->start_date && $campaign->end_date)
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                        {{ $campaign->start_date->format('M d, Y') }} &mdash; {{ $campaign->end_date->format('M d, Y') }}
                    @elseif($campaign->start_date)
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                        {{ __('Started') }} {{ $campaign->start_date->format('M d, Y') }}
                    @endif
                    @if($campaign->createdBy)
                        <span class="ms-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                            {{ $campaign->createdBy->name }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->user()->tenant->ai_enabled)
                <button type="button" class="btn btn-outline-purple" id="campaign-ai-insights-btn" title="{{ __('AI Campaign Insights') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                    {{ __('AI Insights') }}
                </button>
                @endif
                <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                    {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('{{ __('Delete this campaign? Leads will be unlinked but not deleted.') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                        {{ __('Delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- KPI Cards Row --}}
<div class="row mb-3">
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="subheader text-secondary mb-1">{{ __('Total Leads') }}</div>
                <div class="h1 mb-0">{{ number_format($leadCount) }}</div>
                @if($campaign->target_count > 0)
                    <div class="mt-1">
                        @php $pct = min(round(($leadCount / $campaign->target_count) * 100), 100); @endphp
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-primary" style="width: {{ $pct }}%" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-secondary">{{ $pct }}% {{ __('of target') }}</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="subheader text-secondary mb-1">{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Created') }}</div>
                <div class="h1 mb-0">{{ number_format($dealCount) }}</div>
                @if($leadCount > 0)
                    <small class="text-secondary">{{ round(($dealCount / $leadCount) * 100, 1) }}% {{ __('conversion') }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="subheader text-secondary mb-1">{{ __('Closed Won') }}</div>
                <div class="h1 mb-0 text-green">{{ number_format($closedDealCount) }}</div>
                @if($dealCount > 0)
                    <small class="text-secondary">{{ round(($closedDealCount / $dealCount) * 100, 1) }}% {{ __('close rate') }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="subheader text-secondary mb-1">{{ __('Total Revenue') }}</div>
                <div class="h1 mb-0 text-green">{{ \Fmt::currency($revenue) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="subheader text-secondary mb-1">{{ __('ROI') }}</div>
                @if($roi !== null)
                    <div class="h1 mb-0 {{ $roi >= 0 ? 'text-green' : 'text-red' }}">{{ $roi }}%</div>
                    <small class="text-secondary">{{ \Fmt::currency($spend) }} {{ __('spent') }}</small>
                @else
                    <div class="h1 mb-0 text-secondary">{{ __('N/A') }}</div>
                    <small class="text-secondary">{{ __('No spend recorded') }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="subheader text-secondary mb-1">{{ __('Cost Per Lead') }}</div>
                @if($costPerLead !== null)
                    <div class="h1 mb-0">{{ \Fmt::currency($costPerLead) }}</div>
                @else
                    <div class="h1 mb-0 text-secondary">{{ __('N/A') }}</div>
                    <small class="text-secondary">{{ __('No data yet') }}</small>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    {{-- Charts Column --}}
    <div class="col-lg-8">
        <div class="row mb-3">
            {{-- Lead Status Breakdown --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Lead Status Breakdown') }}</h3>
                    </div>
                    <div class="card-body">
                        @if(count($statusBreakdown) > 0)
                            <div id="chart-status" style="min-height: 260px;"></div>
                        @else
                            <div class="text-center py-4 text-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 3.2a9 9 0 1 0 10.8 10.8a1 1 0 0 0 -1 -1h-3.8a4.5 4.5 0 1 1 -5 -5v-3.8a1 1 0 0 0 -1 -1"/><path d="M15 3.5a9 9 0 0 1 5.5 5.5h-4.5v-4.5"/></svg>
                                <div>{{ __('No lead data yet') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Temperature Distribution --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Lead Temperature Distribution') }}</h3>
                    </div>
                    <div class="card-body">
                        @if(count($temperatureBreakdown) > 0)
                            <div id="chart-temperature" style="min-height: 260px;"></div>
                        @else
                            <div class="text-center py-4 text-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 3.2a9 9 0 1 0 10.8 10.8a1 1 0 0 0 -1 -1h-3.8a4.5 4.5 0 1 1 -5 -5v-3.8a1 1 0 0 0 -1 -1"/><path d="M15 3.5a9 9 0 0 1 5.5 5.5h-4.5v-4.5"/></svg>
                                <div>{{ __('No lead data yet') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget vs Spend Progress --}}
        @if($campaign->budget > 0)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Budget Utilization') }}</h3>
            </div>
            <div class="card-body">
                @php
                    $budgetPct = min(round(($spend / (float)$campaign->budget) * 100), 100);
                    $budgetColor = $budgetPct >= 90 ? 'bg-red' : ($budgetPct >= 70 ? 'bg-yellow' : 'bg-green');
                @endphp
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ \Fmt::currency($spend) }} {{ __('of') }} {{ \Fmt::currency($campaign->budget) }}</span>
                    <span class="fw-bold">{{ $budgetPct }}%</span>
                </div>
                <div class="progress progress-lg">
                    <div class="progress-bar {{ $budgetColor }}" style="width: {{ $budgetPct }}%" role="progressbar" aria-valuenow="{{ $budgetPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                @if($budgetPct >= 90)
                    <small class="text-danger mt-1 d-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
                        {{ __('Budget nearly exhausted') }}
                    </small>
                @endif
            </div>
        </div>
        @endif

        {{-- Leads Table --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Campaign Leads') }} ({{ number_format($leadCount) }})</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Temperature') }}</th>
                            <th>{{ __('Agent') }}</th>
                            <th>{{ __('Added') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                        <tr>
                            <td>
                                <a href="{{ route('leads.show', $lead) }}">{{ $lead->full_name }}</a>
                            </td>
                            <td class="text-secondary">
                                @if($lead->phone)
                                    <a href="tel:{{ $lead->phone }}" class="text-reset text-decoration-none">{{ $lead->phone }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-green-lt">{{ __(ucwords(str_replace('_', ' ', $lead->status))) }}</span>
                            </td>
                            <td>
                                @php
                                    $tempColors = ['hot' => 'bg-red-lt', 'warm' => 'bg-yellow-lt', 'cold' => 'bg-azure-lt'];
                                @endphp
                                <span class="badge {{ $tempColors[$lead->temperature] ?? 'bg-secondary-lt' }}">{{ __(ucfirst($lead->temperature)) }}</span>
                            </td>
                            <td class="text-secondary">{{ $lead->agent->name ?? '-' }}</td>
                            <td class="text-secondary">{{ $lead->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-secondary">{{ __('No leads assigned to this campaign yet.') }}</div>
                                <small class="text-secondary">{{ __('Set campaign_id on leads to link them to this campaign.') }}</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leads->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $leads->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $leads->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $leads->total() }}</span> {{ __('entries') }}</p>
                <div class="ms-auto">
                    {{ $leads->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Campaign Details --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Campaign Details') }}</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Type') }}</div>
                        <div class="datagrid-content">
                            <span class="badge {{ $typeColors[$campaign->type] ?? 'bg-secondary-lt' }}">
                                {{ \App\Models\Campaign::typeLabel($campaign->type) }}
                            </span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Status') }}</div>
                        <div class="datagrid-content">
                            <span class="badge {{ $statusColors[$campaign->status] ?? 'bg-secondary' }}">
                                {{ \App\Models\Campaign::statusLabel($campaign->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Budget') }}</div>
                        <div class="datagrid-content">{{ $campaign->budget ? \Fmt::currency($campaign->budget) : __('Not set') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Actual Spend') }}</div>
                        <div class="datagrid-content">{{ $spend > 0 ? \Fmt::currency($spend) : __('None') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Target Count') }}</div>
                        <div class="datagrid-content">{{ $campaign->target_count > 0 ? number_format($campaign->target_count) : __('Not set') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Start Date') }}</div>
                        <div class="datagrid-content">{{ $campaign->start_date ? $campaign->start_date->format('M d, Y') : __('Not set') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('End Date') }}</div>
                        <div class="datagrid-content">{{ $campaign->end_date ? $campaign->end_date->format('M d, Y') : __('Not set') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Created By') }}</div>
                        <div class="datagrid-content">{{ $campaign->createdBy->name ?? __('Unknown') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Created') }}</div>
                        <div class="datagrid-content">{{ $campaign->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Summary --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Performance Summary') }}</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-vcenter card-table">
                    <tbody>
                        <tr>
                            <td class="text-secondary">{{ __('Total Leads') }}</td>
                            <td class="text-end fw-bold">{{ number_format($leadCount) }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Created') }}</td>
                            <td class="text-end fw-bold">{{ number_format($dealCount) }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ __('Lead-to-Deal Rate') }}</td>
                            <td class="text-end fw-bold">{{ $leadCount > 0 ? round(($dealCount / $leadCount) * 100, 1) . '%' : __('N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ __('Close Rate') }}</td>
                            <td class="text-end fw-bold">{{ $dealCount > 0 ? round(($closedDealCount / $dealCount) * 100, 1) . '%' : __('N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ __('Revenue') }}</td>
                            <td class="text-end fw-bold text-green">{{ \Fmt::currency($revenue) }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ __('Cost Per Lead') }}</td>
                            <td class="text-end fw-bold">{{ $costPerLead !== null ? \Fmt::currency($costPerLead) : __('N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ __('Cost Per Deal') }}</td>
                            <td class="text-end fw-bold">{{ ($dealCount > 0 && $spend > 0) ? \Fmt::currency(round($spend / $dealCount, 2)) : __('N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">{{ __('ROI') }}</td>
                            <td class="text-end fw-bold {{ ($roi !== null && $roi >= 0) ? 'text-green' : (($roi !== null) ? 'text-red' : '') }}">
                                {{ $roi !== null ? $roi . '%' : __('N/A') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes --}}
        @if($campaign->notes)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Notes') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-0" style="white-space: pre-wrap;">{{ $campaign->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

@if(auth()->user()->tenant->ai_enabled)
{{-- AI Campaign Insights Modal --}}
<div class="modal modal-blur fade" id="campaign-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaign-ai-modal-title">{{ __('AI Campaign Insights') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="campaign-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="campaign-ai-result" style="display: none;">
                    <div style="line-height: 1.6;" id="campaign-ai-text"></div>
                </div>
                <div id="campaign-ai-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="campaign-ai-save-btn" style="display: none;">{{ __('Save to Notes') }}</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="campaign-ai-copy-btn" style="display: none;">{{ __('Copy to Clipboard') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(count($statusBreakdown) > 0)
    // Lead Status Breakdown Donut
    var statusData = @json($statusBreakdown);
    var statusLabels = [];
    var statusValues = [];
    for (var key in statusData) {
        statusLabels.push(key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); }));
        statusValues.push(statusData[key]);
    }

    var statusChart = new ApexCharts(document.querySelector('#chart-status'), {
        chart: {
            type: 'donut',
            height: 260,
            fontFamily: 'inherit',
        },
        series: statusValues,
        labels: statusLabels,
        colors: ['#206bc4', '#4299e1', '#f76707', '#f59f00', '#2fb344', '#d63939', '#ae3ec9', '#0ca678', '#667382', '#626976', '#3b5bdb', '#e8590c'],
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '55%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: '{{ __("Total") }}',
                            fontSize: '14px',
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' {{ __("leads") }}';
                }
            }
        }
    });
    statusChart.render();
    @endif

    @if(count($temperatureBreakdown) > 0)
    // Temperature Distribution Donut
    var tempData = @json($temperatureBreakdown);
    var tempLabels = [];
    var tempValues = [];
    var tempColors = { 'hot': '#d63939', 'warm': '#f59f00', 'cold': '#4299e1' };
    var tempChartColors = [];
    for (var key in tempData) {
        tempLabels.push(key.charAt(0).toUpperCase() + key.slice(1));
        tempValues.push(tempData[key]);
        tempChartColors.push(tempColors[key] || '#667382');
    }

    var temperatureChart = new ApexCharts(document.querySelector('#chart-temperature'), {
        chart: {
            type: 'donut',
            height: 260,
            fontFamily: 'inherit',
        },
        series: tempValues,
        labels: tempLabels,
        colors: tempChartColors,
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '55%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: '{{ __("Total") }}',
                            fontSize: '14px',
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' {{ __("leads") }}';
                }
            }
        }
    });
    temperatureChart.render();
    @endif
});
</script>

@if(auth()->user()->tenant->ai_enabled)
<script>
document.addEventListener('DOMContentLoaded', function() {
    var campaignAiModalEl = document.getElementById('campaign-ai-modal');
    var campaignAiModal = new bootstrap.Modal(campaignAiModalEl);
    campaignAiModalEl.addEventListener('hide.bs.modal', function() {
        if (campaignAiModalEl.contains(document.activeElement)) document.activeElement.blur();
    });
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var lastCampaignAiText = '';

    function showCampaignAiLoading() {
        document.getElementById('campaign-ai-loading').style.display = 'block';
        document.getElementById('campaign-ai-result').style.display = 'none';
        document.getElementById('campaign-ai-error').style.display = 'none';
        document.getElementById('campaign-ai-copy-btn').style.display = 'none';
        document.getElementById('campaign-ai-save-btn').style.display = 'none';
        campaignAiModal.show();
    }

    function showCampaignAiResult(text) {
        lastCampaignAiText = text;
        document.getElementById('campaign-ai-loading').style.display = 'none';
        document.getElementById('campaign-ai-result').style.display = 'block';
        document.getElementById('campaign-ai-text').innerHTML = window.renderAiMarkdown(text);
        document.getElementById('campaign-ai-copy-btn').style.display = 'inline-block';
        document.getElementById('campaign-ai-save-btn').style.display = 'inline-block';
    }

    function showCampaignAiError(msg) {
        document.getElementById('campaign-ai-loading').style.display = 'none';
        document.getElementById('campaign-ai-error').style.display = 'block';
        document.getElementById('campaign-ai-error').textContent = msg;
    }

    document.getElementById('campaign-ai-insights-btn').addEventListener('click', function() {
        showCampaignAiLoading();
        fetch('{{ url("/ai/campaign-insights") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ campaign_id: {{ $campaign->id }} })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.error) {
                showCampaignAiError(res.error);
                return;
            }
            showCampaignAiResult(res.analysis || res.message || '');
        }).catch(function() {
            showCampaignAiError('{{ __("Request failed. Please try again.") }}');
        });
    });

    document.getElementById('campaign-ai-copy-btn').addEventListener('click', function() {
        var btn = this;
        navigator.clipboard.writeText(lastCampaignAiText).then(function() {
            btn.textContent = '{{ __("Copied!") }}';
            setTimeout(function() { btn.textContent = '{{ __("Copy to Clipboard") }}'; }, 2000);
        });
    });

    document.getElementById('campaign-ai-save-btn').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.textContent = '{{ __("Saving...") }}';
        fetch('{{ url("/ai/apply-campaign-notes") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ campaign_id: {{ $campaign->id }}, notes: lastCampaignAiText })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.success) {
                btn.textContent = '{{ __("Saved!") }}';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                btn.textContent = '{{ __("Failed") }}';
                btn.disabled = false;
            }
        }).catch(function() {
            btn.textContent = '{{ __("Failed") }}';
            btn.disabled = false;
        });
    });
}); // end DOMContentLoaded
</script>
@endif
@endpush
@endsection
