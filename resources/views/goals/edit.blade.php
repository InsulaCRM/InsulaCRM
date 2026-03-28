@extends('layouts.app')

@section('title', __('Edit Goal'))
@section('page-title', __('Edit Goal'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('goals.index') }}">{{ __('Goals') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="container-xl">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('goals.update', $goal) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Edit KPI Goal') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('Metric') }}</label>
                                <select name="metric" class="form-select @error('metric') is-invalid @enderror" required>
                                    <option value="">{{ __('-- Select Metric --') }}</option>
                                    @foreach(\App\Models\Goal::metricLabels() as $key => $label)
                                        <option value="{{ $key }}" {{ old('metric', $goal->metric) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('metric') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('Target Value') }}</label>
                                <input type="number" name="target_value" class="form-control @error('target_value') is-invalid @enderror" value="{{ old('target_value', $goal->target_value) }}" min="1" step="any" required>
                                @error('target_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('Period') }}</label>
                                <select name="period" id="period-select" class="form-select @error('period') is-invalid @enderror" required>
                                    <option value="">{{ __('-- Select Period --') }}</option>
                                    <option value="weekly" {{ old('period', $goal->period) === 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                                    <option value="monthly" {{ old('period', $goal->period) === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                    <option value="quarterly" {{ old('period', $goal->period) === 'quarterly' ? 'selected' : '' }}>{{ __('Quarterly') }}</option>
                                    <option value="yearly" {{ old('period', $goal->period) === 'yearly' ? 'selected' : '' }}>{{ __('Yearly') }}</option>
                                </select>
                                @error('period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div id="date-range-display" class="form-text text-secondary mt-1" style="display:none;"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Assign To') }}</label>
                                <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                    <option value="">{{ __('Team Goal (everyone)') }}</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ old('user_id', $goal->user_id) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                                @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $goal->is_active) ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Current Progress Display --}}
                        @php
                            $currentValue = $goal->getCurrentValue();
                            $progressPct = $goal->getProgressPercentage();
                            $paceStatus = $goal->getPaceStatus();
                        @endphp
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>{{ __('Current Progress') }}</strong>
                                <span class="badge {{ $paceStatus === 'ahead' ? 'bg-green-lt' : ($paceStatus === 'behind' ? 'bg-red-lt' : 'bg-blue-lt') }}">{{ __(ucfirst(str_replace('_', ' ', $paceStatus))) }}</span>
                            </div>
                            <div>{{ number_format($currentValue) }} / {{ number_format($goal->target_value) }} ({{ round($progressPct, 1) }}%)</div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar {{ $paceStatus === 'behind' ? 'bg-red' : 'bg-green' }}" style="width: {{ $progressPct }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('goals.index') }}" class="btn btn-ghost-secondary me-2">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Update Goal') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var periodSelect = document.getElementById('period-select');
    var rangeDisplay = document.getElementById('date-range-display');

    function updateDateRange() {
        var period = periodSelect.value;
        if (!period) {
            rangeDisplay.style.display = 'none';
            return;
        }

        var now = new Date();
        var start, end;

        switch (period) {
            case 'weekly':
                var day = now.getDay();
                var diff = now.getDate() - day + (day === 0 ? -6 : 1);
                start = new Date(now.getFullYear(), now.getMonth(), diff);
                end = new Date(start);
                end.setDate(start.getDate() + 6);
                break;
            case 'monthly':
                start = new Date(now.getFullYear(), now.getMonth(), 1);
                end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                break;
            case 'quarterly':
                var qMonth = Math.floor(now.getMonth() / 3) * 3;
                start = new Date(now.getFullYear(), qMonth, 1);
                end = new Date(now.getFullYear(), qMonth + 3, 0);
                break;
            case 'yearly':
                start = new Date(now.getFullYear(), 0, 1);
                end = new Date(now.getFullYear(), 11, 31);
                break;
        }

        var opts = { month: 'short', day: 'numeric', year: 'numeric' };
        rangeDisplay.textContent = start.toLocaleDateString('{{ Fmt::jsLocale() }}', opts) + ' - ' + end.toLocaleDateString('{{ Fmt::jsLocale() }}', opts);
        rangeDisplay.style.display = 'block';
    }

    periodSelect.addEventListener('change', updateDateRange);
    updateDateRange();
});
</script>
@endpush
@endsection
