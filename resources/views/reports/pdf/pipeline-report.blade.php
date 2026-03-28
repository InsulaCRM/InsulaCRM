@extends('reports.pdf.layout')

@section('title', 'Pipeline Report')
@section('report-title', 'Pipeline Report')

@section('content')
    {{-- Summary cards --}}
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-value">{{ Fmt::currency($totalPipelineValue) }}</div>
            <div class="card-label">Active Pipeline Value</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ Fmt::currency($totalPipelineFees) }}</div>
            <div class="card-label">Potential Fees</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ number_format($closedWon) }}</div>
            <div class="card-label">Deals Won</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ Fmt::currency($totalFeesClosed) }}</div>
            <div class="card-label">Fees Earned</div>
        </div>
    </div>

    {{-- Pipeline by Stage --}}
    <div class="report-section">
        <h2>Pipeline by Stage</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th class="text-center">Deals</th>
                    <th class="text-right">Total Value</th>
                    <th class="text-right">Total Fees</th>
                    <th class="text-right">Avg Days in Stage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orderedStages as $stage)
                    <tr>
                        <td>{{ $stage->label }}</td>
                        <td class="text-center">{{ number_format($stage->deal_count) }}</td>
                        <td class="text-right">{{ Fmt::currency($stage->total_value) }}</td>
                        <td class="text-right">{{ Fmt::currency($stage->total_fees) }}</td>
                        <td class="text-right">
                            {{ $stage->avg_days_in_stage !== null ? number_format($stage->avg_days_in_stage, 1) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding:20px; color:#94a3b8;">
                            No deal data found for the selected date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Win/Loss Summary --}}
    <div class="report-section">
        <h2>Win / Loss Summary</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="text-right">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Deals Won</td>
                    <td class="text-right">{{ number_format($closedWon) }}</td>
                </tr>
                <tr>
                    <td>Deals Lost</td>
                    <td class="text-right">{{ number_format($closedLost) }}</td>
                </tr>
                <tr>
                    <td>Win Rate</td>
                    <td class="text-right">
                        {{ ($closedWon + $closedLost) > 0 ? number_format(($closedWon / ($closedWon + $closedLost)) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td>Total Fees from Won Deals</td>
                    <td class="text-right">{{ Fmt::currency($totalFeesClosed) }}</td>
                </tr>
                <tr>
                    <td>Active Pipeline Value</td>
                    <td class="text-right">{{ Fmt::currency($totalPipelineValue) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
