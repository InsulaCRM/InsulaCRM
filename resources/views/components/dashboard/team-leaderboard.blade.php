@props(['teamPerformance'])

@if($teamPerformance->count())
<div class="card">
    <div class="card-header border-0">
        <h3 class="card-title">{{ __('Team Leaderboard') }}</h3>
        <div class="card-actions">
            <span class="text-secondary small">{{ __('This Month') }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr><th>{{ __('Agent') }}</th><th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Closed') }}</th><th>{{ $modeTerms['money_label'] ?? __('Fees') }}</th></tr>
            </thead>
            <tbody>
                @foreach($teamPerformance as $row)
                <tr>
                    <td>{{ $row->agent->name }}</td>
                    <td>{{ $row->dealsClosed }}</td>
                    <td>{{ Fmt::currency($row->feesGenerated) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
