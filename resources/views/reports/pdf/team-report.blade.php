@extends('reports.pdf.layout')

@section('title', 'Team Performance Report')
@section('report-title', 'Team Performance Report')

@section('content')
    {{-- Summary cards --}}
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-value">{{ $teamPerformance->count() }}</div>
            <div class="card-label">Team Members</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ number_format($teamPerformance->sum('leadCount')) }}</div>
            <div class="card-label">Total Leads</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ number_format($teamPerformance->sum('dealsClosed')) }}</div>
            <div class="card-label">Deals Closed</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ Fmt::currency($teamPerformance->sum('feesGenerated')) }}</div>
            <div class="card-label">Total Fees</div>
        </div>
    </div>

    {{-- Team Performance Table --}}
    <div class="report-section">
        <h2>Agent Performance Ranking</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:45px">Rank</th>
                    <th>Agent</th>
                    <th class="text-center">Leads</th>
                    <th class="text-center">Deals</th>
                    <th class="text-center">Closed</th>
                    <th class="text-center">Activities</th>
                    <th class="text-right">Fees Generated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teamPerformance as $index => $row)
                    <tr>
                        <td class="text-center">
                            @php $rank = $index + 1; @endphp
                            <span class="rank-badge {{ $rank <= 3 ? 'rank-' . $rank : 'rank-default' }}">
                                {{ $rank }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $row->agent->name }}</strong>
                            <br>
                            <span style="font-size:11px; color:#64748b;">{{ ucfirst(str_replace('_', ' ', $row->agent->role->name ?? '')) }}</span>
                        </td>
                        <td class="text-center">{{ number_format($row->leadCount) }}</td>
                        <td class="text-center">{{ number_format($row->dealCount) }}</td>
                        <td class="text-center">{{ number_format($row->dealsClosed) }}</td>
                        <td class="text-center">{{ number_format($row->activitiesLogged) }}</td>
                        <td class="text-right">{{ Fmt::currency($row->feesGenerated) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding:20px; color:#94a3b8;">
                            No team members found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($teamPerformance->count())
                <tfoot>
                    <tr>
                        <td colspan="2">Totals</td>
                        <td class="text-center">{{ number_format($teamPerformance->sum('leadCount')) }}</td>
                        <td class="text-center">{{ number_format($teamPerformance->sum('dealCount')) }}</td>
                        <td class="text-center">{{ number_format($teamPerformance->sum('dealsClosed')) }}</td>
                        <td class="text-center">{{ number_format($teamPerformance->sum('activitiesLogged')) }}</td>
                        <td class="text-right">{{ Fmt::currency($teamPerformance->sum('feesGenerated')) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection
