@php
    $currentValue = $goal->getCurrentValue();
    $progressPct = $goal->getProgressPercentage();
    $paceStatus = $goal->getPaceStatus();

    $paceConfig = match($paceStatus) {
        'ahead' => ['label' => __('Ahead'), 'color' => 'bg-green', 'text' => 'text-green', 'badge' => 'bg-green-lt'],
        'on_track' => ['label' => __('On Track'), 'color' => 'bg-blue', 'text' => 'text-blue', 'badge' => 'bg-blue-lt'],
        'behind' => ['label' => __('Behind'), 'color' => 'bg-red', 'text' => 'text-red', 'badge' => 'bg-red-lt'],
        default => ['label' => __('On Track'), 'color' => 'bg-blue', 'text' => 'text-blue', 'badge' => 'bg-blue-lt'],
    };

    // Progress bar color
    if ($progressPct >= 75) {
        $barColor = 'bg-green';
    } elseif ($progressPct >= 40) {
        $barColor = $paceStatus === 'behind' ? 'bg-yellow' : 'bg-blue';
    } else {
        $barColor = $paceStatus === 'behind' ? 'bg-red' : 'bg-blue';
    }

    $isMonetary = in_array($goal->metric, ['revenue_earned']);
@endphp

<div class="col-md-6 col-lg-4">
    <div class="card card-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h4 class="card-title mb-0">{{ \App\Models\Goal::metricLabel($goal->metric) }}</h4>
                <span class="badge {{ $paceConfig['badge'] }}">{{ $paceConfig['label'] }}</span>
            </div>

            <div class="d-flex align-items-baseline mb-2">
                <span class="fs-2 fw-bold {{ $paceConfig['text'] }}">
                    @if($isMonetary)
                        {{ Fmt::currency($currentValue, 0) }}
                    @else
                        {{ number_format($currentValue) }}
                    @endif
                </span>
                <span class="text-secondary ms-1">
                    / @if($isMonetary){{ Fmt::currency($goal->target_value, 0) }}@else{{ number_format($goal->target_value) }}@endif
                </span>
            </div>

            <div class="progress progress-sm mb-2" style="height: 8px;">
                <div class="progress-bar {{ $barColor }}" style="width: {{ $progressPct }}%" role="progressbar" aria-valuenow="{{ round($progressPct) }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="d-flex justify-content-between mb-3">
                <span class="text-secondary small">{{ round($progressPct, 1) }}% {{ __('complete') }}</span>
                <span class="text-secondary small">{{ __(ucfirst($goal->period)) }}</span>
            </div>

            <div class="text-secondary small mb-2">
                {{ $goal->start_date->format('M d') }} - {{ $goal->end_date->format('M d, Y') }}
            </div>

            @if($goal->user)
            <div class="text-secondary small mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                {{ $goal->user->name }}
            </div>
            @endif

            <div class="d-flex gap-1 mt-2">
                @if(isset($aiEnabled) && $aiEnabled)
                <button type="button" class="btn btn-outline-purple btn-sm btn-forecast" data-goal-id="{{ $goal->id }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                    {{ __('Forecast') }}
                </button>
                @endif

                @if(auth()->user()->isAdmin())
                <a href="{{ route('goals.edit', $goal) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit') }}</a>
                <form action="{{ route('goals.destroy', $goal) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm btn-delete-goal">{{ __('Delete') }}</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
