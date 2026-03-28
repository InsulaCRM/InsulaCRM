@extends('layouts.app')

@section('title', __('AI History'))
@section('page-title', __('AI History'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('AI History') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('AI Generated Outputs') }}</h3>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('ai-log.index') }}" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">{{ __('All Users') }}</option>
                    <option value="system" {{ request('user_id') === 'system' ? 'selected' : '' }}>{{ __('System (scheduled)') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id && request('user_id') !== 'system' ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
            </div>
            @if(request()->hasAny(['search', 'type', 'user_id']))
            <div class="col-auto">
                <a href="{{ route('ai-log.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Date/Time') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Preview') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-secondary" style="white-space: nowrap;">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                    <td>
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
                                'arv_analysis' => 'bg-teal-lt',
                                'document_draft' => 'bg-lime-lt',
                                'campaign_insights' => 'bg-blue-lt',
                                'buyer_risk' => 'bg-red-lt',
                                'goal_recommendations' => 'bg-cyan-lt',
                                'portal_description' => 'bg-green-lt',
                                'workflow_qualify' => 'bg-purple-lt',
                                'draft_email' => 'bg-indigo-lt',
                            ];
                            $badgeClass = $typeColors[$log->type] ?? 'bg-secondary-lt';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ __(ucwords(str_replace('_', ' ', $log->type))) }}</span>
                    </td>
                    <td>{{ $log->user?->name ?? __('System') }}</td>
                    <td>
                        @if($log->model_type)
                            @if($log->subject_url)
                                <a href="{{ $log->subject_url }}">{{ $log->subject_label }}</a>
                            @else
                                {{ $log->subject_label }}
                            @endif
                            @if($log->prompt_summary)
                                <br><small class="text-muted">{{ $log->prompt_summary }}</small>
                            @endif
                        @elseif($log->prompt_summary)
                            <span class="text-muted">{{ $log->prompt_summary }}</span>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-secondary">
                        <span title="{{ Str::limit(strip_tags($log->result), 300) }}">{{ Str::limit(strip_tags($log->result), 80) }}</span>
                    </td>
                    <td>
                        <a href="{{ route('ai-log.show', $log) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-secondary py-4">{{ __('No AI history entries yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $logs->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $logs->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $logs->total() }}</span> {{ __('entries') }}</p>
        <div class="ms-auto">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
