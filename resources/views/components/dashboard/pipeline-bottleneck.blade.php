@props(['pipelineBottleneck'])

@if($pipelineBottleneck->count())
<div class="card">
    <div class="card-header border-0">
        <h3 class="card-title">{{ __('Pipeline Bottleneck') }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr><th>{{ __('Stage') }}</th><th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s</th><th>{{ __('Avg Days') }}</th><th>{{ __('Status') }}</th></tr>
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
