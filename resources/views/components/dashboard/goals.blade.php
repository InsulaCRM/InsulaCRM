@props(['goals' => null])

@php
    // If goals not passed, fetch top 3 active goals
    if ($goals === null) {
        $user = auth()->user();
        $goals = \App\Models\Goal::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->orderBy('end_date')
            ->limit(3)
            ->get();
    }
@endphp

@if($goals->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="3"/></svg>
            {{ __('Goal Progress') }}
        </h3>
        <div class="card-actions">
            <a href="{{ route('goals.index') }}" class="btn btn-ghost-primary btn-sm">{{ __('View All') }}</a>
        </div>
    </div>
    <div class="card-body">
        @foreach($goals as $goal)
            @php
                $currentValue = $goal->getCurrentValue();
                $progressPct = $goal->getProgressPercentage();
                $paceStatus = $goal->getPaceStatus();
                $isMonetary = in_array($goal->metric, ['revenue_earned']);

                $paceColor = match($paceStatus) {
                    'ahead' => 'text-green',
                    'on_track' => 'text-blue',
                    'behind' => 'text-red',
                    default => 'text-blue',
                };
                $barColor = match($paceStatus) {
                    'ahead' => 'bg-green',
                    'on_track' => 'bg-blue',
                    'behind' => $progressPct < 30 ? 'bg-red' : 'bg-yellow',
                    default => 'bg-blue',
                };
                $paceBadge = match($paceStatus) {
                    'ahead' => 'bg-green-lt',
                    'on_track' => 'bg-blue-lt',
                    'behind' => 'bg-red-lt',
                    default => 'bg-blue-lt',
                };
                $paceLabel = match($paceStatus) {
                    'ahead' => __('Ahead'),
                    'on_track' => __('On Track'),
                    'behind' => __('Behind'),
                    default => __('On Track'),
                };
            @endphp
            <div class="mb-3{{ !$loop->last ? '' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-medium">{{ \App\Models\Goal::metricLabel($goal->metric) }}</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small {{ $paceColor }}">{{ round($progressPct) }}%</span>
                        <span class="badge {{ $paceBadge }}" style="font-size: 0.7rem;">{{ $paceLabel }}</span>
                    </div>
                </div>
                <div class="progress progress-sm" style="height: 6px;">
                    <div class="progress-bar {{ $barColor }}" style="width: {{ $progressPct }}%" role="progressbar" aria-valuenow="{{ round($progressPct) }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-secondary" style="font-size: 0.75rem;">
                        @if($isMonetary){{ Fmt::currency($currentValue, 0) }}@else{{ number_format($currentValue) }}@endif
                        / @if($isMonetary){{ Fmt::currency($goal->target_value, 0) }}@else{{ number_format($goal->target_value) }}@endif
                    </span>
                    <span class="text-secondary" style="font-size: 0.75rem;">{{ __(ucfirst($goal->period)) }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
