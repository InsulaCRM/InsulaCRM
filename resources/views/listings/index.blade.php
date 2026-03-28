@extends('layouts.app')

@section('title', __('Listings'))
@section('page-title', __('Listings'))

@section('content')
{{-- KPI Cards --}}
<div class="row mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-primary text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M9 8l1 0"/><path d="M9 12l1 0"/><path d="M9 16l1 0"/><path d="M14 8l1 0"/><path d="M14 12l1 0"/><path d="M14 16l1 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $activeCount }}</div>
                        <div class="text-muted">{{ __('Active Listings') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-yellow text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $avgDom ? number_format($avgDom, 0) : '-' }}</div>
                        <div class="text-muted">{{ __('Avg Days on Market') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-green text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/><path d="M12 3v3m0 12v3"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ Fmt::currency($totalVolume) }}</div>
                        <div class="text-muted">{{ __('Total Volume') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-orange text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $showingsThisWeek }} {{ __('showings') }} / {{ $pendingOffers }} {{ __('offers') }}</div>
                        <div class="text-muted">{{ __('This Week') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Listings Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Active Listings') }}</h3>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom py-3">
        <form method="GET" action="{{ route('listings.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('Stage') }}</label>
                <select name="stage" class="form-select form-select-sm">
                    <option value="">{{ __('All Stages') }}</option>
                    @foreach($listingStageLabels as $key => $label)
                        <option value="{{ $key }}" {{ request('stage') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
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
            <div class="col-md-3">
                <label class="form-label">{{ __('Search') }}</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="{{ __('Address, title...') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('listings.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Property') }}</th>
                    <th>{{ __('List Price') }}</th>
                    <th>{{ __('DOM') }}</th>
                    <th>{{ __('MLS #') }}</th>
                    <th>{{ __('Stage') }}</th>
                    <th>{{ __('Agent') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($listings as $deal)
                @php $property = $deal->lead?->property; @endphp
                <tr>
                    <td>
                        @if($property)
                            <div class="fw-bold">{{ $property->address }}</div>
                            <div class="text-muted small">{{ $property->city }}, {{ $property->state }} {{ $property->zip_code }}</div>
                            @if($property->bedrooms || $property->bathrooms || $property->square_footage)
                            <div class="text-muted small">
                                @if($property->bedrooms){{ $property->bedrooms }} {{ __('bd') }}@endif
                                @if($property->bathrooms) / {{ $property->bathrooms }} {{ __('ba') }}@endif
                                @if($property->square_footage) / {{ Fmt::area($property->square_footage) }}@endif
                            </div>
                            @endif
                        @else
                            <span class="text-muted">{{ $deal->title }}</span>
                        @endif
                    </td>
                    <td>
                        @if($property && $property->list_price)
                            <strong>{{ Fmt::currency($property->list_price) }}</strong>
                        @elseif($deal->contract_price)
                            <span class="text-muted">{{ Fmt::currency($deal->contract_price) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($deal->days_on_market)
                            <span class="{{ $deal->days_on_market > 60 ? 'text-danger' : ($deal->days_on_market > 30 ? 'text-warning' : '') }}">
                                {{ $deal->days_on_market }} {{ __('days') }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $deal->mls_number ?? '-' }}</td>
                    <td>
                        @php
                            $stageColors = [
                                'listing_agreement' => 'blue',
                                'active_listing' => 'green',
                                'showing' => 'orange',
                                'offer_received' => 'purple',
                            ];
                        @endphp
                        <span class="badge bg-{{ $stageColors[$deal->stage] ?? 'secondary' }}">{{ \App\Models\Deal::stageLabel($deal->stage) }}</span>
                    </td>
                    <td>{{ $deal->agent->name ?? '-' }}</td>
                    <td>
                        <a href="{{ url('/pipeline/' . $deal->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('No active listings found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($listings->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $listings->appends(request()->query())->links('vendor.pagination.tabler') }}
    </div>
    @endif
</div>
@endsection
