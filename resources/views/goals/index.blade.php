@extends('layouts.app')

@section('title', __('Goals'))
@section('page-title', __('KPI Goal Tracking'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Goals') }}</li>
@endsection

@section('content')
<div class="container-xl">
    @if(auth()->user()->isAdmin())
    <div class="d-flex justify-content-end gap-2 mb-3">
        @if(auth()->user()->tenant->ai_enabled)
        <button type="button" class="btn btn-outline-purple" id="btn-ai-recommend-goals">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
            {{ __('AI Recommend Goals') }}
        </button>
        @endif
        <a href="{{ route('goals.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Create Goal') }}
        </a>
    </div>
    @endif

    {{-- Team Goals --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="3"/></svg>
                {{ __('Team Goals') }}
            </h3>
        </div>
        <div class="card-body">
            @if($teamGoals->isEmpty())
                <p class="text-secondary text-center py-3">{{ __('No team goals set.') }} @if(auth()->user()->isAdmin()) <a href="{{ route('goals.create') }}">{{ __('Create one') }}</a>@endif</p>
            @else
                <div class="row row-cards">
                    @foreach($teamGoals as $goal)
                        @include('goals._goal_card', ['goal' => $goal])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- My Goals --}}
    @if($myGoals->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 7v5l3 3"/></svg>
                {{ __('My Goals') }}
            </h3>
        </div>
        <div class="card-body">
            <div class="row row-cards">
                @foreach($myGoals as $goal)
                    @include('goals._goal_card', ['goal' => $goal])
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Admin: All User Goals --}}
    @if(auth()->user()->isAdmin() && $allUserGoals->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                {{ __('Individual Goals') }}
            </h3>
        </div>
        <div class="card-body">
            @foreach($allUserGoals as $userId => $goals)
                @php $goalUser = $goals->first()->user; @endphp
                <h4 class="mb-3">{{ $goalUser->name ?? __('Unknown User') }}</h4>
                <div class="row row-cards mb-4">
                    @foreach($goals as $goal)
                        @include('goals._goal_card', ['goal' => $goal])
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- AI Forecast Modal --}}
@if($aiEnabled)
<div class="modal modal-blur fade" id="forecast-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                    {{ __('AI Forecast') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="forecast-loading" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-purple"></div>
                    <span class="text-secondary ms-2">{{ __('Generating forecast...') }}</span>
                </div>
                <div id="forecast-result" style="display:none;">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-secondary small">{{ __('Projected Value') }}</div>
                            <div class="fw-bold fs-3" id="forecast-projected"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small">{{ __('Est. Completion') }}</div>
                            <div class="fw-bold" id="forecast-completion"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-secondary small">{{ __('Daily Rate') }}</div>
                        <div id="forecast-daily-rate"></div>
                    </div>
                    <div id="forecast-ai-insight" style="display:none;">
                        <hr>
                        <div class="text-secondary small mb-1">{{ __('AI Insight') }}</div>
                        <div id="forecast-ai-text" style="line-height:1.6;"></div>
                    </div>
                </div>
                <div id="forecast-error" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- AI Recommend Goals Modal --}}
@if(auth()->user()->tenant->ai_enabled && auth()->user()->isAdmin())
<div class="modal modal-blur fade" id="goals-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                    {{ __('AI Recommend Goals') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="goals-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is analyzing your metrics...') }}</p>
                </div>
                <div id="goals-ai-result" style="display: none;">
                    <div id="goals-ai-analysis" class="mb-3" style="line-height: 1.6;"></div>
                    <div id="goals-ai-cards"></div>
                </div>
                <div id="goals-ai-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="goals-ai-accept-btn" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    {{ __('Add Selected Goals') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    // AI Forecast buttons
    document.querySelectorAll('.btn-forecast').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var goalId = this.dataset.goalId;
            var modalEl = document.getElementById('forecast-modal');
            var modal = new bootstrap.Modal(modalEl);

            document.getElementById('forecast-loading').style.display = 'block';
            document.getElementById('forecast-result').style.display = 'none';
            document.getElementById('forecast-error').style.display = 'none';
            document.getElementById('forecast-ai-insight').style.display = 'none';

            modal.show();

            fetch('{{ url("/goals/forecast") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ goal_id: goalId })
            }).then(function(r) { return r.json(); }).then(function(res) {
                document.getElementById('forecast-loading').style.display = 'none';

                if (res.error) {
                    document.getElementById('forecast-error').style.display = 'block';
                    document.getElementById('forecast-error').textContent = res.error;
                    return;
                }

                document.getElementById('forecast-projected').textContent = parseFloat(res.projected_value).toLocaleString('{{ Fmt::jsLocale() }}', {maximumFractionDigits: 0});
                document.getElementById('forecast-completion').textContent = res.projected_completion_date || '{{ __("N/A") }}';
                document.getElementById('forecast-daily-rate').textContent = parseFloat(res.daily_rate).toLocaleString('{{ Fmt::jsLocale() }}', {maximumFractionDigits: 2}) + ' {{ __("per day") }}';
                document.getElementById('forecast-result').style.display = 'block';

                if (res.ai_insight) {
                    document.getElementById('forecast-ai-text').innerHTML = typeof window.renderAiMarkdown === 'function' ? window.renderAiMarkdown(res.ai_insight) : res.ai_insight.replace(/\n/g, '<br>');
                    document.getElementById('forecast-ai-insight').style.display = 'block';
                }
            }).catch(function() {
                document.getElementById('forecast-loading').style.display = 'none';
                document.getElementById('forecast-error').style.display = 'block';
                document.getElementById('forecast-error').textContent = '{{ __("Request failed. Please try again.") }}';
            });
        });
    });

    // Delete goal confirm
    document.querySelectorAll('.btn-delete-goal').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('{{ __("Are you sure you want to delete this goal?") }}')) {
                e.preventDefault();
            }
        });
    });

    // AI Recommend Goals
    var goalsAiBtn = document.getElementById('btn-ai-recommend-goals');
    if (goalsAiBtn) {
        var goalsAiModalEl = document.getElementById('goals-ai-modal');
        var goalsAiModal = new bootstrap.Modal(goalsAiModalEl);
        var goalsAiData = [];

        @php
            $metricLabelsJson = json_encode(\App\Models\Goal::metricLabels());
        @endphp
        var metricLabels = @json(\App\Models\Goal::metricLabels());
        var periodLabels = { weekly: '{{ __("Weekly") }}', monthly: '{{ __("Monthly") }}', quarterly: '{{ __("Quarterly") }}' };

        goalsAiModalEl.addEventListener('hide.bs.modal', function() {
            if (goalsAiModalEl.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });

        goalsAiBtn.addEventListener('click', function() {
            goalsAiData = [];
            document.getElementById('goals-ai-loading').style.display = 'block';
            document.getElementById('goals-ai-result').style.display = 'none';
            document.getElementById('goals-ai-error').style.display = 'none';
            document.getElementById('goals-ai-accept-btn').style.display = 'none';

            goalsAiModal.show();

            fetch('{{ url("/ai/goal-recommendations") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            }).then(function(r) { return r.json(); }).then(function(res) {
                document.getElementById('goals-ai-loading').style.display = 'none';

                if (res.error) {
                    document.getElementById('goals-ai-error').style.display = 'block';
                    document.getElementById('goals-ai-error').textContent = res.error;
                    return;
                }

                // Show analysis text
                var analysisEl = document.getElementById('goals-ai-analysis');
                if (res.analysis) {
                    analysisEl.innerHTML = typeof window.renderAiMarkdown === 'function'
                        ? window.renderAiMarkdown(res.analysis)
                        : res.analysis.replace(/\n/g, '<br>');
                    analysisEl.style.display = 'block';
                } else {
                    analysisEl.style.display = 'none';
                }

                // Render goal cards with checkboxes
                goalsAiData = res.goals || [];
                var cardsEl = document.getElementById('goals-ai-cards');

                if (goalsAiData.length > 0) {
                    var html = '<h4 class="mb-2">{{ __("Recommended Goals") }}</h4>';
                    goalsAiData.forEach(function(goal, i) {
                        var label = metricLabels[goal.metric] || goal.metric;
                        var period = periodLabels[goal.period] || goal.period;
                        var targetDisplay = goal.metric === 'revenue_earned'
                            ? '$' + parseFloat(goal.target_value).toLocaleString()
                            : parseFloat(goal.target_value).toLocaleString();
                        html += '<label class="d-flex align-items-start gap-2 p-2 border rounded mb-2" style="cursor: pointer;">';
                        html += '<input type="checkbox" class="form-check-input mt-1 ai-goal-check" data-index="' + i + '" checked>';
                        html += '<div class="flex-fill">';
                        html += '<div class="fw-bold">' + label + ' <span class="badge bg-purple-lt">' + period + '</span></div>';
                        html += '<div class="fs-3 text-primary">' + targetDisplay + '</div>';
                        if (goal.reason) {
                            html += '<div class="text-secondary small">' + goal.reason + '</div>';
                        }
                        html += '</div></label>';
                    });
                    cardsEl.innerHTML = html;
                    document.getElementById('goals-ai-accept-btn').style.display = 'inline-block';
                } else {
                    cardsEl.innerHTML = '<div class="text-secondary text-center py-2">{{ __("No structured goals could be generated. Try again.") }}</div>';
                }

                document.getElementById('goals-ai-result').style.display = 'block';
            }).catch(function() {
                document.getElementById('goals-ai-loading').style.display = 'none';
                document.getElementById('goals-ai-error').style.display = 'block';
                document.getElementById('goals-ai-error').textContent = '{{ __("Request failed. Please try again.") }}';
            });
        });

        document.getElementById('goals-ai-accept-btn').addEventListener('click', function() {
            var btn = this;
            var selected = [];
            document.querySelectorAll('.ai-goal-check:checked').forEach(function(cb) {
                var idx = parseInt(cb.dataset.index);
                if (goalsAiData[idx]) {
                    selected.push({
                        metric: goalsAiData[idx].metric,
                        target_value: goalsAiData[idx].target_value,
                        period: goalsAiData[idx].period
                    });
                }
            });

            if (selected.length === 0) {
                alert('{{ __("Please select at least one goal.") }}');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Creating...") }}';

            fetch('{{ url("/goals/store-from-ai") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ goals: selected })
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '{{ __("Add Selected Goals") }}';
                    alert(res.error || '{{ __("Failed to create goals.") }}');
                }
            }).catch(function() {
                btn.disabled = false;
                btn.innerHTML = '{{ __("Add Selected Goals") }}';
                alert('{{ __("Request failed. Please try again.") }}');
            });
        });
    }
});
</script>
@endpush
@endsection
