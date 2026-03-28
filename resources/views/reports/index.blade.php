@extends('layouts.app')

@section('title', __('Reports'))
@section('page-title', __('Reports'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Reports') }}</li>
@endsection

@section('content')
<!-- PDF Export Buttons -->
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('reports.pdf.leads') }}?from={{ $from }}&to={{ $to }}" class="btn btn-outline-primary" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 17v-6"/><path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/></svg>
        {{ __('Lead Report PDF') }}
    </a>
    <a href="{{ route('reports.pdf.pipeline') }}?from={{ $from }}&to={{ $to }}" class="btn btn-outline-primary" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 17v-6"/><path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/></svg>
        {{ __('Pipeline Report PDF') }}
    </a>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('reports.pdf.team') }}?from={{ $from }}&to={{ $to }}" class="btn btn-outline-primary" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 17v-6"/><path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/></svg>
        {{ __('Team Report PDF') }}
    </a>
    @endif
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('From') }}</label>
                <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('To') }}</label>
                <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            @if(auth()->user()->isAdmin())
            <div class="col-md-3">
                <label class="form-label">{{ __('Agent') }}</label>
                <select name="agent_id" class="form-select">
                    <option value="">{{ __('All Agents') }}</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ $agentId == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="subheader">{{ __('Conversion Rate') }}</div>
                <div class="h1 mb-0">{{ $conversionRate }}%</div>
                <div class="text-secondary">{{ $closedDeals }} {{ __('closed') }} / {{ $totalLeads }} {{ __('leads') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="subheader">{{ __('Total Leads') }}</div>
                <div class="h1 mb-0">{{ $totalLeads }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="subheader">{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Closed') }}</div>
                <div class="h1 mb-0">{{ $closedDeals }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Conversion Funnel -->
<div class="card mb-3" aria-label="{{ __('Conversion funnel chart showing lead progression through stages') }}">
    <div class="card-header">
        <h3 class="card-title">{{ __('Conversion Funnel') }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.exportFunnel', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary btn-sm">{{ __('Export CSV') }}</a>
        </div>
    </div>
    <div class="card-body">
        @php
            $funnelLabels = ['new' => __('New Leads'), 'contacted' => __('Contacted'), 'negotiating' => __('Negotiating'), 'offer_made' => __('Offer Made'), 'under_contract' => __('Under Contract'), 'closed_won' => __('Closed Won')];
            $maxFunnel = max(array_values($funnel)) ?: 1;
        @endphp
        @foreach($funnel as $stage => $count)
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
                <span>{{ $funnelLabels[$stage] ?? __(ucwords(str_replace('_', ' ', $stage))) }}</span>
                <span class="text-secondary">{{ $count }}</span>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="{{ round(($count / $maxFunnel) * 100) }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ ($funnelLabels[$stage] ?? __(ucwords(str_replace('_', ' ', $stage)))) . ': ' . $count }}" style="height: 20px;">
                <div class="progress-bar bg-primary" style="width: {{ round(($count / $maxFunnel) * 100) }}%">{{ $count }}</div>
            </div>
        </div>
        @endforeach
    </div>
    <noscript>
        <div class="card-body">
            <p>{{ __('See the data tables below for detailed numbers.') }}</p>
        </div>
    </noscript>
    <div class="visually-hidden" aria-label="{{ __('Chart data summary') }}">
        <p>{{ __('See the data tables below for detailed numbers.') }}</p>
    </div>
</div>

<!-- Pipeline Bottleneck -->
@if(auth()->user()->isAdmin() && $pipelineBottleneck->count())
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Pipeline Bottleneck Analysis') }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr><th>{{ __('Stage') }}</th><th>{{ __('Active Deals') }}</th><th>{{ __('Avg Days in Stage') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
                @foreach($pipelineBottleneck as $row)
                <tr>
                    <td>{{ \App\Models\Deal::stageLabel($row->stage) }}</td>
                    <td>{{ $row->deal_count }}</td>
                    <td>{{ round($row->avg_days, 1) }}</td>
                    <td>
                        @if($row->avg_days > 14)
                            <span class="badge bg-red-lt">{{ __('Critical') }}</span>
                        @elseif($row->avg_days > 7)
                            <span class="badge bg-yellow-lt">{{ __('Slow') }}</span>
                        @else
                            <span class="badge bg-green-lt">{{ __('Healthy') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Team Performance Leaderboard -->
@if(auth()->user()->isAdmin() && count($teamPerformance))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Team Performance Leaderboard') }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.exportTeamPerformance', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary btn-sm">{{ __('Export CSV') }}</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr><th>{{ __('Agent') }}</th><th>{{ __('Leads Contacted') }}</th><th>{{ __('Offers Made') }}</th><th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Closed') }}</th><th>{{ $modeTerms['money_label'] ?? __('Fees') }} {{ __('Generated') }}</th></tr>
            </thead>
            <tbody>
                @foreach($teamPerformance as $row)
                <tr>
                    <td>{{ $row->agent->name }}</td>
                    <td>{{ $row->leadsContacted }}</td>
                    <td>{{ $row->offersMade }}</td>
                    <td>{{ $row->dealsClosed }}</td>
                    <td>{{ Fmt::currency($row->feesGenerated) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Lead Source ROI -->
@if(auth()->user()->isAdmin() && count($leadSourceROI))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Lead Source ROI') }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr><th>{{ __('Source') }}</th><th>{{ __('Leads') }}</th><th>{{ __('Closed Deals') }}</th><th>{{ __('Monthly Budget') }}</th><th>{{ __('Cost/Lead') }}</th><th>{{ __('Cost/Deal') }}</th></tr>
            </thead>
            <tbody>
                @foreach($leadSourceROI as $row)
                <tr>
                    <td>{{ __(ucwords(str_replace('_', ' ', $row->source))) }}</td>
                    <td>{{ $row->leads }}</td>
                    <td>{{ $row->closed }}</td>
                    <td>{{ Fmt::currency($row->budget) }}</td>
                    <td>{{ $row->cost_per_lead > 0 ? Fmt::currency($row->cost_per_lead) : '-' }}</td>
                    <td>{{ $row->cost_per_deal > 0 ? Fmt::currency($row->cost_per_deal) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Leads by Source -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Leads by Source') }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.exportLeadsBySource', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary btn-sm">{{ __('Export CSV') }}</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr><th>{{ __('Source') }}</th><th>{{ __('Count') }}</th><th>{{ __('Percentage') }}</th></tr>
            </thead>
            <tbody>
                @foreach($leadsBySource as $source)
                <tr>
                    <td>{{ __(ucwords(str_replace('_', ' ', $source->lead_source))) }}</td>
                    <td>{{ $source->count }}</td>
                    <td>{{ $totalLeads > 0 ? round(($source->count / $totalLeads) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Top Agents -->
@if(auth()->user()->isAdmin() && count($topAgents))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Top Performing Agents') }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.exportTopAgents', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary btn-sm">{{ __('Export CSV') }}</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr><th>{{ __('Agent') }}</th><th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Closed') }}</th><th>{{ __('Total') }} {{ $modeTerms['money_label'] ?? __('Fees') }}</th></tr>
            </thead>
            <tbody>
                @foreach($topAgents as $row)
                <tr>
                    <td>{{ $row->agent->name ?? '-' }}</td>
                    <td>{{ $row->deals_closed }}</td>
                    <td>{{ Fmt::currency($row->total_fees) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- List Stacking Effectiveness (wholesale only) -->
@if(auth()->user()->isAdmin() && ($businessMode ?? 'wholesale') === 'wholesale')
<div class="card mb-3" aria-label="{{ __('List stacking effectiveness chart showing property list overlap') }}">
    <div class="card-header">
        <h3 class="card-title">{{ __('List Stacking Effectiveness') }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.exportListStacking') }}" class="btn btn-outline-secondary btn-sm">{{ __('Export CSV') }}</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row text-center mb-3">
            <div class="col-md-4">
                <div class="subheader">{{ __('1 List') }}</div>
                <div class="h2">{{ $stackDepth['1_list'] }}</div>
            </div>
            <div class="col-md-4">
                <div class="subheader">{{ __('2 Lists') }}</div>
                <div class="h2 text-warning">{{ $stackDepth['2_lists'] }}</div>
            </div>
            <div class="col-md-4">
                <div class="subheader">{{ __('3+ Lists') }}</div>
                <div class="h2 text-success">{{ $stackDepth['3_plus'] }}</div>
            </div>
        </div>
        @php
            $maxStack = max($stackDepth['1_list'], $stackDepth['2_lists'], $stackDepth['3_plus']) ?: 1;
        @endphp
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
                <span>{{ __('1 List') }}</span>
                <span>{{ $stackDepth['1_list'] }}</span>
            </div>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-azure" style="width: {{ round(($stackDepth['1_list'] / $maxStack) * 100) }}%"></div>
            </div>
        </div>
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
                <span>{{ __('2 Lists') }}</span>
                <span>{{ $stackDepth['2_lists'] }}</span>
            </div>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-yellow" style="width: {{ round(($stackDepth['2_lists'] / $maxStack) * 100) }}%"></div>
            </div>
        </div>
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
                <span>{{ __('3+ Lists (High Motivation)') }}</span>
                <span>{{ $stackDepth['3_plus'] }}</span>
            </div>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-green" style="width: {{ round(($stackDepth['3_plus'] / $maxStack) * 100) }}%"></div>
            </div>
        </div>
    </div>
    <div class="visually-hidden" aria-label="{{ __('Chart data summary') }}">
        <p>{{ __('See the data tables below for detailed numbers.') }}</p>
    </div>
</div>
@endif

<!-- Buyer Match Rate -->
@if(auth()->user()->isAdmin() && !empty($buyerMatchRate) && $buyerMatchRate['totalMatches'] > 0)
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Client Match Rate') : __('Buyer Match Rate') }}</h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="subheader">{{ __('Total Matches') }}</div>
                <div class="h2">{{ $buyerMatchRate['totalMatches'] }}</div>
            </div>
            <div class="col-md-3">
                <div class="subheader">{{ __('Notified') }}</div>
                <div class="h2">{{ $buyerMatchRate['notified'] }}</div>
                <div class="text-secondary">{{ $buyerMatchRate['totalMatches'] > 0 ? round(($buyerMatchRate['notified'] / $buyerMatchRate['totalMatches']) * 100, 1) : 0 }}%</div>
            </div>
            <div class="col-md-3">
                <div class="subheader">{{ __('Responded') }}</div>
                <div class="h2">{{ $buyerMatchRate['responded'] }}</div>
                <div class="text-secondary">{{ $buyerMatchRate['notified'] > 0 ? round(($buyerMatchRate['responded'] / $buyerMatchRate['notified']) * 100, 1) : 0 }}%</div>
            </div>
            <div class="col-md-3">
                <div class="subheader">{{ __('Interested') }}</div>
                <div class="h2">{{ $buyerMatchRate['interested'] }}</div>
                <div class="text-secondary">{{ $buyerMatchRate['responded'] > 0 ? round(($buyerMatchRate['interested'] / $buyerMatchRate['responded']) * 100, 1) : 0 }}%</div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Conversion Trend -->
@if(count($conversionTrend))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Conversion Trend (Monthly)') }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Month') }}</th>
                    <th>{{ __('Leads') }}</th>
                    <th>{{ __('Closed') }}</th>
                    <th>{{ __('Conversion Rate') }}</th>
                    <th>{{ __('Trend') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conversionTrend as $trend)
                <tr>
                    <td>{{ $trend->month }}</td>
                    <td>{{ $trend->leads }}</td>
                    <td>{{ $trend->closed }}</td>
                    <td><span class="badge bg-{{ $trend->rate >= 10 ? 'green' : ($trend->rate >= 5 ? 'yellow' : 'red') }}-lt">{{ $trend->rate }}%</span></td>
                    <td>
                        <div class="progress progress-sm" style="width:100px;" role="progressbar" aria-valuenow="{{ $trend->rate }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $trend->month . ': ' . $trend->rate . '%' }}">
                            <div class="progress-bar bg-primary" style="width: {{ min($trend->rate * 2, 100) }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Lead-to-Close Velocity -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Lead-to-Close Velocity') }}</h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <div class="subheader">{{ __('Average Days') }}</div>
                <div class="h1 mb-0">{{ $leadVelocity->avg }}</div>
                <div class="text-secondary small">{{ __('lead to close') }}</div>
            </div>
            <div class="col-md-4">
                <div class="subheader">{{ __('Fastest') }}</div>
                <div class="h2 text-green mb-0">{{ $leadVelocity->min }}</div>
                <div class="text-secondary small">{{ __('days') }}</div>
            </div>
            <div class="col-md-4">
                <div class="subheader">{{ __('Slowest') }}</div>
                <div class="h2 text-red mb-0">{{ $leadVelocity->max }}</div>
                <div class="text-secondary small">{{ __('days') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Agent Comparison Chart (admin only) -->
@if(auth()->user()->isAdmin() && count($agentComparison))
<div class="card mb-3" aria-label="{{ __('Agent comparison chart showing deals closed by each agent') }}">
    <div class="card-header">
        <h3 class="card-title">{{ __('Agent Comparison') }}</h3>
    </div>
    <div class="card-body">
        @foreach($agentComparison as $ac)
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
                <strong>{{ $ac->name }}</strong>
                <span>{{ $ac->closed }} {{ __('deals') }} &middot; {{ Fmt::currency($ac->fees) }}</span>
            </div>
            <div class="progress progress-sm" role="progressbar" aria-label="{{ $ac->name . ': ' . $ac->closed . ' ' . __('deals') }}">
                @php $maxClosed = collect($agentComparison)->max('closed'); @endphp
                <div class="progress-bar bg-primary" style="width: {{ $maxClosed > 0 ? round(($ac->closed / $maxClosed) * 100) : 0 }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="visually-hidden" aria-label="{{ __('Chart data summary') }}">
        <p>{{ __('See the data tables below for detailed numbers.') }}</p>
    </div>
</div>
@endif
@endsection
