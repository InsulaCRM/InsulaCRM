@extends('layouts.auth')

@section('title', __('Register'))

@section('content')
<div class="card card-md">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">{{ __('Create new account') }}</h2>
        <form action="{{ route('register') }}" method="POST" autocomplete="off">
            @csrf
            <div class="mb-3">
                <label class="form-label">{{ __('Company Name') }}</label>
                <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                       placeholder="{{ __('Your company') }}" value="{{ old('company_name') }}" required>
                @error('company_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Full Name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       placeholder="{{ __('John Doe') }}" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Email address') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('your@email.com') }}" value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Password') }}</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Password') }}" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Confirm Password') }}</label>
                <input type="password" name="password_confirmation" class="form-control"
                       placeholder="{{ __('Confirm password') }}" required>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">{{ __('Create new account') }}</button>
            </div>
        </form>
    </div>
</div>
<div class="text-center text-secondary mt-3">
    {{ __('Already have account?') }} <a href="{{ route('login') }}" tabindex="-1">{{ __('Sign in') }}</a>
</div>
@endsection
