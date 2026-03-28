@extends('layouts.app')

@section('title', __('Dashboard'))
@section('page-title')
    {{ __('Good') }} {{ now()->hour < 12 ? __('morning') : (now()->hour < 17 ? __('afternoon') : __('evening')) }}, {{ explode(' ', auth()->user()->name)[0] }}
@endsection

@section('content')
<div class="d-flex justify-content-end mb-3">
    <div class="dropdown">
        <button class="btn btn-ghost-secondary btn-sm" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="{{ __('Customize Dashboard') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.066 2.573c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.573 1.066c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.066 -2.573c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.573 -1.066z"/><circle cx="12" cy="12" r="3"/></svg>
            {{ __('Customize') }}
        </button>
        <div class="dropdown-menu dropdown-menu-end" style="min-width: 220px;">
            <h6 class="dropdown-header">{{ __('Show Widgets') }}</h6>
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="kpi-cards" {{ in_array('kpi_cards', $activeWidgets) ? 'checked' : '' }}> {{ __('KPI Cards') }}
            </label>
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="charts-row" {{ in_array('charts_row', $activeWidgets) ? 'checked' : '' }}> {{ __('Charts') }}
            </label>
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="pipeline-recent-tasks" {{ in_array('pipeline_recent_tasks', $activeWidgets) ? 'checked' : '' }}> {{ __('Pipeline & Recent') }}
            </label>
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="goals" {{ in_array('goals', $activeWidgets) ? 'checked' : '' }}> {{ __('Goals') }}
            </label>
            @if(auth()->user()->isAdmin())
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="roi-bottleneck" {{ in_array('roi_bottleneck', $activeWidgets) ? 'checked' : '' }}> {{ __('ROI & Bottleneck') }}
            </label>
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="team-leaderboard" {{ in_array('team_leaderboard', $activeWidgets) ? 'checked' : '' }}> {{ __('Team Leaderboard') }}
            </label>
            @endif
            @if(auth()->user()->isAdmin() && auth()->user()->tenant->ai_enabled)
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="ai-digest" {{ in_array('ai_digest', $activeWidgets) ? 'checked' : '' }}> {{ __('AI Weekly Digest') }}
            </label>
            @endif
            @if(auth()->user()->tenant->ai_enabled && !auth()->user()->isFieldScout())
            <label class="dropdown-item d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" data-widget="pipeline-health" {{ in_array('pipeline_health', $activeWidgets) ? 'checked' : '' }}> {{ __('Pipeline Health') }}
            </label>
            @endif
        </div>
    </div>
</div>

<!-- Primary KPI Cards -->
<div data-widget-section="kpi-cards">
<x-dashboard.kpi-cards
    :totalLeads="$totalLeads"
    :leadsThisMonth="$leadsThisMonth"
    :activeDeals="$activeDeals"
    :totalPipelineValue="$totalPipelineValue"
    :closedThisMonth="$closedThisMonth"
    :feesThisMonth="$feesThisMonth"
    :hotLeads="$hotLeads"
    :overdueTasks="$overdueTasks"
/>
</div>

<!-- Charts Row -->
<div class="row row-deck row-cards mb-4" data-widget-section="charts-row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">{{ __('Leads vs Closed') }} {{ $modeTerms['deal_label'] ?? __('Deals') }}s</h3>
                <div class="card-actions">
                    <span class="text-secondary small">{{ __('Last 6 months') }}</span>
                </div>
            </div>
            <div class="card-body pt-0">
                <div id="chart-monthly" style="height: 260px;" role="img" aria-label="{{ __('Bar chart showing new leads versus closed deals over the last 6 months') }}"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">{{ __('Lead Sources') }}</h3>
            </div>
            <div class="card-body pt-0 d-flex align-items-center justify-content-center">
                <div style="width: 100%; max-width: 260px;">
                    <div id="chart-sources" style="height: 260px;" role="img" aria-label="{{ __('Donut chart showing lead distribution by source') }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pipeline Value + Recent Leads + Upcoming Tasks -->
<div class="row row-deck row-cards" data-widget-section="pipeline-recent-tasks">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">{{ $modeTerms['pipeline_label'] ?? __('Pipeline') }} {{ __('by Stage') }}</h3>
            </div>
            <div class="card-body pt-0" id="pipeline-value">
                <p class="text-secondary">{{ __('Loading...') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <x-dashboard.recent-leads :recentLeads="$recentLeads" />
    </div>
    <div class="col-lg-4">
        <x-dashboard.upcoming-tasks :upcomingTasks="$upcomingTasks" />
    </div>
</div>

<!-- Goal Progress Widget -->
<div data-widget-section="goals">
<div class="row row-deck row-cards mt-4">
    <div class="col-lg-6">
        <x-dashboard.goals />
    </div>
</div>
</div>

<!-- Lead Source ROI + Pipeline Bottleneck + Team Leaderboard (Admin only) -->
@if(auth()->user()->isAdmin())
<div data-widget-section="roi-bottleneck">
<div class="row row-deck row-cards mt-4">
    <div class="col-lg-6">
        <x-dashboard.lead-source-roi />
    </div>
    <div class="col-lg-6">
        <x-dashboard.pipeline-bottleneck :pipelineBottleneck="$pipelineBottleneck" />
    </div>
</div>
</div>
<div data-widget-section="team-leaderboard">
<div class="row row-deck row-cards mt-4">
    <div class="col-lg-12">
        <x-dashboard.team-leaderboard :teamPerformance="$teamPerformance" />
    </div>
</div>
</div>
@endif

@if(auth()->user()->isAdmin() && auth()->user()->tenant->ai_enabled)
<div data-widget-section="ai-digest">
<div class="row row-deck row-cards mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                    {{ __('AI Weekly Digest') }}
                </h3>
                <button type="button" class="btn btn-outline-purple btn-sm" id="ai-digest-btn">{{ __('Generate Digest') }}</button>
            </div>
            <div class="card-body">
                <div id="ai-digest-loading" style="display:none;" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-purple"></div>
                    <span class="text-secondary ms-2">{{ __('AI is analyzing your week...') }}</span>
                </div>
                <div id="ai-digest-result" style="display:none;">
                    <div style="line-height:1.6;" id="ai-digest-text"></div>
                </div>
                <div id="ai-digest-placeholder" class="text-secondary text-center py-2">
                    {{ __('Click "Generate Digest" for an AI-powered summary of your business performance this week.') }}
                </div>
                <div id="ai-digest-error" class="alert alert-danger" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
</div>
@endif

@if(auth()->user()->tenant->ai_enabled && !auth()->user()->isFieldScout())
<div data-widget-section="pipeline-health">
<div class="row row-deck row-cards mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-activity-heartbeat me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4"/></svg>
                    {{ __('Pipeline Health') }}
                </h3>
                <button type="button" class="btn btn-outline-purple btn-sm" id="ai-pipeline-health-btn">{{ __('Run Analysis') }}</button>
            </div>
            <div class="card-body">
                <div id="ai-pipeline-health-loading" style="display:none;" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-purple"></div>
                    <span class="text-secondary ms-2">{{ __('AI is analyzing your pipeline...') }}</span>
                </div>
                <div id="ai-pipeline-health-result" style="display:none;">
                    <div style="line-height:1.6;" id="ai-pipeline-health-text"></div>
                </div>
                <div id="ai-pipeline-health-placeholder" class="text-secondary text-center py-2">
                    {{ __('Click "Run Analysis" for an AI-powered assessment of your pipeline health, bottlenecks, and recommendations.') }}
                </div>
                <div id="ai-pipeline-health-error" class="alert alert-danger" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
</div>
@endif

{{-- Plugin dashboard widgets --}}
@php $pluginWidgets = app(\App\Services\HookManager::class)->getDashboardWidgets(); @endphp
@foreach($pluginWidgets as $widget)
    @include($widget['view'])
@endforeach

@push('scripts')
<script>
// Dashboard widget toggle — DB is sole source of truth
(function() {
    // Apply DB-driven widget visibility (server-rendered checked state is source of truth)
    document.querySelectorAll('[data-widget]').forEach(function(checkbox) {
        var section = document.querySelector('[data-widget-section="' + checkbox.dataset.widget + '"]');
        if (section && !checkbox.checked) {
            section.style.display = 'none';
        }

        checkbox.addEventListener('change', function() {
            var section = document.querySelector('[data-widget-section="' + this.dataset.widget + '"]');
            if (section) {
                section.style.display = this.checked ? '' : 'none';
            }
            // Persist to DB
            var activeWidgets = [];
            document.querySelectorAll('[data-widget]').forEach(function(w) {
                if (w.checked) activeWidgets.push(w.dataset.widget.replace(/-/g, '_'));
            });
            fetch('{{ url("/dashboard/widgets") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ widgets: activeWidgets })
            });
        });
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
const baseUrl = '{{ route('dashboard.data') }}';

function loadWidget(widget, el, renderFn) {
    if (!el) return;
    el.innerHTML = '<p class="text-secondary text-center py-3">{{ __('Loading...') }}</p>';
    fetch(baseUrl + '?widget=' + widget, { headers })
        .then(r => r.json())
        .then(data => renderFn(data, el))
        .catch(() => { el.innerHTML = '<div class="text-center text-danger py-4">{{ __('Failed to load.') }}</div>'; });
}

// Monthly chart
loadWidget('monthly', document.getElementById('chart-monthly'), function(data, el) {
    el.innerHTML = '';
    new ApexCharts(el, {
        chart: {
            type: 'bar',
            height: 260,
            fontFamily: 'inherit',
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 600 },
        },
        series: [
            { name: '{{ __('New Leads') }}', data: data.leadsPerMonth },
            { name: '{{ __('Closed') }} {{ $modeTerms['deal_label'] ?? __('Deals') }}s', data: data.dealsPerMonth },
        ],
        colors: ['#206bc4', '#2fb344'],
        plotOptions: {
            bar: { columnWidth: '50%', borderRadius: 4 }
        },
        xaxis: {
            categories: data.months,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#667382', fontSize: '12px' } }
        },
        yaxis: {
            labels: { style: { colors: '#667382', fontSize: '12px' } }
        },
        grid: {
            strokeDashArray: 4,
            borderColor: '#e6e8eb',
            padding: { top: -10, right: 0, bottom: -10, left: 0 }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px',
            markers: { radius: 12 },
        },
        dataLabels: { enabled: false },
        tooltip: {
            theme: 'light',
            y: { formatter: val => val }
        },
    }).render();
});

// Lead sources donut
loadWidget('sources', document.getElementById('chart-sources'), function(data, el) {
    el.innerHTML = '';
    const sourceLabels = data.leadSources.map(s => s.lead_source.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
    const sourceData = data.leadSources.map(s => s.count);

    new ApexCharts(el, {
        chart: {
            type: 'donut',
            height: 260,
            fontFamily: 'inherit',
            animations: { enabled: true, easing: 'easeinout', speed: 600 },
        },
        series: sourceData,
        labels: sourceLabels,
        colors: ['#206bc4', '#2fb344', '#f76707', '#d63939', '#ae3ec9', '#667382'],
        legend: {
            position: 'bottom',
            fontSize: '12px',
            markers: { radius: 12 },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: '{{ __('Total') }}',
                            fontSize: '12px',
                            color: '#667382',
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        stroke: { width: 2 },
        tooltip: {
            fillSeriesColor: false,
        },
    }).render();
});

// Pipeline value by stage
loadWidget('pipeline', document.getElementById('pipeline-value'), function(data, el) {
    @php
        $stageColors = ['bg-azure','bg-blue','bg-indigo','bg-purple','bg-green','bg-teal','bg-cyan','bg-yellow','bg-lime','bg-pink','bg-orange'];
    @endphp
    const stages = {
        @foreach(\App\Models\Deal::stageLabels() as $key => $label)
        '{{ $key }}': { label: '{{ $label }}', color: '{{ $stageColors[$loop->index % count($stageColors)] }}' },
        @endforeach
    };

    if (data.pipelineValue && data.pipelineValue.length) {
        let totalPipeline = data.pipelineValue.reduce((sum, pv) => sum + Number(pv.total), 0);
        let pvHtml = '';
        data.pipelineValue.forEach(pv => {
            const info = stages[pv.stage] || { label: pv.stage.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), color: 'bg-secondary' };
            const val = Number(pv.total);
            const pct = totalPipeline > 0 ? Math.round((val / totalPipeline) * 100) : 0;
            pvHtml += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="d-flex align-items-center">
                            <span class="legend-dot ${info.color} me-2"></span>
                            <span>${info.label}</span>
                        </span>
                        <span class="fw-bold">{{ Fmt::currencySymbol() }}${val.toLocaleString('{{ Fmt::jsLocale() }}')}</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar ${info.color}" style="width: ${pct}%" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            `;
        });
        el.innerHTML = pvHtml;
    } else {
        el.innerHTML = '<div class="text-center text-secondary py-4">{{ __('No active') }} {{ strtolower($modeTerms['pipeline_label'] ?? __('deals')) }}.</div>';
    }
});

// Lead Source ROI (admin only)
// AI Weekly Digest
@if(auth()->user()->isAdmin() && auth()->user()->tenant->ai_enabled)
(function() {
    var digestBtn = document.getElementById('ai-digest-btn');
    if (!digestBtn) return;
    digestBtn.addEventListener('click', function() {
        var loading = document.getElementById('ai-digest-loading');
        var result = document.getElementById('ai-digest-result');
        var placeholder = document.getElementById('ai-digest-placeholder');
        var errorEl = document.getElementById('ai-digest-error');

        digestBtn.disabled = true;
        loading.style.display = 'block';
        result.style.display = 'none';
        placeholder.style.display = 'none';
        errorEl.style.display = 'none';

        fetch('{{ route('ai.weeklyDigest') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: '{}'
        }).then(function(r) { return r.json(); }).then(function(res) {
            loading.style.display = 'none';
            if (res.error) {
                errorEl.style.display = 'block';
                errorEl.textContent = res.error;
            } else {
                document.getElementById('ai-digest-text').innerHTML = window.renderAiMarkdown(res.digest || '');
                result.style.display = 'block';
            }
        }).catch(function() {
            loading.style.display = 'none';
            errorEl.style.display = 'block';
            errorEl.textContent = '{{ __('Request failed. Please try again.') }}';
        }).finally(function() {
            digestBtn.disabled = false;
        });
    });
})();
@endif

// AI Pipeline Health
@if(auth()->user()->tenant->ai_enabled && !auth()->user()->isFieldScout())
(function() {
    var healthBtn = document.getElementById('ai-pipeline-health-btn');
    if (!healthBtn) return;
    healthBtn.addEventListener('click', function() {
        var loading = document.getElementById('ai-pipeline-health-loading');
        var result = document.getElementById('ai-pipeline-health-result');
        var placeholder = document.getElementById('ai-pipeline-health-placeholder');
        var errorEl = document.getElementById('ai-pipeline-health-error');

        healthBtn.disabled = true;
        loading.style.display = 'block';
        result.style.display = 'none';
        placeholder.style.display = 'none';
        errorEl.style.display = 'none';

        fetch('{{ url("/ai/pipeline-health") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: '{}'
        }).then(function(r) { return r.json(); }).then(function(res) {
            loading.style.display = 'none';
            if (res.error) {
                errorEl.style.display = 'block';
                errorEl.textContent = res.error;
            } else {
                document.getElementById('ai-pipeline-health-text').innerHTML = window.renderAiMarkdown(res.analysis || '');
                result.style.display = 'block';
            }
        }).catch(function() {
            loading.style.display = 'none';
            errorEl.style.display = 'block';
            errorEl.textContent = '{{ __("Request failed. Please try again.") }}';
        }).finally(function() {
            healthBtn.disabled = false;
        });
    });
})();
@endif

@if(auth()->user()->isAdmin())
loadWidget('lead_source_roi', document.getElementById('lead-source-roi-container'), function(data, el) {
    if (data.leadSourceROI && data.leadSourceROI.length) {
        let html = '<div class="table-responsive"><table class="table table-vcenter card-table table-sm">';
        html += '<thead><tr><th>{{ __('Source') }}</th><th>{{ __('Leads') }}</th><th>{{ __('Closed') }}</th><th>{{ __('Budget') }}</th><th>{{ __('$/Lead') }}</th><th>{{ __('$/Deal') }}</th></tr></thead><tbody>';
        data.leadSourceROI.forEach(row => {
            const sourceName = row.source.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            html += '<tr>' +
                '<td>' + sourceName + '</td>' +
                '<td>' + row.leads + '</td>' +
                '<td>' + row.closed + '</td>' +
                '<td>$' + Number(row.budget).toLocaleString('{{ Fmt::jsLocale() }}') + '</td>' +
                '<td>' + (row.cost_per_lead > 0 ? '{{ Fmt::currencySymbol() }}' + row.cost_per_lead.toFixed(2) : '-') + '</td>' +
                '<td>' + (row.cost_per_deal > 0 ? '{{ Fmt::currencySymbol() }}' + row.cost_per_deal.toFixed(2) : '-') + '</td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
    } else {
        el.innerHTML = '<div class="card-body text-center text-secondary py-4">{{ __('No lead source data. Configure costs in Settings.') }}</div>';
    }
});
@endif
</script>
@endpush
@endsection
