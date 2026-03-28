@extends('layouts.app')

@section('title', __('Audit Log'))
@section('page-title', __('Audit Log'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Audit Log') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Activity Log') }}</h3>
        <div class="card-actions">
            <a href="{{ route('audit-log.export', request()->query()) }}" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                {{ __('Export CSV') }}
            </a>
        </div>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('audit-log.index') }}" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search action or user...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="action" class="form-select">
                    <option value="">{{ __('All Actions') }}</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">{{ __('All Users') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
            </div>
            @if(request()->hasAny(['search', 'action', 'user_id']))
            <div class="col-auto">
                <a href="{{ route('audit-log.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Date/Time') }}</th>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Action') }}</th>
                    <th>{{ __('Details') }}</th>
                    <th>{{ __('Changes') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-secondary">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                    <td>{{ $log->user->name ?? __('System') }}</td>
                    <td>
                        @php
                            $actionStr = $log->action;
                            if (str_contains($actionStr, 'created') || str_contains($actionStr, 'invited')) {
                                $badgeClass = 'bg-green-lt';
                            } elseif (str_contains($actionStr, 'updated') || str_contains($actionStr, 'toggled')) {
                                $badgeClass = 'bg-blue-lt';
                            } elseif (str_contains($actionStr, 'deleted') || str_contains($actionStr, 'removed')) {
                                $badgeClass = 'bg-red-lt';
                            } elseif (str_contains($actionStr, 'stage_changed')) {
                                $badgeClass = 'bg-purple-lt';
                            } else {
                                $badgeClass = 'bg-secondary-lt';
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $actionStr }}</span>
                    </td>
                    <td class="text-secondary">
                        @if($log->model_type)
                            {{ class_basename($log->model_type) }} #{{ $log->model_id }}
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        @if($log->old_values || $log->new_values)
                            @foreach(($log->new_values ?? []) as $key => $newVal)
                                <small class="d-block">
                                    <strong>{{ $key }}:</strong>
                                    @if(isset($log->old_values[$key]))
                                        <span class="text-danger">{{ $log->old_values[$key] }}</span> &rarr;
                                    @endif
                                    <span class="text-success">{{ $newVal }}</span>
                                </small>
                            @endforeach
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-secondary">{{ __('No audit log entries found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $logs->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $logs->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $logs->total() }}</span> {{ __('entries') }}</p>
        <div class="ms-auto">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
