@extends('layouts.app')

@section('title', __('Field Scout Dashboard'))
@section('page-title')
    {{ __('Good') }} {{ now()->hour < 12 ? __('morning') : (now()->hour < 17 ? __('afternoon') : __('evening')) }}, {{ explode(' ', auth()->user()->name)[0] }}
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Submit a Property') }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('properties.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label required" for="address">{{ __('Address') }}</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                               id="address" name="address" value="{{ old('address') }}"
                               placeholder="{{ __('123 Main St') }}" required>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label class="form-label required" for="city">{{ __('City') }}</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror"
                                   id="city" name="city" value="{{ old('city') }}" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required" for="state">{{ Fmt::stateLabel() }}</label>
                            <input type="text" class="form-control @error('state') is-invalid @enderror"
                                   id="state" name="state" value="{{ old('state') }}"
                                   maxlength="{{ Fmt::stateMaxLength() }}" required>
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required" for="zip_code">{{ Fmt::postalCodeLabel() }}</label>
                            <input type="text" class="form-control @error('zip_code') is-invalid @enderror"
                                   id="zip_code" name="zip_code" value="{{ old('zip_code') }}"
                                   maxlength="{{ Fmt::postalCodeMaxLength() }}" required>
                            @error('zip_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="property_type">{{ __('Property Type') }}</label>
                        <select class="form-select @error('property_type') is-invalid @enderror"
                                id="property_type" name="property_type" required>
                            <option value="">{{ __('Select type...') }}</option>
                            @foreach(\App\Services\CustomFieldService::getOptions('property_type') as $val => $label)
                                <option value="{{ $val }}" @selected(old('property_type') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('property_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <div class="mb-3">
                        <label class="form-label">{{ __('Distress Markers') }}</label>
                        <div class="row">
                            @php $oldMarkers = old('distress_markers', []); @endphp
                            @foreach(\App\Services\CustomFieldService::getOptions('distress_markers') as $value => $label)
                                <div class="col-sm-6 col-md-4 mb-2">
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                               name="distress_markers[]" value="{{ $value }}"
                                               @checked(in_array($value, $oldMarkers))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="notes">{{ __('Notes') }}</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes" name="notes" rows="4"
                                  placeholder="{{ __('Describe the property condition, owner situation, or any other relevant details...') }}">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-send" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11"/><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                            {{ __('Submit Property') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
