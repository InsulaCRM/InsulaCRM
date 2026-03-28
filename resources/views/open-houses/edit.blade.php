@extends('layouts.app')

@section('title', __('Edit Open House'))
@section('page-title', __('Edit Open House'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Edit Open House') }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('open-houses.update', $openHouse) }}">
            @csrf @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('Property') }}</label>
                    <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                        <option value="">{{ __('Select property...') }}</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ old('property_id', $openHouse->property_id) == $property->id ? 'selected' : '' }}>
                                {{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Agent') }}</label>
                    <select name="agent_id" class="form-select @error('agent_id') is-invalid @enderror">
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('agent_id', $openHouse->agent_id) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    @error('agent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Date') }}</label>
                    <input type="date" name="event_date" class="form-control @error('event_date') is-invalid @enderror" value="{{ old('event_date', $openHouse->event_date->format('Y-m-d')) }}" required>
                    @error('event_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('Start Time') }}</label>
                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $openHouse->start_time) }}" required>
                    @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('End Time') }}</label>
                    <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $openHouse->end_time) }}" required>
                    @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach(\App\Models\OpenHouse::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $openHouse->status) === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $openHouse->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $openHouse->notes) }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Update Open House') }}</button>
                <a href="{{ route('open-houses.show', $openHouse) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
