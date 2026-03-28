@props(['upcomingTasks'])

<div class="card">
    <div class="card-header border-0">
        <h3 class="card-title">{{ __('Upcoming Tasks') }}</h3>
        <div class="card-actions">
            <span class="badge {{ $upcomingTasks->count() > 0 ? 'bg-primary-lt' : 'bg-secondary-lt' }}">{{ $upcomingTasks->count() }}</span>
        </div>
    </div>
    <div class="list-group list-group-flush list-group-hoverable">
        @forelse($upcomingTasks as $task)
        <a href="{{ route('leads.show', $task->lead_id) }}" class="list-group-item list-group-item-action">
            <div class="row align-items-center">
                <div class="col-auto">
                    @if($task->is_overdue)
                        <span class="status-dot status-dot-animated bg-danger d-block"></span>
                    @else
                        <span class="status-dot bg-primary d-block"></span>
                    @endif
                </div>
                <div class="col text-truncate">
                    <div class="d-block text-truncate">{{ $task->title }}</div>
                    <div class="d-block text-secondary text-truncate small mt-n1">
                        {{ $task->lead->full_name ?? '-' }}
                        &middot;
                        <span class="{{ $task->is_overdue ? 'text-danger fw-bold' : '' }}">
                            {{ $task->due_date->format('M d') }}
                            @if($task->is_overdue) ({{ __('Overdue') }}) @endif
                        </span>
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="list-group-item text-center text-secondary py-4">
            {{ __("No upcoming tasks. You're all caught up!") }}
        </div>
        @endforelse
    </div>
</div>
