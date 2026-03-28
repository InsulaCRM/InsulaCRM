@extends('layouts.app')

@section('title', __('Edit Showing'))
@section('page-title', __('Edit Showing'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Edit Showing') }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('showings.update', $showing) }}">
            @csrf @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Property') }}</label>
                    <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                        <option value="">{{ __('Select property...') }}</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ old('property_id', $showing->property_id) == $property->id ? 'selected' : '' }}>
                                {{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Client') }}</label>
                    <select name="lead_id" class="form-select @error('lead_id') is-invalid @enderror">
                        <option value="">{{ __('Select client (optional)...') }}</option>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->id }}" {{ old('lead_id', $showing->lead_id) == $lead->id ? 'selected' : '' }}>
                                {{ $lead->first_name }} {{ $lead->last_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('lead_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label required">{{ __('Date') }}</label>
                    <input type="date" name="showing_date" class="form-control @error('showing_date') is-invalid @enderror" value="{{ old('showing_date', $showing->showing_date->format('Y-m-d')) }}" required>
                    @error('showing_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('Time') }}</label>
                    <input type="time" name="showing_time" class="form-control @error('showing_time') is-invalid @enderror" value="{{ old('showing_time', $showing->showing_time) }}" required>
                    @error('showing_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Duration (minutes)') }}</label>
                    <input type="number" name="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', $showing->duration_minutes) }}" min="15" max="480">
                    @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Agent') }}</label>
                    <select name="agent_id" class="form-select @error('agent_id') is-invalid @enderror">
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('agent_id', $showing->agent_id) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    @error('agent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Listing Agent Name') }}</label>
                    <input type="text" name="listing_agent_name" class="form-control" value="{{ old('listing_agent_name', $showing->listing_agent_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Listing Agent Phone') }}</label>
                    <input type="text" name="listing_agent_phone" class="form-control" value="{{ old('listing_agent_phone', $showing->listing_agent_phone) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $showing->notes) }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Update Showing') }}</button>
                <a href="{{ route('showings.show', $showing) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
