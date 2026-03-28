@extends('layouts.app')

@section('title', __('Notifications'))
@section('page-title', __('Notifications'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Notifications') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('All Notifications') }}</h3>
        <div class="card-actions">
            @if(($unreadCount ?? auth()->user()->unreadNotifications->count()) > 0)
            <form method="POST" action="{{ route('notifications.markAllRead') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l10 -10"/><path d="M2 12l5 5m5 -5l5 -5"/></svg>
                    {{ __('Mark all as read') }}
                </button>
            </form>
            @endif
        </div>
    </div>
    <div class="card-body border-bottom py-2">
        <div class="d-flex gap-1 flex-wrap">
            @php $currentFilter = $filter ?? 'all'; @endphp
            <a href="{{ route('notifications.index') }}" class="btn btn-sm {{ $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('All') }}</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="btn btn-sm {{ $currentFilter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ __('Unread') }}
                @if(($unreadCount ?? 0) > 0)<span class="badge bg-red ms-1">{{ $unreadCount }}</span>@endif
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'leads']) }}" class="btn btn-sm {{ $currentFilter === 'leads' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Leads') }}</a>
            <a href="{{ route('notifications.index', ['filter' => 'deals']) }}" class="btn btn-sm {{ $currentFilter === 'deals' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $modeTerms['deal_label'] ?? __('Deals') }}s</a>
            <a href="{{ route('notifications.index', ['filter' => 'tasks']) }}" class="btn btn-sm {{ $currentFilter === 'tasks' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Tasks') }}</a>
            <a href="{{ route('notifications.index', ['filter' => 'team']) }}" class="btn btn-sm {{ $currentFilter === 'team' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Team') }}</a>
        </div>
    </div>
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
        @php
            $data = $notification->data;
            $isUnread = $notification->read_at === null;
            $colorMap = [
                'blue' => 'bg-blue',
                'purple' => 'bg-purple',
                'orange' => 'bg-orange',
                'green' => 'bg-green',
                'cyan' => 'bg-cyan',
                'red' => 'bg-red',
            ];
            $badgeColor = $colorMap[$data['color'] ?? 'blue'] ?? 'bg-blue';
        @endphp
        <a href="{{ $data['url'] ?? '#' }}"
           class="list-group-item list-group-item-action {{ $isUnread ? '' : 'bg-transparent' }}"
           style="{{ $isUnread ? 'background: #f0f6ff; border-left: 3px solid #206bc4;' : '' }}"
           @if($isUnread)
           onclick="fetch('{{ route('notifications.markAsRead', $notification->id) }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});"
           @endif
        >
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar avatar-sm {{ $badgeColor }}-lt">
                        @switch($data['icon'] ?? 'bell')
                            @case('user-plus')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 11h6"/><path d="M19 8v6"/></svg>
                                @break
                            @case('arrow-right')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="13" y1="18" x2="19" y2="12"/><line x1="13" y1="6" x2="19" y2="12"/></svg>
                                @break
                            @case('alert-triangle')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84-2.75l-7.1-12.25a2 2 0 0 0-3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
                                @break
                            @case('users')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
                                @break
                            @case('user-check')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 11l2 2l4-4"/></svg>
                                @break
                            @default
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2-3v-3a7 7 0 0 1 4-6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                        @endswitch
                    </span>
                </div>
                <div class="col">
                    <div class="d-flex justify-content-between">
                        <strong class="{{ $isUnread ? '' : 'text-muted' }}">{{ $data['title'] ?? __('Notification') }}</strong>
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="text-muted small">{{ $data['body'] ?? '' }}</div>
                </div>
                @if($isUnread)
                <div class="col-auto">
                    <span class="badge bg-blue badge-pill" aria-label="{{ __('Unread') }}"></span>
                </div>
                @endif
            </div>
        </a>
        @empty
        <div class="list-group-item text-center py-4">
            @if(($filter ?? 'all') === 'unread')
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-success mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                <div class="text-secondary">{{ __('All caught up! No unread notifications.') }}</div>
            @elseif(($filter ?? 'all') !== 'all')
                <div class="text-secondary">{{ __('No notifications in this category.') }}</div>
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary mt-2">{{ __('View All') }}</a>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-secondary mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2-3v-3a7 7 0 0 1 4-6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                <div class="text-secondary">{{ __('No notifications yet. You\'ll see updates here when there\'s activity on your leads, deals, or tasks.') }}</div>
            @endif
        </div>
        @endforelse
    </div>
    @if($notifications->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $notifications->links('vendor.pagination.tabler') }}
    </div>
    @endif
</div>
@endsection
