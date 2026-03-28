@extends('layouts.app')

@section('title', __('AI History') . ' — ' . ucwords(str_replace('_', ' ', $aiLog->type)))
@section('page-title', __('AI History'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('ai-log.index') }}">{{ __('AI History') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __(ucwords(str_replace('_', ' ', $aiLog->type))) }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('AI Output') }}</h3>
            </div>
            <div class="card-body">
                <div id="ai-log-content" style="line-height: 1.7;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Details') }}</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">{{ __('Type') }}</dt>
                    <dd class="col-7">
                        @php
                            $typeColors = [
                                'digest' => 'bg-blue-lt',
                                'pipeline_health' => 'bg-cyan-lt',
                                'lead_snapshot' => 'bg-green-lt',
                                'deal_analysis' => 'bg-purple-lt',
                                'stage_advice' => 'bg-indigo-lt',
                                'score' => 'bg-orange-lt',
                                'dnc_risk' => 'bg-red-lt',
                                'stale_deal_alert' => 'bg-yellow-lt',
                                'stage_change_summary' => 'bg-pink-lt',
                            ];
                            $badgeClass = $typeColors[$aiLog->type] ?? 'bg-secondary-lt';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ __(ucwords(str_replace('_', ' ', $aiLog->type))) }}</span>
                    </dd>

                    <dt class="col-5">{{ __('Date') }}</dt>
                    <dd class="col-7">{{ $aiLog->created_at->format('M j, Y g:i A') }}</dd>

                    <dt class="col-5">{{ __('User') }}</dt>
                    <dd class="col-7">{{ $aiLog->user?->name ?? __('System') }}</dd>

                    @if($aiLog->prompt_summary)
                    <dt class="col-5">{{ __('Context') }}</dt>
                    <dd class="col-7">{{ $aiLog->prompt_summary }}</dd>
                    @endif

                    @if($aiLog->model_type)
                    <dt class="col-5">{{ __('Related To') }}</dt>
                    <dd class="col-7">
                        @if($aiLog->subject_url)
                            <a href="{{ $aiLog->subject_url }}">{{ $aiLog->subject_label }}</a>
                        @else
                            {{ $aiLog->subject_label }}
                        @endif
                    </dd>
                    @endif

                    @if($aiLog->metadata)
                    <dt class="col-12 mt-2">{{ __('Metadata') }}</dt>
                    <dd class="col-12">
                        <div class="bg-light rounded p-2">
                            @foreach($aiLog->metadata as $key => $value)
                                <small class="d-block"><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}</small>
                            @endforeach
                        </div>
                    </dd>
                    @endif
                </dl>
            </div>
        </div>
        <a href="{{ route('ai-log.index') }}" class="btn btn-outline-secondary w-100 mt-3">
            {{ __('Back to AI History') }}
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var raw = @json($aiLog->result);
    var el = document.getElementById('ai-log-content');
    if (window.renderAiMarkdown) {
        el.innerHTML = window.renderAiMarkdown(raw);
    } else {
        el.textContent = raw;
    }
});
</script>
@endsection
