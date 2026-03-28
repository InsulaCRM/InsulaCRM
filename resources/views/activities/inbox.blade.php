@extends('layouts.app')

@section('title', __('Activity Feed'))
@section('page-title', __('Activity Feed'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Activity Feed') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('All Activities') }}</h3>
    </div>
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('activities.index') }}" class="row g-2">
            <div class="col-md-2">
                <select name="type[]" class="form-select" multiple size="1">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach($activityTypes as $val => $label)
                        <option value="{{ $val }}" {{ in_array($val, (array) request('type')) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->isAdmin())
            <div class="col-md-2">
                <select name="agent_id" class="form-select">
                    <option value="">{{ __('All Agents') }}</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="{{ __('From') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="{{ __('To') }}">
            </div>
            <div class="col-md-2">
                <select name="entity_type" class="form-select">
                    <option value="">{{ __('All Entities') }}</option>
                    <option value="lead" {{ request('entity_type') == 'lead' ? 'selected' : '' }}>{{ __('Leads') }}</option>
                    <option value="deal" {{ request('entity_type') == 'deal' ? 'selected' : '' }}>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transactions') : __('Deals') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
                </div>
            </div>
            @if(request()->hasAny(['type', 'agent_id', 'date_from', 'date_to', 'entity_type', 'search']))
            <div class="col-auto">
                <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
            </div>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>{{ __('Activity') }}</th>
                    <th>{{ __('Related To') }}</th>
                    <th>{{ __('Agent') }}</th>
                    <th>{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                <tr>
                    <td>
                        @php
                            $icons = [
                                'call' => ['phone', 'blue'],
                                'sms' => ['message', 'cyan'],
                                'email' => ['mail', 'green'],
                                'note' => ['note', 'yellow'],
                                'meeting' => ['users', 'purple'],
                                'voicemail' => ['phone-incoming', 'orange'],
                                'direct_mail' => ['mail-forward', 'red'],
                                'stage_change' => ['arrows-exchange', 'indigo'],
                            ];
                            $icon = $icons[$activity->type] ?? ['activity', 'secondary'];
                        @endphp
                        <span class="avatar avatar-sm bg-{{ $icon[1] }}-lt text-{{ $icon[1] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="10"/></svg>
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $activity->subject ?: __(ucfirst($activity->type)) }}</div>
                        @if($activity->body)
                        <div class="text-secondary small">{{ Str::limit(strip_tags($activity->body), 80) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($activity->lead)
                            <a href="{{ route('leads.show', $activity->lead_id) }}" class="text-reset">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                {{ $activity->lead->first_name }} {{ $activity->lead->last_name }}
                            </a>
                        @endif
                        @if($activity->deal)
                            <a href="{{ route('deals.show', $activity->deal_id) }}" class="text-reset">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="3 17 9 11 13 15 21 7"/><polyline points="14 7 21 7 21 14"/></svg>
                                {{ $activity->deal->title }}
                            </a>
                        @endif
                    </td>
                    <td>{{ $activity->agent->name ?? '—' }}</td>
                    <td>
                        <span title="{{ $activity->logged_at?->format('Y-m-d H:i') }}">
                            {{ $activity->logged_at?->diffForHumans() ?? '—' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">{{ __('No activities found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($activities->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $activities->withQueryString()->links('vendor.pagination.tabler') }}
    </div>
    @endif
</div>
@endsection