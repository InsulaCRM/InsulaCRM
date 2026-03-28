@extends('layouts.app')

@section('title', __('Showing Details'))
@section('page-title', __('Showing Details'))

@section('content')
<div class="row">
    <div class="col-md-8">
        {{-- Showing Details --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Showing Information') }}</h3>
                <div class="card-actions">
                    <a href="{{ route('showings.edit', $showing) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('showings.destroy', $showing) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this showing?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Property') }}</div>
                        <div class="datagrid-content">
                            @if($showing->property)
                                <a href="{{ route('properties.show', $showing->property) }}">{{ $showing->property->address }}</a>
                                <div class="text-muted small">{{ $showing->property->city }}, {{ $showing->property->state }} {{ $showing->property->zip_code }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Client') }}</div>
                        <div class="datagrid-content">
                            @if($showing->lead)
                                <a href="{{ route('leads.show', $showing->lead) }}">{{ $showing->lead->first_name }} {{ $showing->lead->last_name }}</a>
                            @else
                                <span class="text-muted">{{ __('Not assigned') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Date & Time') }}</div>
                        <div class="datagrid-content">
                            {{ $showing->showing_date->format('l, M j, Y') }} {{ __('at') }} {{ \Carbon\Carbon::parse($showing->showing_time)->format('g:i A') }}
                            <div class="text-muted small">{{ $showing->duration_minutes }} {{ __('minutes') }}</div>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Agent') }}</div>
                        <div class="datagrid-content">{{ $showing->agent->name ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Status') }}</div>
                        <div class="datagrid-content">
                            @php
                                $statusColors = ['scheduled' => 'blue', 'completed' => 'green', 'cancelled' => 'secondary', 'no_show' => 'red'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$showing->status] ?? 'secondary' }}">{{ \App\Models\Showing::statusLabel($showing->status) }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Outcome') }}</div>
                        <div class="datagrid-content">
                            @if($showing->outcome)
                                @php
                                    $outcomeColors = ['interested' => 'green', 'not_interested' => 'secondary', 'made_offer' => 'purple', 'needs_second_showing' => 'yellow'];
                                @endphp
                                <span class="badge bg-{{ $outcomeColors[$showing->outcome] ?? 'secondary' }}">{{ \App\Models\Showing::outcomeLabel($showing->outcome) }}</span>
                            @else
                                <span class="text-muted">{{ __('Pending') }}</span>
                            @endif
                        </div>
                    </div>
                    @if($showing->listing_agent_name)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Listing Agent') }}</div>
                        <div class="datagrid-content">
                            {{ $showing->listing_agent_name }}
                            @if($showing->listing_agent_phone)
                                <div class="text-muted small">{{ $showing->listing_agent_phone }}</div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($showing->deal)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Transaction') }}</div>
                        <div class="datagrid-content">
                            <a href="{{ url('/pipeline/' . $showing->deal_id) }}">{{ $showing->deal->title }}</a>
                        </div>
                    </div>
                    @endif
                </div>

                @if($showing->notes)
                <div class="mt-3">
                    <h4 class="subheader">{{ __('Notes') }}</h4>
                    <p>{{ $showing->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Feedback Card --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Feedback & Outcome') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('showings.update', $showing) }}">
                    @csrf @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Models\Showing::STATUSES as $key => $label)
                                    <option value="{{ $key }}" {{ $showing->status === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Outcome') }}</label>
                            <select name="outcome" class="form-select">
                                <option value="">{{ __('Select outcome...') }}</option>
                                @foreach(\App\Models\Showing::OUTCOMES as $key => $label)
                                    <option value="{{ $key }}" {{ $showing->outcome === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Client Feedback') }}</label>
                        <textarea name="feedback" class="form-control" rows="4" placeholder="{{ __('How did the showing go? What did the client think?') }}">{{ $showing->feedback }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('Save Feedback') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- Property Quick View --}}
        @if($showing->property)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Property') }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>{{ $showing->property->address }}</strong>
                    <div class="text-muted">{{ $showing->property->city }}, {{ $showing->property->state }} {{ $showing->property->zip_code }}</div>
                </div>
                @if($showing->property->list_price)
                <div class="mb-2">
                    <span class="text-muted">{{ __('List Price') }}:</span>
                    <strong>{{ Fmt::currency($showing->property->list_price) }}</strong>
                </div>
                @endif
                @if($showing->property->bedrooms || $showing->property->bathrooms)
                <div class="mb-2">
                    @if($showing->property->bedrooms)<span>{{ $showing->property->bedrooms }} {{ __('beds') }}</span>@endif
                    @if($showing->property->bedrooms && $showing->property->bathrooms) / @endif
                    @if($showing->property->bathrooms)<span>{{ $showing->property->bathrooms }} {{ __('baths') }}</span>@endif
                </div>
                @endif
                @if($showing->property->square_footage)
                <div class="mb-2">{{ Fmt::area($showing->property->square_footage) }}</div>
                @endif
                <a href="{{ route('properties.show', $showing->property) }}" class="btn btn-sm btn-outline-primary w-100">{{ __('View Property') }}</a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
