@extends('layouts.app')

@section('title', __('Open Houses'))
@section('page-title', __('Open Houses'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Open Houses') }}</h3>
        <div class="card-actions">
            <a href="{{ route('open-houses.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Schedule Open House') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('open-houses.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('Status') }}</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('All') }}</option>
                    @foreach(\App\Models\OpenHouse::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('From') }}</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('To') }}</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            @if(auth()->user()->isAdmin() && $agents->isNotEmpty())
            <div class="col-md-2">
                <label class="form-label">{{ __('Agent') }}</label>
                <select name="agent" class="form-select form-select-sm">
                    <option value="">{{ __('All Agents') }}</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ request('agent') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('open-houses.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Property') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Time') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Attendees') }}</th>
                    <th>{{ __('Agent') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($openHouses as $openHouse)
                <tr>
                    <td>
                        @if($openHouse->property)
                            <a href="{{ route('properties.show', $openHouse->property) }}">{{ $openHouse->property->address }}</a>
                            <div class="text-muted small">{{ $openHouse->property->city }}, {{ $openHouse->property->state }}</div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $openHouse->event_date->format('M j, Y') }}</td>
                    <td>
                        <div>{{ \Carbon\Carbon::parse($openHouse->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($openHouse->end_time)->format('g:i A') }}</div>
                    </td>
                    <td>
                        @php
                            $statusColors = ['scheduled' => 'blue', 'active' => 'cyan', 'completed' => 'green', 'cancelled' => 'secondary'];
                        @endphp
                        <span class="badge bg-{{ $statusColors[$openHouse->status] ?? 'secondary' }}">{{ \App\Models\OpenHouse::statusLabel($openHouse->status) }}</span>
                    </td>
                    <td>
                        <span class="badge bg-purple-lt">{{ $openHouse->attendee_count }}</span>
                    </td>
                    <td>{{ $openHouse->agent->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('open-houses.show', $openHouse) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('No open houses found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($openHouses->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $openHouses->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
    @endif
</div>
@endsection
