@extends('reports.pdf.layout')

@section('title', 'Lead Report')
@section('report-title', 'Lead Report')

@section('content')
    {{-- Summary cards --}}
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-value">{{ number_format($totalLeads) }}</div>
            <div class="card-label">Total Leads</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ $leadsBySource->count() }}</div>
            <div class="card-label">Lead Sources</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ $leadsByStatus->count() }}</div>
            <div class="card-label">Statuses</div>
        </div>
        <div class="summary-card">
            <div class="card-value">
                {{ $totalLeads > 0 ? number_format($totalLeads / max($leadsBySource->count(), 1), 1) : 0 }}
            </div>
            <div class="card-label">Avg per Source</div>
        </div>
    </div>

    {{-- Leads by Source --}}
    <div class="report-section">
        <h2>Leads by Source</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Source</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leadsBySource as $index => $source)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $source->lead_source)) }}</td>
                        <td class="text-right">{{ number_format($source->count) }}</td>
                        <td class="text-right">
                            {{ $totalLeads > 0 ? number_format(($source->count / $totalLeads) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding:20px; color:#94a3b8;">
                            No lead data found for the selected date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($leadsBySource->count())
                <tfoot>
                    <tr>
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ number_format($totalLeads) }}</td>
                        <td class="text-right">100%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Leads by Status --}}
    <div class="report-section">
        <h2>Leads by Status</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Status</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leadsByStatus as $index => $status)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $status->status)) }}</td>
                        <td class="text-right">{{ number_format($status->count) }}</td>
                        <td class="text-right">
                            {{ $totalLeads > 0 ? number_format(($status->count / $totalLeads) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding:20px; color:#94a3b8;">
                            No lead data found for the selected date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($leadsByStatus->count())
                <tfoot>
                    <tr>
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ number_format($totalLeads) }}</td>
                        <td class="text-right">100%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection
